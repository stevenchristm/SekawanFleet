<?php

namespace App\Services;

use App\Enums\ServiceStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Agregasi pemakaian per kendaraan (perjalanan, jarak, BBM, odometer) untuk halaman Kendaraan.
 * Semua dihitung di PostgreSQL dari vehicle_usages (dicatat saat kendaraan kembali).
 */
class FleetStatsService
{
    /**
     * Statistik pemakaian semua (atau sebagian) kendaraan, di-key per vehicle_id.
     *
     * @param  array<int>|null  $vehicleIds
     * @return Collection<int, object{trips:int, km:int, liters:float, odometer:?int}>
     */
    public function usageTotals(?array $vehicleIds = null): Collection
    {
        $usage = DB::table('vehicle_usages as u')
            ->join('bookings as b', 'b.id', '=', 'u.booking_id')
            ->when($vehicleIds !== null, fn ($q) => $q->whereIn('b.vehicle_id', $vehicleIds))
            ->groupBy('b.vehicle_id')
            ->selectRaw('b.vehicle_id, COUNT(*) AS trips,
                COALESCE(SUM(u.end_odometer - u.start_odometer), 0) AS km,
                COALESCE(SUM(u.fuel_liters), 0) AS liters,
                MAX(u.end_odometer) AS odometer')
            ->get()
            ->keyBy('vehicle_id');

        $serviceOdometer = DB::table('service_schedules')
            ->where('status', ServiceStatus::Completed->value)
            ->when($vehicleIds !== null, fn ($q) => $q->whereIn('vehicle_id', $vehicleIds))
            ->groupBy('vehicle_id')
            ->selectRaw('vehicle_id, MAX(odometer_at_service) AS odometer')
            ->pluck('odometer', 'vehicle_id');

        $ids = $usage->keys()->merge($serviceOdometer->keys())->unique();

        return $ids->mapWithKeys(function ($id) use ($usage, $serviceOdometer) {
            $u = $usage->get($id);
            $odometers = array_filter([$u?->odometer, $serviceOdometer->get($id)], fn ($v) => $v !== null);

            return [$id => (object) [
                'trips' => (int) ($u->trips ?? 0),
                'km' => (int) ($u->km ?? 0),
                'liters' => (float) ($u->liters ?? 0),
                'odometer' => $odometers ? (int) max($odometers) : null,
            ]];
        });
    }

    /**
     * Jarak & BBM per bulan untuk satu kendaraan, N bulan terakhir (bulan kosong tetap muncul).
     *
     * @return array{labels: array<string>, km: array<int>, liters: array<float>}
     */
    public function monthly(int $vehicleId, int $months = 6): array
    {
        $from = now()->startOfMonth()->subMonths($months - 1);

        $rows = DB::select("
            SELECT to_char(m.month, 'YYYY-MM') AS ym,
                   COALESCE(SUM(u.end_odometer - u.start_odometer), 0) AS km,
                   COALESCE(SUM(u.fuel_liters), 0) AS liters
            FROM generate_series(?::date, date_trunc('month', now())::date, interval '1 month') AS m(month)
            LEFT JOIN bookings b ON b.vehicle_id = ?
            LEFT JOIN vehicle_usages u ON u.booking_id = b.id
                AND u.returned_at >= m.month AND u.returned_at < m.month + interval '1 month'
            GROUP BY m.month
            ORDER BY m.month
        ", [$from->toDateString(), $vehicleId]);

        return [
            'labels' => array_map(fn ($r) => Carbon::createFromFormat('Y-m-d', $r->ym.'-01')->locale('id')->translatedFormat('M Y'), $rows),
            'km' => array_map(fn ($r) => (int) $r->km, $rows),
            'liters' => array_map(fn ($r) => round((float) $r->liters, 1), $rows),
        ];
    }
}
