<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Region, Location};

class RegionLocationSeeder extends Seeder
{
    public function run(): void
    {
        $jakarta = Region::create(['name' => 'DKI Jakarta']);
        $sulteng = Region::create(['name' => 'Sulawesi Tengah']);

        Location::create(['region_id' => $jakarta->id, 'name' => 'Kantor Pusat Jakarta', 'type' => 'head_office']);
        Location::create(['region_id' => $sulteng->id, 'name' => 'Kantor Cabang Morowali', 'type' => 'branch_office']);

        foreach (range(1, 6) as $i) {
            Location::create(['region_id' => $sulteng->id, 'name' => "Tambang Site {$i}", 'type' => 'mine']);
        }
    }
}