<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Booking, BookingApproval, Driver, User, Vehicle, VehicleUsage};
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $admin     = User::where('role', 'admin')->first();
        $approvers = User::where('role', 'approver')->get();
        $vehicles  = Vehicle::all();
        $drivers   = Driver::all();
        $dests     = ['Tambang Site 1', 'Tambang Site 3', 'Pelabuhan Morowali', 'Bandara Kasiguncu', 'Kantor Cabang'];
        $seq = [];

        $make = function (Carbon $start, string $status, array $levels) use ($admin, $dests, &$seq, &$vehicles, &$drivers, $approvers) {
            $month = $start->format('Ym');
            $seq[$month] = ($seq[$month] ?? 0) + 1;

            [$l1, $l2] = $approvers->random(2)->values();

            $booking = Booking::create([
                'code' => sprintf('BK-%s-%04d', $month, $seq[$month]),
                'vehicle_id' => $vehicles->shift()->id,
                'driver_id'  => $drivers->shift()->id,
                'created_by' => $admin->id,
                'requester_name' => fake('id_ID')->name(),
                'purpose' => fake()->randomElement(['Inspeksi site', 'Angkut sparepart', 'Antar tamu', 'Survey lokasi']),
                'destination' => fake()->randomElement($dests),
                'start_at' => $start, 'end_at' => $start->copy()->addHours(rand(3, 9)),
                'status' => $status,
                'current_level' => $levels[1] === 'waiting' ? 1 : 2,
            ]);

            foreach ([1 => $l1, 2 => $l2] as $lvl => $user) {
                $st = $levels[$lvl - 1];
                BookingApproval::create([
                    'booking_id' => $booking->id, 'approver_id' => $user->id, 'level' => $lvl,
                    'status' => $st,
                    'note' => $st === 'rejected' ? 'Jadwal kendaraan dialihkan ke kebutuhan prioritas' : null,
                    'acted_at' => in_array($st, ['approved', 'rejected']) ? $start->copy()->subDay() : null,
                ]);
            }

            if ($status === 'completed') {
                $odo = rand(10000, 90000);
                VehicleUsage::create([
                    'booking_id' => $booking->id, 'start_odometer' => $odo,
                    'end_odometer' => $odo + rand(20, 250), 'fuel_liters' => rand(10, 60),
                    'returned_at' => $booking->end_at,
                ]);
            }
        };

        $allVehicles = Vehicle::all();
        $allDrivers  = Driver::all();

        for ($d = 180; $d >= 1; $d -= rand(2, 4)) {
            $vehicles = $allVehicles->shuffle();
            $drivers  = $allDrivers->shuffle();
            $day = now()->subDays($d)->setTime(rand(7, 10), 0);

            foreach (range(1, rand(1, 3)) as $_) {
                match (true) {
                    rand(1, 10) <= 8 => $make($day, 'completed', ['approved', 'approved']),
                    rand(0, 1) === 0 => $make($day, 'rejected',  ['rejected', 'skipped']),
                    default          => $make($day, 'rejected',  ['approved', 'rejected']),
                };
            }
        }

        foreach (range(1, 4) as $i) {
            $vehicles = $allVehicles->shuffle();
            $drivers  = $allDrivers->shuffle();
            $day = now()->addDays($i)->setTime(8, 0);

            $make($day, 'pending', ['pending', 'waiting']);
            $make($day, 'pending', ['approved', 'pending']);
        }
    }
}