<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * 1 kantor pusat, 1 kantor cabang, 6 site tambang nikel di beberapa region.
 */
class RegionLocationSeeder extends Seeder
{
    public function run(): void
    {
        $regions = collect(['DKI Jakarta', 'Sulawesi Tengah', 'Sulawesi Tenggara', 'Sulawesi Selatan', 'Maluku Utara'])
            ->mapWithKeys(fn ($name) => [$name => Region::create(['name' => $name])]);

        $locations = [
            ['Kantor Pusat Jakarta', 'head_office', 'DKI Jakarta'],
            ['Kantor Cabang Palu', 'branch_office', 'Sulawesi Tengah'],
            ['Site Morowali', 'mine', 'Sulawesi Tengah'],
            ['Site Kolaka', 'mine', 'Sulawesi Tenggara'],
            ['Site Pomalaa', 'mine', 'Sulawesi Tenggara'],
            ['Site Konawe', 'mine', 'Sulawesi Tenggara'],
            ['Site Halmahera', 'mine', 'Maluku Utara'],
            ['Site Sorowako', 'mine', 'Sulawesi Selatan'],
        ];

        foreach ($locations as [$name, $type, $region]) {
            Location::create(['region_id' => $regions[$region]->id, 'name' => $name, 'type' => $type]);
        }
    }
}
