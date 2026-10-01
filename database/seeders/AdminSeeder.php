<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the administrator account used by the admin panel.
     */
    public function run(): void
    {
        Admin::query()->updateOrCreate(
            ['username' => 'renz'],
            [
                'firstname' => 'Reniel',
                'lastname' => 'Montejo',
                'password' => Hash::make('Password@123'),
                'email' => 'renzmontejo17@gmail.com',
                'contact_no' => '09293470606',
                'role' => 'admin',
            ],
        );

        Admin::query()->updateOrCreate(
            ['username' => 'triager'],
            [
                'firstname' => 'Triage',
                'lastname' => 'Staff',
                'password' => Hash::make('Password@123'),
                'email' => 'triager@qmmc.local',
                'contact_no' => '09293470607',
                'role' => 'triager',
            ],
        );
    }
}
