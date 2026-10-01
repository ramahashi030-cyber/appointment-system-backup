<?php

use App\Models\Staff;
use App\Support\StaffDoctorSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('existing doctor accounts are copied into staff before the doctor table is removed', function () {
    Schema::create('doctors', function ($table): void {
        $table->id();
        $table->string('first_name');
        $table->string('middlename')->nullable();
        $table->string('last_name');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('specialty')->default('Family Medicine');
        $table->string('status')->default('Active')->index();
        $table->string('profile_pic')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    $password = Hash::make('migration-password');

    DB::table('doctors')->insert([
        'first_name' => 'Maria',
        'middlename' => 'Santos',
        'last_name' => 'Reyes',
        'email' => 'maria.reyes@qmmc.local',
        'password' => $password,
        'specialty' => 'Pediatrics',
        'status' => 'Active',
        'profile_pic' => 'maria.jpg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    StaffDoctorSchema::migrateDoctorsIntoStaff();

    $staff = Staff::query()->where('email', 'maria.reyes@qmmc.local')->firstOrFail();

    expect(Schema::hasTable('doctors'))->toBeFalse()
        ->and(Schema::hasColumn('staff', 'legacy_doctor_id'))->toBeTrue()
        ->and($staff->legacy_doctor_id)->toBeNull()
        ->and($staff->is_doctor)->toBeTrue()
        ->and($staff->is_active)->toBeTrue()
        ->and($staff->contactno)->toBe('N/A')
        ->and($staff->site)->toBe('TELE')
        ->and($staff->specialty())->toBe('Family Medicine')
        ->and($staff->profile_pic)->toBe('maria.jpg')
        ->and($staff->full_name)->toBe('Maria Santos Reyes')
        ->and(Hash::check('migration-password', $staff->password))->toBeTrue();
});
