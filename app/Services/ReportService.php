<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Satu sumber query untuk tampilan laporan dan file Excel, supaya isinya selalu sama.
 *
 * @phpstan-type Filters array{from:string, to:string, status?:?string, type?:?string, location_id?:?int}
 */
class ReportService
{
    /** @param Filters $filters */
    public function query(array $filters): Builder
    {
        return $this->filtered($filters)
            ->with(['vehicle.location', 'vehicle.rentalCompany', 'driver', 'approvals.approver', 'usage'])
            ->orderBy('start_at')
            ->orderBy('id'); // urutan unik: wajib untuk export per-chunk
    }

    /**
     * Ringkasan angka untuk periode & filter yang sama.
     *
     * @param  Filters  $filters
     */
    public function summary(array $filters): array
    {
        $byStatus = $this->filtered($filters)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $usage = $this->filtered($filters)
            ->whereIn('bookings.status', [BookingStatus::Approved, BookingStatus::Completed])
            ->leftJoin('vehicle_usages', 'vehicle_usages.booking_id', '=', 'bookings.id')
            ->selectRaw('
                COALESCE(SUM(EXTRACT(EPOCH FROM (bookings.end_at - bookings.start_at)) / 3600), 0) AS hours,
                COALESCE(SUM(vehicle_usages.end_odometer - vehicle_usages.start_odometer), 0) AS km,
                COALESCE(SUM(vehicle_usages.fuel_liters), 0) AS liters
            ')
            ->toBase()
            ->first();

        return [
            'total' => (int) $byStatus->sum(),
            'by_status' => collect(BookingStatus::cases())
                ->mapWithKeys(fn ($s) => [$s->value => (int) ($byStatus[$s->value] ?? 0)])
                ->all(),
            'hours' => round((float) $usage->hours, 1),
            'km' => (int) $usage->km,
            'liters' => round((float) $usage->liters, 2),
        ];
    }

    /**
     * Rekap per kendaraan (hanya booking yang terpakai: approved/completed).
     *
     * @param  Filters  $filters
     * @return array<int, object{plate_number:string, brand_model:string, type:string, ownership:string, trips:int, hours:float, km:int, liters:float}>
     */
    public function perVehicle(array $filters): array
    {
        return $this->filtered($filters)
            ->whereIn('bookings.status', [BookingStatus::Approved, BookingStatus::Completed])
            ->join('vehicles', 'vehicles.id', '=', 'bookings.vehicle_id')
            ->leftJoin('vehicle_usages', 'vehicle_usages.booking_id', '=', 'bookings.id')
            ->groupBy('vehicles.id', 'vehicles.plate_number', 'vehicles.brand_model', 'vehicles.type', 'vehicles.ownership')
            ->orderByDesc('trips')
            ->orderBy('vehicles.plate_number')
            ->selectRaw('
                vehicles.plate_number, vehicles.brand_model, vehicles.type, vehicles.ownership,
                COUNT(bookings.id) AS trips,
                ROUND(SUM(EXTRACT(EPOCH FROM (bookings.end_at - bookings.start_at)) / 3600)::numeric, 1) AS hours,
                COALESCE(SUM(vehicle_usages.end_odometer - vehicle_usages.start_odometer), 0) AS km,
                COALESCE(SUM(vehicle_usages.fuel_liters), 0) AS liters
            ')
            ->toBase()
            ->get()
            ->all();
    }

    /** @param Filters $filters */
    private function filtered(array $filters): Builder
    {
        $from = Carbon::parse($filters['from'])->startOfDay();
        $to = Carbon::parse($filters['to'])->addDay()->startOfDay();

        return Booking::query()
            ->where('bookings.start_at', '>=', $from)
            ->where('bookings.start_at', '<', $to)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('bookings.status', $s))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->whereHas('vehicle', fn ($v) => $v->where('type', $t)))
            ->when($filters['location_id'] ?? null, fn ($q, $l) => $q->whereHas('vehicle', fn ($v) => $v->where('location_id', $l)));
    }
}
