<?php

use App\Models\Admin;
use App\Models\Appointment;
use App\Models\PatientRequest;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function adminDashboardTables(): void
{
    if (! Schema::hasTable('staff')) {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('appointments')) {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('service_id');
            $table->string('complaint')->nullable();
            $table->string('consultation_reason', 100)->nullable();
            $table->json('symptoms')->nullable();
            $table->text('complaint_details')->nullable();
            $table->date('date');
            $table->string('time_slot');
            $table->string('status')->default('Booked');
            $table->string('mode')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->char('qr_code_token', 64)->nullable()->unique();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services')) {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services_tele')) {
        Schema::create('services_tele', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('request')) {
        Schema::create('request', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->text('Complaints')->nullable();
            $table->string('status')->default('Pending');
            $table->string('consult_type')->nullable();
            $table->timestamps();
        });
    }
}

function adminDashboardAdmin(array $overrides = []): Admin
{
    return Admin::create(array_merge([
        'firstname' => 'Reniel',
        'lastname' => 'Montejo',
        'username' => 'renzel-admin',
        'password' => 'Password@123',
        'email' => 'renzel-admin@example.com',
        'contact_no' => '09293470606',
    ], $overrides));
}

test('the admin dashboard route renders its blade for an administrator', function () {
    $admin = adminDashboardAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin Dashboard')
        ->assertSee('Reniel Montejo')
        ->assertSee('Total Registered Patients')
        ->assertSee('Administrative Dashboard')
        ->assertSee('Secure')
        ->assertSee('Efficient')
        ->assertDontSee('Convenient')
        ->assertSee('QMMC Admin Portal')
        ->assertSee('Doctors and Patient Management')
        ->assertSee('Dekalidad na Serbisyo, Alagang QMMC.')
        ->assertDontSee('Overview of patients, staff, and appointments across QMMC Patient Portal.')
        ->assertDontSee('Appointment Statistics — This Week')
        ->assertDontSee('Recent Consultations')
        ->assertDontSee('Recent Registrations')
        ->assertDontSee('Search patients, doctors, appointments...');
});

test('the dashboard shows live system metrics and pending requests', function () {
    adminDashboardTables();
    $admin = adminDashboardAdmin();

    $patient = makePatient([
        'first_name' => 'JOSE',
        'middlename' => 'A',
        'last_name' => 'DELA CRUZ',
        'contact_number' => '09171234567',
        'username' => 'jose',
    ]);

    Staff::create([
        'username' => 'admin',
        'password' => 'secret123',
        'is_verified' => true,
    ]);

    $faceService = Service::create(['service_name' => 'FAMILY MEDICINE']);
    $teleService = ServiceTele::create(['service_name' => 'FAMILY MEDICINE']);

    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $faceService->id,
        'date' => today(),
        'time_slot' => '08:00 - 10:00',
        'status' => 'Booked',
        'mode' => 'FACE',
    ]);
    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $teleService->id,
        'date' => today()->addDay(),
        'time_slot' => '10:00 - 12:00',
        'status' => 'Booked',
        'mode' => 'TELE',
    ]);
    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $teleService->id,
        'date' => today()->subDay(),
        'time_slot' => '12:00 - 02:00',
        'status' => 'Completed',
        'mode' => 'TELE',
    ]);
    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $faceService->id,
        'date' => today()->subDays(2),
        'time_slot' => '02:00 - 04:00',
        'status' => 'Cancelled',
        'mode' => 'FACE',
    ]);

    PatientRequest::create([
        'patient_id' => $patient->id,
        'Complaints' => 'Follow-up',
        'status' => 'Pending',
        'consult_type' => 'Family Medicine',
    ]);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('JOSE A DELA CRUZ')
        ->assertSee('Pending Appointment Requests')
        ->assertViewHas('statCards', function (array $cards): bool {
            $values = collect($cards)->keyBy('label');

            return $values['Total Registered Patients']['value'] === 1
                && $values['Doctors / Medical Staff']['value'] === 1
                && $values['Today\'s Appointments']['value'] === 1
                && $values['Upcoming Appointments']['value'] === 2
                && $values['Completed Appointments']['value'] === 1
                && $values['Cancelled Appointments']['value'] === 1
                && $values['Telemedicine Appointments']['value'] === 2
                && $values['Family Medicine Appointments']['value'] === 4;
        });
});

test('the admin module quick-access route renders for an administrator', function () {
    $admin = adminDashboardAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients'))
        ->assertOk()
        ->assertSee('Patient Management');
});

test('the admin dashboard requires an administrator session', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('auth.login'));
});

test('a legacy staff session cannot access the admin panel', function () {
    $this->withSession([
        'staff_id' => 1,
        'user_type' => 'staff',
    ])->get(route('admin.dashboard'))
        ->assertRedirect(route('auth.login'));
});
