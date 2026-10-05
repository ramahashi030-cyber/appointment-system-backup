<?php

use App\Models\Admin;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function patientManagementTables(): void
{
    if (! Schema::hasTable('patients')) {
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('middlename')->nullable();
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('contact_number', 11)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('username', 50)->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('status')->default('Pending');
            $table->string('hospital_number', 20)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('staff')) {
        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 50)->nullable();
            $table->string('password')->nullable();
            $table->string('LastName', 100)->nullable();
            $table->string('FirstName', 100)->nullable();
            $table->string('MiddleName', 100)->nullable();
            $table->string('contactno', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('availability')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services')) {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('service_name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services_tele')) {
        Schema::create('services_tele', function (Blueprint $table): void {
            $table->id();
            $table->string('service_name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('appointments')) {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('complaint')->nullable();
            $table->string('consultation_reason')->nullable();
            $table->json('symptoms')->nullable();
            $table->text('complaint_details')->nullable();
            $table->date('date');
            $table->string('time_slot');
            $table->string('status')->default('Booked');
            $table->string('mode')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('medical_records')) {
        Schema::create('medical_records', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('patient_id')->nullable();
            $table->string('record_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('prescriptions')) {
        Schema::create('prescriptions', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('patient_id')->nullable();
            $table->string('prescription_number')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('medicine')->nullable();
            $table->string('quantity')->nullable();
            $table->text('instructions')->nullable();
            $table->text('remarks')->nullable();
            $table->string('encounter_code')->nullable();
            $table->string('license_no')->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('date')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}

function patientManagementAdmin(): Admin
{
    return Admin::factory()->create();
}

test('the patient roster renders the admin navigation and patient information', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    makePatient();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients'))
        ->assertOk()
        ->assertSee('Patient Management', false)
        ->assertSee('JUAN S DELA CRUZ', false)
        ->assertSee('admin-sidebar', false)
        ->assertSee('admin-header', false)
        ->assertSee('data-patient-filters', false)
        ->assertSee('id="createPatientModal"', false);
});

test('the create patient modal is included in admin main content for ajax navigation', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();

    $html = $this->actingAs($admin, 'admin')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('admin.patients'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('class="admin-main"');
    expect($html)->toContain('id="createPatientModal"');

    preg_match('/<main class="admin-main">(.*)<\/main>/s', $html, $matches);
    expect($matches[1] ?? '')->toContain('id="createPatientModal"');
});

test('guests are redirected away from the patient roster', function (): void {
    patientManagementTables();

    $this->get(route('admin.patients'))
        ->assertRedirect(route('auth.login'));
});

test('the roster search matches names, username, email, and hospital number', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    makePatient(['first_name' => 'MARIA', 'middlename' => '', 'last_name' => 'SANTOS', 'username' => 'maria.santos', 'email' => 'maria@example.com']);
    makePatient(['first_name' => 'JOSE', 'middlename' => '', 'last_name' => 'REYES', 'username' => 'jose.reyes', 'hospital_number' => '0999888']);
    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients', ['search' => 'maria']))
        ->assertOk()
        ->assertSee('MARIA SANTOS', false)
        ->assertDontSee('JOSE REYES', false);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients', ['search' => '0999888']))
        ->assertOk()
        ->assertSee('JOSE REYES', false)
        ->assertDontSee('MARIA SANTOS', false);
});

test('the roster filters by status and gender', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    makePatient(['first_name' => 'ACTIVE', 'middlename' => '', 'last_name' => 'PATIENT', 'username' => 'active.patient', 'status' => 'Active', 'gender' => 'Male']);
    makePatient(['first_name' => 'PENDING', 'middlename' => '', 'last_name' => 'PATIENT', 'username' => 'pending.patient', 'status' => 'Pending', 'gender' => 'Female']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients', ['status' => 'Active']))
        ->assertOk()
        ->assertSee('ACTIVE PATIENT', false)
        ->assertDontSee('PENDING PATIENT', false);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients', ['gender' => 'Female']))
        ->assertOk()
        ->assertSee('PENDING PATIENT', false)
        ->assertDontSee('ACTIVE PATIENT', false);
});

test('the roster counts deactivated patients instead of pending ones', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    makePatient(['first_name' => 'ACTIVE', 'middlename' => '', 'last_name' => 'PATIENT', 'username' => 'active.patient', 'status' => 'Active', 'gender' => 'Male']);
    makePatient(['first_name' => 'OFF', 'middlename' => '', 'last_name' => 'PATIENT', 'username' => 'off.patient', 'status' => 'Pending', 'gender' => 'Female']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients'))
        ->assertOk()
        ->assertSee('<small>Deactivated</small><strong data-patient-stat="deactivated">1</strong>', false)
        ->assertSee('<small>Active</small><strong data-patient-stat="active">1</strong>', false)
        ->assertDontSee('data-patient-stat="pending"', false);
});

test('toggling a patient account refreshes the deactivated stat card', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient(['status' => 'Active']);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.patients.status', $patient), [], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('stats.deactivated', '1');

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.patients.status', $patient), [], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('stats.deactivated', '0');
});

test('the patient roster paginates twenty patients at a time', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();

    foreach (range(1, 21) as $number) {
        makePatient([
            'first_name' => 'PATIENT',
            'last_name' => (string) $number,
            'username' => 'patient-'.$number,
        ]);
    }

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients'))
        ->assertOk()
        ->assertViewHas('patients', function ($paginator): bool {
            return $paginator->total() === 21
                && $paginator->perPage() === 20
                && $paginator->currentPage() === 1;
        });
});

test('the view patient route opens the profile modal on the roster', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients.show', $patient))
        ->assertRedirect(route('admin.patients', ['view' => $patient->id]));

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients', ['view' => $patient->id]))
        ->assertOk()
        ->assertSee('id="viewPatientModal"', false)
        ->assertSee('Patient profile', false)
        ->assertSee('JUAN S DELA CRUZ', false)
        ->assertSee('Medical information', false)
        ->assertSee('Appointment history', false)
        ->assertSee('Consultation history', false);
});

test('the edit patient route opens the edit modal on the roster', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients.edit', $patient))
        ->assertRedirect(route('admin.patients', ['edit' => $patient->id]));

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients', ['edit' => $patient->id]))
        ->assertOk()
        ->assertSee('id="editPatientModal"', false)
        ->assertSee('Edit patient', false)
        ->assertSee('value="JUAN" required maxlength="100"', false);
});

test('an administrator can edit patient information', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient();

    $this->actingAs($admin, 'admin')
        ->put(route('admin.patients.update', $patient), [
            'firstname' => 'PEDRO',
            'middlename' => 'MENDOZA',
            'lastname' => 'RAMIREZ',
            'username' => 'pedro.ramirez',
            'email' => 'pedro@example.com',
            'contactno' => '09181234567',
            'dob' => '1990-01-15',
            'gender' => 'Male',
            'address' => '123 Main St',
            'hospital_number' => '123456',
            'status' => 'Active',
        ])
        ->assertRedirect(route('admin.patients', ['view' => $patient->id]))
        ->assertSessionHas('success', 'Patient information updated successfully.');

    $patient->refresh();

    expect($patient->first_name)->toBe('PEDRO')
        ->and($patient->last_name)->toBe('RAMIREZ')
        ->and($patient->username)->toBe('pedro.ramirez')
        ->and($patient->email)->toBe('pedro@example.com')
        ->and($patient->contact_number)->toBe('09181234567')
        ->and($patient->hospital_number)->toBe('123456');
});

