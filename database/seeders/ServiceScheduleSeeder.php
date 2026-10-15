<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUsage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data demo jadwal servis: riwayat servis selesai ~6 bulan terakhir per kendaraan
 * (odometer mengikuti riwayat pemakaian), ditambah satu servis terjadwal per kendaraan
 * dengan variasi terlambat / segera / masih lama supaya semua penanda tampil.
 * Dijalankan setelah BookingSeeder; tanggal servis mendatang tidak bentrok dengan pemesanan aktif.
 */
class ServiceScheduleSeeder extends Seeder
{
    private const WORKSHOPS = [
        'Bengkel Pool Morowali', 'Auto2000 Palu', 'Bengkel Site Pomalaa',
        'Hino Service Kendari', 'Bengkel Mitra Kolaka',
    ];

    private const ROUTINE_NOTES = [
        'Ganti oli mesin & filter oli, cek rem dan tekanan ban',
        'Ganti oli, filter udara, dan filter solar',
        'Servis berkala: oli, gemuk, setel rem, cek aki',
    ];

    /** Selisih hari servis terjadwal dari hari ini, per urutan kendaraan. */
    private const UPCOMING_OFFSETS = [-5, -2, 1, 3, 6, 10, 16, 24, 35, 50];

    public function run(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        foreach (Vehicle::orderBy('id')->get() as $i => $vehicle) {
            $usages = VehicleUsage::query()
                ->whereHas('booking', fn ($q) => $q->where('vehicle_id', $vehicle->id))
                ->orderBy('returned_at')
                ->get(['start_odometer', 'end_odometer', 'returned_at']);

            // Riwayat: servis selesai setiap ~45-75 hari.
            for ($date = now()->subDays(rand(170, 185)); $date->lt(now()->subDays(7)); $date->addDays(rand(45, 75))) {
                $roll = rand(1, 100);
                $type = $roll <= 70 ? 'routine' : ($roll <= 88 ? 'repair' : 'inspection');
                $odometer = $this->odometerAt($usages, $date);
                $created = $date->copy()->subDays(rand(5, 12))->setTime(9, 0);

                ServiceSchedule::create([
                    'vehicle_id' => $vehicle->id,
                    'type' => $type,
                    'scheduled_date' => $date->toDateString(),
                    'odometer_due' => $type === 'routine' ? $this->nextMultiple($odometer) : null,
                    'description' => $type === 'repair' ? 'Perbaikan sesuai catatan pengembalian' : null,
                    'workshop' => self::WORKSHOPS[array_rand(self::WORKSHOPS)],
                    'status' => 'completed',
                    'completed_date' => $date->toDateString(),
                    'odometer_at_service' => $odometer,
                    'cost' => match ($type) {
                        'routine' => rand(6, 25) * 100000,
                        'repair' => rand(15, 80) * 100000,
                        'inspection' => rand(2, 6) * 100000,
                    },
                    'notes' => match ($type) {
                        'routine' => self::ROUTINE_NOTES[array_rand(self::ROUTINE_NOTES)],
                        'repair' => collect(['Ganti kampas rem depan', 'Perbaikan AC', 'Ganti ban belakang (2 pcs)', 'Perbaikan sistem kelistrikan'])->random(),
                        'inspection' => 'Uji KIR berkala, lulus',
                    },
                    'created_by' => $admin->id,
                    'completed_by' => $admin->id,
                    'created_at' => $created,
                    'updated_at' => $date->copy()->setTime(15, 0),
                ]);
            }

            // Mendatang (atau terlambat): satu servis terjadwal per kendaraan.
            $offset = self::UPCOMING_OFFSETS[$i % count(self::UPCOMING_OFFSETS)];
            $date = $this->freeDay($vehicle, today()->addDays($offset), $offset < 0 ? -1 : 1);
            $current = $this->odometerAt($usages, now());
            $type = $i % 4 === 3 ? 'inspection' : 'routine';

            ServiceSchedule::create([
                'vehicle_id' => $vehicle->id,
                'type' => $type,
                'scheduled_date' => $date->toDateString(),
                'odometer_due' => $type === 'routine' ? $this->nextMultiple($current) : null,
                'description' => $type === 'routine' ? self::ROUTINE_NOTES[$i % count(self::ROUTINE_NOTES)] : 'Uji KIR tahunan',
                'workshop' => self::WORKSHOPS[$i % count(self::WORKSHOPS)],
                'status' => 'scheduled',
                'created_by' => $admin->id,
                'created_at' => now()->subDays(rand(1, 10))->setTime(rand(8, 15), rand(0, 59)),
                'updated_at' => now(),
            ]);
        }
    }

    /** Odometer kendaraan pada tanggal tertentu, diperkirakan dari riwayat pemakaian. */
    private function odometerAt($usages, Carbon $date): int
    {
        $before = $usages->filter(fn ($u) => $u->returned_at && $u->returned_at->lte($date))->last();
        if ($before) {
            return (int) $before->end_odometer;
        }

        $first = $usages->first();

        return $first ? max(0, $first->start_odometer - rand(50, 300)) : rand(15000, 60000);
    }

    /** Kelipatan 5.000 km berikutnya (interval servis berkala). */
    private function nextMultiple(int $odometer): int
    {
        return intdiv($odometer, 5000) * 5000 + 5000;
    }

    /** Geser tanggal sampai tidak bentrok dengan pemesanan aktif kendaraan tersebut. */
    private function freeDay(Vehicle $vehicle, Carbon $date, int $step): Carbon
    {
        while (Booking::where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_at', '<', $date->copy()->addDay())
            ->where('end_at', '>', $date)
            ->exists()) {
            $date->addDays($step);
        }

        return $date;
    }
}
