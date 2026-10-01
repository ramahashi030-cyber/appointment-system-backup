<?php

use App\Models\PatientNotification;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Only `patients`, `medical_records` and `sessions` have migrations — the
 * legacy tables these screens read (appointments, prescriptions,
 * notifications, services*) only exist in MySQL, so build them on sqlite.
 */
function portalTables(): void
{
    if (! Schema::hasTable('appointments')) {
        Schema::create('appointments', function ($table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->integer('service_id')->nullable();
            $table->string('complaint')->nullable();
            $table->string('consultation_reason', 100)->nullable();
            $table->json('symptoms')->nullable();
            $table->text('complaint_details')->nullable();
            $table->date('date')->nullable();
            $table->string('time_slot')->nullable();
            $table->string('status')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->char('qr_code_token', 64)->nullable()->unique();
            $table->boolean('reminder_sent')->nullable();
            $table->string('mode')->nullable();
            $table->string('meeting_link')->nullable();
            $table->boolean('room_opened')->nullable();
            $table->integer('opened_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('prescriptions')) {
        Schema::create('prescriptions', function ($table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function ($table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('services_tele')) {
        Schema::create('services_tele', function ($table) {
            $table->increments('id');
            $table->string('service_name')->nullable();
            $table->string('availability_day')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('services')) {
        Schema::create('services', function ($table) {
            $table->id();
            $table->string('service_name')->nullable();
            $table->string('availability_day')->nullable();
            $table->string('homis_code')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}

test('the four new portal screens send a guest to the patient login', function () {
    foreach (['/patients/notifications', '/patients/prescriptions', '/patients/procedures', '/patients/profile'] as $path) {
        $this->get($path)->assertRedirect(route('auth.login'));
    }
});

test('the notifications screen lists upcoming appointments and stored notices', function () {
    portalTables();

    $patient = makePatient();

    DB::table('services_tele')->insert(['service_name' => 'General Consultation']);
    $serviceId = DB::table('services_tele')->value('id');

    DB::table('appointments')->insert([
        'patient_id' => $patient->id,
        'service_id' => $serviceId,
        'date' => now()->addDays(3)->toDateString(),
        'time_slot' => '08:00 - 10:00',
        'status' => 'Booked',
        'mode' => 'TELE',
        'meeting_link' => 'https://meet.jit.si/room',
    ]);

    PatientNotification::create([
        'patient_id' => $patient->id,
        'message' => 'Your laboratory results are ready.',
        'is_read' => false,
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->get('/patients/notifications')
        ->assertOk()
        ->assertSee('Upcoming appointments')
        ->assertSee('General Consultation')
        ->assertSee('Your laboratory results are ready.')
        ->assertSee('New');
});

test('the notifications screen hides other patients and cancelled appointments', function () {
    portalTables();

    $patient = makePatient();
    $other = makePatient(['username' => 'other', 'contact_number' => '09170000002']);

    DB::table('appointments')->insert([
        [
            'patient_id' => $patient->id,
            'date' => now()->addDay()->toDateString(),
            'time_slot' => '10:00 - 12:00',
            'status' => 'Cancelled',
            'mode' => 'TELE',
        ],
        [
            'patient_id' => $other->id,
            'date' => now()->addDay()->toDateString(),
            'time_slot' => '12:00 - 14:00',
            'status' => 'Booked',
            'mode' => 'TELE',
        ],
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->get('/patients/notifications')
        ->assertOk()
        ->assertSee('You have no upcoming appointments.')
        ->assertDontSee('12:00 - 14:00');
});

test('the prescriptions screen shows only the signed-in patient rows', function () {
    portalTables();

    $patient = makePatient();
    $other = makePatient(['username' => 'other', 'contact_number' => '09170000002']);

    Prescription::create([
        'patient_id' => $patient->id,
        'doctor_name' => 'Dra. Santos',
        'notes' => 'Amoxicillin 500mg, 3x a day',
    ]);

    Prescription::create([
        'patient_id' => $other->id,
        'doctor_name' => 'Dr. Reyes',
        'notes' => 'Secret medication for someone else',
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->get('/patients/prescriptions')
        ->assertOk()
        ->assertSee('Dra. Santos')
        ->assertSee('Amoxicillin 500mg, 3x a day')
        ->assertDontSee('Secret medication for someone else');
});

test('the prescriptions screen shows an empty state when there are none', function () {
    portalTables();

    $patient = makePatient();

    $this->withSession(['patient_id' => $patient->id])
        ->get('/patients/prescriptions')
        ->assertOk()
        ->assertSee('You have no prescriptions yet.');
});

test('the procedures screen renders its placeholder', function () {
    $patient = makePatient();

    $this->withSession(['patient_id' => $patient->id])
        ->get('/patients/procedures')
        ->assertOk()
        ->assertSee('My Procedures')
        ->assertSee('No procedures to show yet.');
});

test('the profile screen shows the signed-in patient details', function () {
    $patient = makePatient(['email' => 'juan@example.com']);

    $this->withSession(['patient_id' => $patient->id])
        ->get('/patients/profile')
        ->assertOk()
        ->assertSee('JUAN S DELA CRUZ')
        ->assertSee('juan@example.com')
        ->assertSee(route('patient.profile.update'), false);
});

test('a patient can update their own profile', function () {
    $patient = makePatient();

    $this->withSession(['patient_id' => $patient->id])
        ->from('/patients/profile')
        ->put('/patients/profile', [
            'first_name' => 'JUANA',
            'middlename' => 'S',
            'last_name' => 'DELA CRUZ',
            'gender' => 'Female',
            'dob' => '1990-01-15',
            'contact_number' => '09179998888',
            'email' => 'juana@example.com',
            'address' => '123 Sampaguita St',
            'hospital_number' => 'HN-777',
        ])
        ->assertRedirect(route('patient.profile'))
        ->assertSessionHas('success', 'Your profile has been updated.');

    $patient->refresh();

    expect($patient->first_name)->toBe('JUANA')
        ->and($patient->gender)->toBe('Female')
        ->and($patient->contact_number)->toBe('09179998888')
        ->and($patient->hospital_number)->toBe('HN-777');
});

test('a profile update with a missing required field is rejected before the write', function () {
    $patient = makePatient();

    $this->withSession(['patient_id' => $patient->id])
        ->from('/patients/profile')
        ->put('/patients/profile', [
            'first_name' => '',
            'last_name' => 'DELA CRUZ',
            'gender' => 'Male',
            'contact_number' => '09171234567',
        ])
        ->assertRedirect('/patients/profile')
        ->assertSessionHasErrors('first_name');

    expect($patient->refresh()->first_name)->toBe('JUAN');
});

test('the navbar avatar uses the patient profile picture', function () {
    $patient = makePatient(['profile_pic' => 'uploads/profile-pics/abc123.jpg']);

    $this->withSession(['patient_id' => $patient->id])
        ->get('/patients/profile')
        ->assertOk()
        ->assertSee('<img class="nav-avatar"', false)
        ->assertSee('uploads/profile-pics/abc123.jpg', false);
});

test('the bar keeps three links and the rest lives behind the avatar dropdown', function () {
    $patient = makePatient();

    $response = $this->withSession(['patient_id' => $patient->id])->get('/patients/profile');

    $response->assertOk();

    // On the bar…
    $response->assertSee('href="/telemed"', false)->assertSee('Home')
        ->assertSee('href="/telemed/my-appointments"', false)->assertSee('Appointment')
        ->assertSee('href="/patients/notifications"', false)->assertSee('Notification');

    // …and inside the profile-picture dropdown.
    $response->assertSee('id="accountMenuToggle"', false)
        ->assertSee('href="/records"', false)->assertSee('Medical Record')
        ->assertSee('href="/patients/prescriptions"', false)->assertSee('Prescription')
        ->assertSee('href="/patients/procedures"', false)->assertSee('Procedure')
        ->assertSee('href="/patients/profile"', false)->assertSee('Profile');

    // Booking is not a top-level navbar item; the dropdown holds the account
    // destinations.
    $response->assertDontSee('href="/telemed/book"', false);
});
