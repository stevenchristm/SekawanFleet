<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /** Status yang "memegang" kendaraan & driver pada rentang waktunya. */
    public const BLOCKING_STATUSES = [BookingStatus::Pending, BookingStatus::Approved];

    /** Kunci advisory lock PostgreSQL untuk serialisasi pembuatan kode booking. */
    private const CODE_LOCK_KEY = 730101;

    /**
     * Buat booking baru beserta rantai approval berjenjang.
     *
     * @param  array{vehicle_id:int, driver_id:int, requester_name:string, purpose:string,
     *               destination:string, start_at:string, end_at:string, approvers:array<int>}  $data
     */
    public function create(array $data, User $admin): Booking
    {
        $start = Carbon::parse($data['start_at']);
        $end = Carbon::parse($data['end_at']);

        try {
            return DB::transaction(function () use ($data, $admin, $start, $end) {
                // Kunci baris kendaraan & driver: dua admin yang memesan resource yang sama
                // di saat bersamaan akan antre di sini, sehingga cek bentrok di bawah aman.
                Vehicle::whereKey($data['vehicle_id'])->lockForUpdate()->firstOrFail();
                Driver::whereKey($data['driver_id'])->lockForUpdate()->firstOrFail();

                $this->ensureNoConflict($data['vehicle_id'], $data['driver_id'], $start, $end);

                // Serialisasi penomoran BK-YYYYMM-NNNN (dibuat di Booking::creating).
                DB::select('SELECT pg_advisory_xact_lock(?)', [self::CODE_LOCK_KEY]);

                $booking = Booking::create([
                    'vehicle_id' => $data['vehicle_id'],
                    'driver_id' => $data['driver_id'],
                    'created_by' => $admin->id,
                    'requester_name' => $data['requester_name'],
                    'purpose' => $data['purpose'],
                    'destination' => $data['destination'],
                    'start_at' => $start,
                    'end_at' => $end,
                    'status' => BookingStatus::Pending,
                    'current_level' => 1,
                ]);

                foreach (array_values($data['approvers']) as $index => $approverId) {
                    BookingApproval::create([
                        'booking_id' => $booking->id,
                        'approver_id' => $approverId,
                        'level' => $index + 1,
                        // Hanya level 1 yang langsung bisa bertindak
                        'status' => $index === 0 ? ApprovalStatus::Pending : ApprovalStatus::Waiting,
                    ]);
                }

                return $booking;
            });
        } catch (QueryException $e) {
            // 23P01 = exclusion_violation (constraint no_vehicle_overlap / no_driver_overlap).
            // Pengaman terakhir kalau cek di aplikasi terlewati.
            if ($e->getCode() === '23P01') {
                $field = str_contains($e->getMessage(), 'no_driver_overlap') ? 'driver_id' : 'vehicle_id';

                throw ValidationException::withMessages([
                    $field => $field === 'driver_id'
                        ? 'Driver sudah dipesan pada rentang waktu tersebut.'
                        : 'Kendaraan sudah dipesan pada rentang waktu tersebut.',
                ]);
            }

            throw $e;
        }
    }

    /**
     * Batalkan booking yang belum berjalan. Approval yang belum diproses ditandai skipped.
     */
    public function cancel(Booking $booking, User $user): void
    {
        DB::transaction(function () use ($booking, $user) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! in_array($booking->status, self::BLOCKING_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'booking' => 'Pemesanan dengan status '.$booking->status->label().' tidak bisa dibatalkan.',
                ]);
            }

            $booking->approvals()
                ->whereIn('status', [ApprovalStatus::Pending, ApprovalStatus::Waiting])
                ->update(['status' => ApprovalStatus::Skipped]);

            $booking->update(['status' => BookingStatus::Cancelled]);

            activity('booking')->performedOn($booking)->causedBy($user)
                ->log("Pemesanan {$booking->code} dibatalkan");
        });
    }

    /**
     * Tolak dengan pesan per-field kalau kendaraan atau driver bentrok jadwal.
     */
    public function ensureNoConflict(int $vehicleId, int $driverId, Carbon $start, Carbon $end, ?int $ignoreBookingId = null): void
    {
        $overlapping = fn () => Booking::query()
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->when($ignoreBookingId, fn ($q) => $q->whereKeyNot($ignoreBookingId));

        $errors = [];

        if ($clash = $overlapping()->where('vehicle_id', $vehicleId)->first()) {
            $errors['vehicle_id'] = "Kendaraan sudah dipesan ({$clash->code}) pada "
                .$clash->start_at->format('d/m/Y H:i').' - '.$clash->end_at->format('d/m/Y H:i').'.';
        }

        if ($clash = $overlapping()->where('driver_id', $driverId)->first()) {
            $errors['driver_id'] = "Driver sudah bertugas ({$clash->code}) pada "
                .$clash->start_at->format('d/m/Y H:i').' - '.$clash->end_at->format('d/m/Y H:i').'.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
