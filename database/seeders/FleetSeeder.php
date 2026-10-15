<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Location;
use App\Models\RentalCompany;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    public function run(): void
    {
        $rentals = collect([
            RentalCompany::create(['name' => 'PT Sulawesi Trans Rental', 'phone' => '0411-555001']),
            RentalCompany::create(['name' => 'CV Morowali Armada',       'phone' => '0465-555002']),
        ]);

        $locations = Location::all();

        $vehicles = [
            ['DN 1001 AB', 'Toyota Hilux',        'passenger', 'owned',  5],
            ['DN 1002 AB', 'Toyota Hilux',        'passenger', 'owned',  5],
            ['DN 1003 AB', 'Mitsubishi Triton',   'passenger', 'owned',  5],
            ['DN 1004 AB', 'Toyota HiAce',        'passenger', 'rented', 15],
            ['DN 1005 AB', 'Isuzu Elf',           'passenger', 'rented', 20],
            ['DN 8001 CD', 'Hino Dutro',          'cargo',     'owned',  4],
            ['DN 8002 CD', 'Mitsubishi Fuso',     'cargo',     'owned',  8],
            ['DN 8003 CD', 'Isuzu Giga',          'cargo',     'rented', 20],
            ['DN 8004 CD', 'Hino Ranger',         'cargo',     'rented', 10],
            ['B 1234 KJT', 'Toyota Innova Zenix', 'passenger', 'owned',  7],
        ];

        foreach ($vehicles as $i => [$plate, $model, $type, $own, $cap]) {
            Vehicle::create([
                'location_id' => $locations[$i % $locations->count()]->id,
                'rental_company_id' => $own === 'rented' ? $rentals->random()->id : null,
                'plate_number' => $plate, 'brand_model' => $model, 'type' => $type,
                'ownership' => $own, 'capacity' => $cap, 'status' => 'available',
            ]);
        }

        $names = ['Joko', 'Rahmat', 'Yusuf', 'Hendra', 'Agus', 'Wahyu', 'Fajar'];
        foreach ($names as $i => $name) {
            Driver::create([
                'location_id' => $locations[$i % $locations->count()]->id,
                'name' => $name, 'phone' => '0812'.str_pad($i + 1, 8, '0', STR_PAD_LEFT),
                'license_number' => 'SIM-'.(720000 + $i), 'is_active' => true,
            ]);
        }
    }
}
