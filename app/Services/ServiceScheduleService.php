<?php

namespace App\Services;

use App\Enums\ServiceStatus;
use App\Models\Booking;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUsage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jadwal servis kendaraan: menjadwalkan, menyelesaikan, membatalkan.
 * Hari servis terjadwal memblokir pemesanan kendaraan tersebut (lihat BookingService::ensureNoConflict),
 * dan sebaliknya servis tidak bisa dijadwalkan di hari kendaraan sudah dipesan.
 */
class ServiceScheduleService
{
    /**
     * @param  array{type:string, scheduled_date:string, odometer_due:?int, description:?string, workshop:?string}  $data
     */
    public function schedule(Vehicle $vehicle, array $data, User $user): ServiceSchedule
    {
        $date = Carbon::parse($data['scheduled_date'])->startOfDay();

        try {
            return DB::transaction(function () use ($vehicle, $data, $user, $date) {
                // Kunci baris kendaraan: pembuatan booking juga mengunci baris ini,
                // jadi cek bentrok booking vs servis tidak bisa saling mendahului.
                Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();

                $clash = Booking::query()
                    ->whereIn('status', BookingService::BLOCKING_STATUSES)
                    ->where('vehicle_id', $vehicle->id)
                    ->where('start_at', '<', $date->copy()->addDay())
                    ->where('end_at', '>', $date)
                    ->orderBy('start_at')
                    ->first();

                if ($clash) {
                    throw $this->error('scheduled_date', "Kendaraan sudah dipesan ({$clash->code}) pada tanggal tersebut. "
                        .'Pilih tanggal lain atau batalkan pemesanannya lebih dulu.');
                }

                if (ServiceSchedule::scheduled()->where('vehicle_id', $vehicle->id)->whereDate('scheduled_date', $date)->exists()) {
                    throw $this->error('scheduled_date', 'Sudah ada servis terjadwal untuk kendaraan ini pada tanggal tersebut.');
                }

                $service = ServiceSchedule::create([
                    'vehicle_id' => $vehicle->id,
                    'type' => $data['type'],
                    'scheduled_date' => $date,
                    'odometer_due' => $data['odometer_due'] ?? null,
                    'description' => $data['description'] ?? null,
                    'workshop' => $data['workshop'] ?? null,
                    'status' => ServiceStatus::Scheduled,
                    'created_by' => $user->id,
                ]);

                activity('service')->performedOn($service)->causedBy($user)
                    ->withProperties(array_filter([
                        'vehicle' => $vehicle->plate_number,
                        'type' => $service->type->value,
                        'scheduled_date' => $date->toDateString(),
                        'odometer_due' => $service->odometer_due,
                    ], fn ($v) => $v !== null))
                    ->log("{$service->type->label()} {$vehicle->plate_number} dijadwalkan {$date->format('d/m/Y')}");

                return $service;
            });
        } catch (QueryException $e) {
            // 23505 = unique_violation pada uq_service_vehicle_date (request bersamaan)
            if ($e->getCode() === '23505') {
                throw $this->error('scheduled_date', 'Sudah ada servis terjadwal untuk kendaraan ini pada tanggal tersebut.');
            }

            throw $e;
        }
    }

    /**
     * @param  array{completed_date:string, odometer_at_service:int, cost:?float, workshop:?string, notes:?string}  $data
     */
    public function complete(ServiceSchedule $service, array $data, User $user): ServiceSchedule
    {
        return DB::transaction(function () use ($service, $data, $user) {
            $service = ServiceSchedule::whereKey($service->id)->lockForUpdate()->firstOrFail();

            if ($service->status !== ServiceStatus::Scheduled) {
                throw $this->error('service', 'Servis ini sudah '.strtolower($service->status->label()).'.', 'default');
            }

            $service->update([
                'status' => ServiceStatus::Completed,
                'completed_date' => Carbon::parse($data['completed_date']),
                'odometer_at_service' => $data['odometer_at_service'],
                'cost' => $data['cost'] ?? null,
                'workshop' => $data['workshop'] ?? $service->workshop,
                'notes' => $data['notes'] ?? null,
                'completed_by' => $user->id,
            ]);

            $plate = $service->vehicle->plate_number;

            activity('service')->performedOn($service)->causedBy($user)
                ->withProperties(array_filter([
                    'vehicle' => $plate,
                    'completed_date' => $service->completed_date->toDateString(),
                    'odometer_at_service' => $service->odometer_at_service,
                    'cost' => $service->cost,
                    'workshop' => $service->workshop,
                ], fn ($v) => $v !== null))
                ->log("{$service->type->label()} {$plate} selesai dikerjakan");

            return $service;
        });
    }

    public function cancel(ServiceSchedule $service, User $user): void
    {
        DB::transaction(function () use ($service, $user) {
            $service = ServiceSchedule::whereKey($service->id)->lockForUpdate()->firstOrFail();

            if ($service->status !== ServiceStatus::Scheduled) {
                throw $this->error('service', 'Hanya servis yang masih terjadwal yang bisa dibatalkan.', 'default');
            }

            $service->update(['status' => ServiceStatus::Cancelled]);

            activity('service')->performedOn($service)->causedBy($user)
                ->log("{$service->type->label()} {$service->vehicle->plate_number} tanggal {$service->scheduled_date->format('d/m/Y')} dibatalkan");
        });
    }

    /**
     * Odometer terbaru yang diketahui: dari pengembalian kendaraan atau dari servis yang selesai.
     */
    public function currentOdometer(int $vehicleId): ?int
    {
        $fromUsage = VehicleUsage::query()
            ->whereHas('booking', fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->max('end_odometer');

        $fromService = ServiceSchedule::query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', ServiceStatus::Completed)
            ->max('odometer_at_service');

        $values = array_filter([$fromUsage, $fromService], fn ($v) => $v !== null);

        return $values ? (int) max($values) : null;
    }

    private function error(string $field, string $message, string $bag = 'schedule'): ValidationException
    {
        return ValidationException::withMessages([$field => $message])->errorBag($bag);
    }
}
