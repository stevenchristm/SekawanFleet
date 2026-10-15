<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\ServiceSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * Contoh isi log aktivitas untuk 14 hari terakhir, dibangun dari data booking
 * hasil BookingSeeder (dibuat, disetujui/ditolak, dibatalkan, dikembalikan),
 * ditambah login/logout. Halaman Log Aktivitas jadi tidak kosong saat demo.
 *
 * Event model dimatikan supaya entri bertanggal lampau ini tidak ikut
 * ditulis ke file log harian (storage/logs/activity-*.log).
 */
class ActivityLogSeeder extends Seeder
{
    private const CONTEXT = ['ip' => '127.0.0.1', 'user_agent' => 'Seeder (data demo)'];

    /** @var array<int, array<string, mixed>> */
    private array $entries = [];

    public function run(): void
    {
        Activity::withoutEvents(function () {
            $admin = User::where('role', 'admin')->firstOrFail();
            $since = now()->subDays(14);

            $bookings = Booking::with(['approvals.approver', 'usage', 'vehicle'])
                ->where('created_at', '>=', $since)
                ->orderBy('created_at')
                ->get();

            foreach ($bookings as $booking) {
                $this->auth($admin, 'login', 'Login', $booking->created_at->copy()->subMinutes(5));

                $this->log('booking', 'created', "Pemesanan {$booking->code} dibuat", $admin, $booking, $booking->created_at, [
                    'attributes' => [
                        'code' => $booking->code,
                        'vehicle_id' => $booking->vehicle_id,
                        'driver_id' => $booking->driver_id,
                        'destination' => $booking->destination,
                        'start_at' => $booking->start_at->toDateTimeString(),
                        'end_at' => $booking->end_at->toDateTimeString(),
                        'status' => 'pending',
                    ],
                ]);

                foreach ($booking->approvals->whereNotNull('acted_at') as $approval) {
                    $approve = $approval->status->value === 'approved';
                    $last = $approval->level === $booking->approvals->max('level');

                    $this->auth($approval->approver, 'login', 'Login', $approval->acted_at->copy()->subMinutes(3));
                    $this->log('approval', $approve ? 'approved' : 'rejected',
                        ($approve ? 'Menyetujui' : 'Menolak')." pemesanan {$booking->code} (level {$approval->level})",
                        $approval->approver, $booking, $approval->acted_at, [
                            'level' => $approval->level,
                            'decision' => $approval->status->value,
                            'note' => $approval->note,
                            'booking_status' => ! $approve ? 'rejected' : ($last ? 'approved' : 'pending'),
                        ]);
                    $this->auth($approval->approver, 'logout', 'Logout', $approval->acted_at->copy()->addMinutes(2));
                }

                if ($booking->status->value === 'cancelled') {
                    $this->log('booking', 'cancelled', "Pemesanan {$booking->code} dibatalkan", $admin, $booking,
                        $booking->created_at->copy()->addDay());
                }

                if ($usage = $booking->usage) {
                    $this->log('booking', 'completed', "Pengembalian kendaraan {$booking->code} dicatat ({$usage->distance} km)",
                        $admin, $booking, $usage->returned_at->copy()->addMinutes(30), [
                            'start_odometer' => $usage->start_odometer,
                            'end_odometer' => $usage->end_odometer,
                            'fuel_liters' => (float) $usage->fuel_liters,
                        ]);
                }
            }

            // Jadwal servis: dijadwalkan & diselesaikan dalam 14 hari terakhir.
            $services = ServiceSchedule::with('vehicle')
                ->where(fn ($q) => $q->where('created_at', '>=', $since)->orWhere('completed_date', '>=', $since->toDateString()))
                ->get();

            foreach ($services as $service) {
                $plate = $service->vehicle->plate_number;
                if ($service->created_at->gte($since)) {
                    $this->log('service', 'scheduled', "{$service->type->label()} {$plate} dijadwalkan {$service->scheduled_date->format('d/m/Y')}",
                        $admin, $service, $service->created_at, array_filter([
                            'vehicle' => $plate,
                            'type' => $service->type->value,
                            'scheduled_date' => $service->scheduled_date->toDateString(),
                            'odometer_due' => $service->odometer_due,
                        ]));
                }
                if ($service->completed_date?->gte($since->copy()->startOfDay())) {
                    $this->log('service', 'completed', "{$service->type->label()} {$plate} selesai dikerjakan",
                        $admin, $service, $service->completed_date->copy()->setTime(15, 0), array_filter([
                            'vehicle' => $plate,
                            'completed_date' => $service->completed_date->toDateString(),
                            'odometer_at_service' => $service->odometer_at_service,
                            'cost' => $service->cost,
                            'workshop' => $service->workshop,
                        ]));
                }
            }

            // Satu percobaan login gagal sebagai contoh (password tidak pernah dicatat).
            $this->log('auth', 'login_failed', 'Login gagal', null, null, now()->subHours(6),
                self::CONTEXT + ['email' => 'admin@nikel.tes']);

            $this->flush();
        });
    }

    private function auth(User $user, string $event, string $description, Carbon $at): void
    {
        $this->log('auth', $event, $description, $user, null, $at, self::CONTEXT);
    }

    private function log(string $logName, string $event, string $description, ?User $causer, ?Model $subject, Carbon $at, array $properties = []): void
    {
        // Ditampung dulu; ditulis berurutan waktu di flush() supaya urutan id = urutan kejadian.
        $this->entries[] = compact('logName', 'event', 'description', 'causer', 'subject', 'at', 'properties');
    }

    private function flush(): void
    {
        usort($this->entries, fn ($a, $b) => $a['at'] <=> $b['at']);

        foreach ($this->entries as ['logName' => $logName, 'event' => $event, 'description' => $description,
            'causer' => $causer, 'subject' => $subject, 'at' => $at, 'properties' => $properties]) {
            $this->write($logName, $event, $description, $causer, $subject, $at, $properties);
        }

        $this->entries = [];
    }

    private function write(string $logName, string $event, string $description, ?User $causer, ?Model $subject, Carbon $at, array $properties): void
    {
        $logger = activity($logName)->event($event)->withProperties($properties)->createdAt($at);

        if ($causer) {
            $logger->causedBy($causer);
        }
        if ($subject) {
            $logger->performedOn($subject);
        }

        $logger->log($description);
    }
}
