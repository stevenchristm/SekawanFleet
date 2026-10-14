<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agregasi data dashboard. Semua query dihitung di PostgreSQL (FILTER, generate_series)
 * supaya tidak ada perulangan data besar di PHP.
 */
class DashboardService
{
    /** Status booking yang dihitung sebagai "kendaraan terpakai" (literal SQL). */
    private const USED = "'approved','completed'";

    public function kpis(): array
    {
        $now = now();

        return [
            'bookings_this_month' => Booking::where('start_at', '>=', $now->copy()->startOfMonth())
                ->where('start_at', '<', $now->copy()->addMonth()->startOfMonth())->count(),
            'pending' => Booking::where('status', BookingStatus::Pending)->count(),
            'in_use_today' => Booking::whereIn('status', [BookingStatus::Approved, BookingStatus::Completed])
                ->where('start_at', '<', $now->copy()->endOfDay())
                ->where('end_at', '>', $now->copy()->startOfDay())
                ->distinct('vehicle_id')->count('vehicle_id'),
            'vehicles_available' => Vehicle::where('status', VehicleStatus::Available)->count(),
            'vehicles_total' => Vehicle::count(),
        ];
    }

    public function approverKpis(User $user): array
    {
        $monthStart = now()->startOfMonth();

        $base = BookingApproval::where('approver_id', $user->id);

        return [
            'waiting_me' => (clone $base)->where('status', ApprovalStatus::Pending)->count(),
            'approved_this_month' => (clone $base)->where('status', ApprovalStatus::Approved)
                ->where('acted_at', '>=', $monthStart)->count(),
            'rejected_this_month' => (clone $base)->where('status', ApprovalStatus::Rejected)
                ->where('acted_at', '>=', $monthStart)->count(),
        ];
    }

    /**
     * Data semua grafik untuk N bulan terakhir (termasuk bulan berjalan).
     */
    public function charts(int $months): array
    {
        $from = now()->startOfMonth()->subMonths($months - 1);

        return [
            'monthly' => $this->monthly($from),
            'topVehicles' => $this->topVehicles($from),
            'status' => $this->statusComposition($from),
            'byType' => $this->byVehicleType($from),
            'fuel' => $this->fuelAndDistance($from),
        ];
    }

    /** Booking per bulan, dipecah: terpakai / menunggu / ditolak-batal. Bulan kosong tetap muncul. */
    private function monthly(Carbon $from): array
    {
        $used = self::USED;

        $rows = DB::select("
            SELECT m AS month,
                   COUNT(b.id) FILTER (WHERE b.status IN ({$used}))                AS used,
                   COUNT(b.id) FILTER (WHERE b.status = 'pending')                 AS pending,
                   COUNT(b.id) FILTER (WHERE b.status IN ('rejected','cancelled')) AS rejected
            FROM generate_series(?::timestamptz, date_trunc('month', ?::timestamptz), interval '1 month') AS m
            LEFT JOIN bookings b ON date_trunc('month', b.start_at) = m
            GROUP BY m
            ORDER BY m
        ", [$from, now()]);

        return [
            'labels' => array_map(fn ($r) => $this->monthLabel($r->month), $rows),
            'used' => array_map(fn ($r) => (int) $r->used, $rows),
            'pending' => array_map(fn ($r) => (int) $r->pending, $rows),
            'rejected' => array_map(fn ($r) => (int) $r->rejected, $rows),
        ];
    }

    /** 10 kendaraan paling sering dipakai: jumlah perjalanan & total jam. */
    private function topVehicles(Carbon $from): array
    {
        $used = self::USED;

        $rows = DB::select("
            SELECT v.plate_number,
                   COUNT(b.id) AS trips,
                   ROUND(SUM(EXTRACT(EPOCH FROM (b.end_at - b.start_at)) / 3600)::numeric, 1) AS hours
            FROM bookings b
            JOIN vehicles v ON v.id = b.vehicle_id
            WHERE b.status IN ({$used}) AND b.start_at >= ?
            GROUP BY v.id, v.plate_number
            ORDER BY trips DESC, hours DESC
            LIMIT 10
        ", [$from]);

        return [
            'labels' => array_column($rows, 'plate_number'),
            'trips' => array_map(fn ($r) => (int) $r->trips, $rows),
            'hours' => array_map(fn ($r) => (float) $r->hours, $rows),
        ];
    }

    private function statusComposition(Carbon $from): array
    {
        $counts = Booking::where('start_at', '>=', $from)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = BookingStatus::cases();

        return [
            'labels' => array_map(fn ($s) => $s->label(), $statuses),
            'keys' => array_map(fn ($s) => $s->value, $statuses),
            'data' => array_map(fn ($s) => (int) ($counts[$s->value] ?? 0), $statuses),
        ];
    }

    /** Pemakaian per jenis angkutan, dipisah milik sendiri vs sewa. */
    private function byVehicleType(Carbon $from): array
    {
        $used = self::USED;

        $rows = collect(DB::select("
            SELECT v.type, v.ownership, COUNT(b.id) AS trips
            FROM bookings b
            JOIN vehicles v ON v.id = b.vehicle_id
            WHERE b.status IN ({$used}) AND b.start_at >= ?
            GROUP BY v.type, v.ownership
        ", [$from]));

        $pick = fn (string $type, string $own) => (int) ($rows->first(
            fn ($r) => $r->type === $type && $r->ownership === $own
        )?->trips ?? 0);

        return [
            'labels' => ['Angkutan Orang', 'Angkutan Barang'],
            'owned' => [$pick('passenger', 'owned'), $pick('cargo', 'owned')],
            'rented' => [$pick('passenger', 'rented'), $pick('cargo', 'rented')],
        ];
    }

    /** Konsumsi BBM (liter) & jarak tempuh (km) per bulan dari data pengembalian kendaraan. */
    private function fuelAndDistance(Carbon $from): array
    {
        $rows = DB::select("
            SELECT m AS month,
                   COALESCE(SUM(u.fuel_liters), 0)                      AS liters,
                   COALESCE(SUM(u.end_odometer - u.start_odometer), 0) AS km
            FROM generate_series(?::timestamptz, date_trunc('month', ?::timestamptz), interval '1 month') AS m
            LEFT JOIN bookings b ON date_trunc('month', b.start_at) = m AND b.status = 'completed'
            LEFT JOIN vehicle_usages u ON u.booking_id = b.id
            GROUP BY m
            ORDER BY m
        ", [$from, now()]);

        return [
            'labels' => array_map(fn ($r) => $this->monthLabel($r->month), $rows),
            'liters' => array_map(fn ($r) => (float) $r->liters, $rows),
            'km' => array_map(fn ($r) => (int) $r->km, $rows),
        ];
    }

    private function monthLabel(string $timestamp): string
    {
        return Carbon::parse($timestamp)->locale('id')->translatedFormat('M Y');
    }
}