test('editing a patient requires a valid username and name', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient();

    $this->actingAs($admin, 'admin')
        ->put(route('admin.patients.update', $patient), [
            'firstname' => '',
            'lastname' => '',
            'username' => '',
        ])
        ->assertSessionHasErrors(['firstname', 'lastname', 'username']);
});

test('an administrator can deactivate and reactivate a patient account', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient(['status' => 'Active']);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.patients.status', $patient))
        ->assertRedirect(route('admin.patients'))
        ->assertSessionHas('success', 'Patient account deactivated successfully.');

    expect($patient->refresh()->status)->toBe('Pending');

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.patients.status', $patient))
        ->assertSessionHas('success', 'Patient account activated successfully.');

    expect($patient->refresh()->status)->toBe('Active');
});

test('an administrator can reset a patient password', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.patients.reset-password', $patient))
        ->assertRedirect(route('admin.patients', ['view' => $patient->id]))
        ->assertSessionHas('success');

    expect(Hash::check('secret123', $patient->refresh()->password))->toBeFalse();
});

test('the patient appointment history page lists all appointments', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient();
    $service = Service::create(['service_name' => 'General Medicine']);
    $staff = Staff::create(['FirstName' => 'Ana', 'LastName' => 'Cruz', 'username' => 'ana.cruz', 'password' => 'Password@123']);

    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'date' => '2026-09-01',
        'time_slot' => '09:00',
        'status' => 'Booked',
        'mode' => 'FACE',
        'consultation_reason' => 'Check-up',
    ]);
    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'date' => '2026-09-10',
        'time_slot' => '10:00',
        'status' => 'Completed',
        'mode' => 'FACE',
        'consultation_reason' => 'Follow-up',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients.appointments', $patient))
        ->assertOk()
        ->assertSee('Patient appointment history', false)
        ->assertSee('General Medicine', false)
        ->assertSee('Check-up', false)
        ->assertSee('Follow-up', false);
});

test('the patient consultation history page only lists completed appointments', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient();
    $service = Service::create(['service_name' => 'General Medicine']);

    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'date' => '2026-09-01',
        'time_slot' => '09:00',
        'status' => 'Booked',
        'mode' => 'FACE',
    ]);
    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'date' => '2026-09-10',
        'time_slot' => '10:00',
        'status' => 'Completed',
        'mode' => 'FACE',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients.history', $patient))
        ->assertOk()
        ->assertSee('Patient consultation history', false)
        ->assertSee('admin-status-pill completed', false);
});

test('the patient medical records page lists records and prescriptions', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient();

    MedicalRecord::create([
        'patient_id' => $patient->id,
        'record_type' => 'Laboratory',
        'description' => 'Blood test results',
        'created_at' => '2026-09-01 09:00:00',
    ]);
    Prescription::create([
        'patient_id' => $patient->id,
        'doctor_name' => 'Dr. Ana Cruz',
        'notes' => 'Take medication twice daily',
        'created_at' => '2026-09-01 09:30:00',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients.records', $patient))
        ->assertOk()
        ->assertSee('Blood test results', false)
        ->assertSee('Take medication twice daily', false);
});

test('the patient visit history page lists completed visits', function (): void {
    patientManagementTables();
    $admin = patientManagementAdmin();
    $patient = makePatient();
    $serviceTele = ServiceTele::create(['service_name' => 'Teleconsult']);

    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $serviceTele->id,
        'date' => '2026-09-10',
        'time_slot' => '10:00',
        'status' => 'Completed',
        'mode' => 'TELE',
        'consultation_reason' => 'Teleconsult follow-up',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.patients.visits', $patient))
        ->assertOk()
        ->assertSee('Patient visit history', false)
        ->assertSee('Teleconsult', false)
        ->assertSee('Teleconsult follow-up', false);
});
