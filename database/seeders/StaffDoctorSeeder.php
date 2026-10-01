<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Support\StaffDoctorSchema;
use Illuminate\Database\Seeder;

class StaffDoctorSeeder extends Seeder
{
    public function run(): void
    {
        StaffDoctorSchema::migrateDoctorsIntoStaff();

        Staff::query()->updateOrCreate(
            ['email' => 'aldrin.gwapo@qmmc.local'],
            [
                'username' => 'aldrin.gwapo',
                'contactno' => 'N/A',
                'FirstName' => 'Aldrin',
                'MiddleName' => null,
                'LastName' => 'Gwapo',
                'password' => '$2y$12$LHCBRJmEZGwpl1o778vRxe2LQOqnr3ijrzY9ntRNztXOiTx4XHPum',
                'is_verified' => true,
                'is_active' => true,
                'is_doctor' => true,
                'site' => 'TELE',
            ],
        );
    }
}
