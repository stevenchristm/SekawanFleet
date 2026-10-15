<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        activity()->disableLogging(); // data dummy tidak perlu masuk log

        $this->call([
            RegionLocationSeeder::class,
            UserSeeder::class,
            FleetSeeder::class,
            BookingSeeder::class,
        ]);

        activity()->enableLogging();

        // Contoh isi log aktivitas (dibangun dari data booking di atas).
        $this->call(ActivityLogSeeder::class);
    }
}
