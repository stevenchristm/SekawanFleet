<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $hq = Location::where('type', 'head_office')->first();
        $branch = Location::where('type', 'branch_office')->first();

        $users = [
            ['Admin Pool',        'admin@nikel.test',      'admin',    'Staff Pool Kendaraan', $branch],
            ['Budi Supervisor',   'supervisor@nikel.test', 'approver', 'Supervisor Operasional', $branch],
            ['Sari Manager',      'manager@nikel.test',    'approver', 'Manager Operasional', $hq],
            ['Andi Kepala Site',  'kasite@nikel.test',     'approver', 'Kepala Site', $branch],
            ['Rina GM',           'gm@nikel.test',         'approver', 'General Manager', $hq],
        ];

        foreach ($users as [$name, $email, $role, $position, $loc]) {
            User::create([
                'name' => $name, 'email' => $email, 'password' => 'password', // di-hash otomatis oleh cast
                'role' => $role, 'position' => $position, 'location_id' => $loc->id,
                'email_verified_at' => now(),
            ]);
        }
    }
}
