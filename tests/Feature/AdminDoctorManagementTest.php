<?php

use App\Models\Admin;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function doctorManagementTables(): void
{
    if (! Schema::hasTable('staff')) {
        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 50)->nullable();
            $table->string('password')->nullable();
            $table->string('LastName', 100)->nullable();
            $table->string('FirstName', 100)->nullable();
            $table->string('MiddleName', 100)->nullable();
            $table->string('contactno', 20)->nullable();
            $table->string('otp', 6)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('site', 20)->nullable();
            $table->string('employee_id')->nullable();
            $table->timestamps();
            $table->string('email', 191)->nullable();
            $table->string('consultation_type', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('availability')->nullable();
            $table->boolean('is_doctor')->default(false);
            $table->unsignedBigInteger('legacy_doctor_id')->nullable()->unique();
            $table->string('profile_pic')->nullable();
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
            $table->date('date');
            $table->string('time_slot');
            $table->string('status')->default('Booked');
            $table->string('mode')->nullable();
            $table->string('consultation_reason')->nullable();
            $table->timestamps();
        });
    }
}

function doctorManagementAdmin(): Admin
{
    return Admin::factory()->create();
}

test('doctor records do not store removed practice fields', function () {
    doctorManagementTables();

    expect(Schema::hasColumn('staff', 'specialization'))->toBeFalse()
        ->and(Schema::hasColumn('staff', 'department'))->toBeFalse()
        ->and(Schema::hasColumn('staff', 'consultation_fee'))->toBeFalse();
});

test('the add doctor form requires an email', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors', ['create' => 1]))
        ->assertOk()
        ->assertSee('id="addDoctorModal"', false)
        ->assertSee('name="email" type="email" value="" maxlength="191" required', false);
});

test('the add doctor form includes an optional legacy doctor ID', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors', ['create' => 1]))
        ->assertOk()
        ->assertSee('name="legacy_doctor_id" type="number" min="1" step="1" value=""', false);
});

test('the add doctor form does not include a consultation type', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors', ['create' => 1]))
        ->assertOk()
        ->assertDontSee('name="consultation_type"', false);
});

test('the create doctor route opens the add doctor modal on the roster', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors.create'))
        ->assertRedirect(route('admin.doctors', ['create' => 1]));
});

test('the add doctor modal can only be dismissed by its close button', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors', ['create' => 1]))
        ->assertOk();
    $content = (string) $response->getContent();

    expect($content)->toContain('id="addDoctorModal"')
        ->and($content)->toContain("backdrop: 'static'")
        ->and($content)->toContain('keyboard: false')
        ->and($content)->toContain('if (!closeButtonRequested)')
        ->and(substr_count($content, 'class="btn-close" data-bs-dismiss="modal"'))->toBe(1);
});

test('the edit provider route opens the edit modal on the roster', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();
    $doctor = Staff::create([
        'FirstName' => 'Ana',
        'LastName' => 'Cruz',
        'username' => 'ana.cruz',
        'password' => 'Password@123',
        'consultation_type' => 'Initial consultation',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors.edit', $doctor))
        ->assertRedirect(route('admin.doctors', ['edit' => $doctor->id]));

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors', ['edit' => $doctor->id]))
        ->assertOk()
        ->assertSee('id="editProviderModal"', false)
        ->assertSee('admin-doctor-modal-header', false)
        ->assertSee('Edit provider')
        ->assertSee('Ana Cruz')
        ->assertSee('name="consultation_type"', false);
});

test('the doctors roster renders staff management information', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    Staff::create([
        'FirstName' => 'Reniel',
        'LastName' => 'Montejo',
        'username' => 'reniel.montejo',
        'password' => 'Password@123',
        'email' => 'reniel@example.com',
        'contactno' => '09171234567',
        'consultation_type' => 'Follow-up',
        'is_active' => true,
        'availability' => [
            'days' => ['monday', 'wednesday'],
            'start' => '08:00',
            'end' => '17:00',
        ],
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors'))
        ->assertOk()
        ->assertSee('Doctors & Staff')
        ->assertSee('Reniel Montejo')
        ->assertSee('data-doctor-filters', false)
        ->assertDontSee('Follow-up', false)
        ->assertDontSee('<th scope="col">Consultation</th>', false)
        ->assertDontSee('Specialization', false)
        ->assertDontSee('Department', false)
        ->assertDontSee('Consultation fee', false)
        ->assertDontSee('>Filter</button>', false);
});

test('the roster search includes the middle name', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    Staff::create([
        'FirstName' => 'Reniel',
        'MiddleName' => 'Quinn',
        'LastName' => 'Montejo',
        'username' => 'reniel.quinn',
        'password' => 'Password@123',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors', ['search' => 'Quinn']))
        ->assertOk()
        ->assertSee('Reniel Quinn Montejo')
        ->assertSee('Quinn', false);
});

test('the doctor roster paginates twenty providers at a time', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    foreach (range(1, 21) as $number) {
        Staff::create([
            'FirstName' => 'Doctor',
            'LastName' => (string) $number,
            'username' => 'doctor-'.$number,
            'password' => 'Password@123',
        ]);
    }

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors'))
        ->assertOk()
        ->assertViewHas('doctors', function ($paginator): bool {
            return $paginator->total() === 21
                && $paginator->perPage() === 20
                && $paginator->currentPage() === 1;
        })
        ->assertSee('admin-doctor-page-current', false)
        ->assertSee('&laquo;', false);
});

test('an administrator can add a doctor with a schedule', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.doctors.store'), [
            'firstname' => 'Maria',
            'lastname' => 'Santos',
            'username' => 'maria.santos',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'email' => 'maria@example.com',
            'contactno' => '09181234567',
            'site' => 'BOTH',
            'is_active' => '1',
            'availability_days' => ['tuesday', 'thursday'],
            'shift_start' => '09:00',
            'shift_end' => '18:00',
        ])
        ->assertRedirect(route('admin.doctors'))
        ->assertSessionHas('success', 'Doctor added successfully.');

    $doctor = Staff::where('username', 'maria.santos')->firstOrFail();

    expect($doctor->full_name)->toBe('Maria Santos')
        ->and($doctor->email)->toBe('maria@example.com')
        ->and($doctor->consultation_type)->toBeNull()
        ->and($doctor->legacy_doctor_id)->toBeNull()
        ->and($doctor->is_active)->toBeTrue()
        ->and($doctor->is_doctor)->toBeTrue()
        ->and($doctor->availability)->toMatchArray([
            'days' => ['tuesday', 'thursday'],
            'start' => '09:00',
            'end' => '18:00',
        ])
        ->and(Hash::check('Password@123', $doctor->password))->toBeTrue();
});

test('an administrator cannot add consultation type through the add doctor modal', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.doctors.store'), [
            'firstname' => 'Maria',
            'lastname' => 'Santos',
            'username' => 'maria.santos',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'email' => 'maria@example.com',
            'contactno' => '09181234567',
            'consultation_type' => 'Initial consultation',
        ])
        ->assertSessionHasErrors([
            'consultation_type' => 'The consultation type field is prohibited.',
        ]);

    expect(Staff::query()->where('username', 'maria.santos')->exists())->toBeFalse();
});

test('an administrator cannot add a doctor without an email', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();

    $this->actingAs($admin, 'admin')
        ->from(route('admin.doctors', ['create' => 1]))
        ->post(route('admin.doctors.store'), [
            'firstname' => 'Maria',
            'lastname' => 'Santos',
            'username' => 'maria.santos',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'contactno' => '09181234567',
        ])
        ->assertRedirect(route('admin.doctors', ['create' => 1]))
        ->assertSessionHasErrors(['email' => 'The email field is required.']);

    expect(Staff::query()->where('username', 'maria.santos')->exists())->toBeFalse();
});

test('an administrator can edit and deactivate a doctor', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();
    $doctor = Staff::create([
        'FirstName' => 'Ana',
        'LastName' => 'Cruz',
        'username' => 'ana.cruz',
        'password' => 'Password@123',
        'consultation_type' => 'Initial consultation',
        'is_active' => true,
    ]);
    $originalPassword = $doctor->password;

    $this->actingAs($admin, 'admin')
        ->put(route('admin.doctors.update', $doctor), [
            'firstname' => 'Ana Maria',
            'lastname' => 'Cruz',
            'username' => 'ana.cruz',
            'email' => 'ana@example.com',
            'legacy_doctor_id' => '25',
            'contactno' => '09191234567',
            'consultation_type' => 'Follow-up',
            'site' => 'TELE',
            'is_active' => '1',
            'availability_days' => ['friday'],
            'shift_start' => '08:30',
            'shift_end' => '16:30',
        ])
        ->assertRedirect(route('admin.doctors', ['view' => $doctor->id]));

    $doctor->refresh();

    expect($doctor->FirstName)->toBe('Ana Maria')
        ->and($doctor->email)->toBe('ana@example.com')
        ->and($doctor->legacy_doctor_id)->toBe(25)
        ->and($doctor->consultation_type)->toBe('Follow-up')
        ->and($doctor->is_active)->toBeTrue()
        ->and($doctor->password)->toBe($originalPassword);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.doctors.status', $doctor))
        ->assertRedirect(route('admin.doctors'));

    expect($doctor->fresh()->is_active)->toBeFalse();
});

test('a doctor profile shows appointments and consultation history', function () {
    doctorManagementTables();
    $admin = doctorManagementAdmin();
    $doctor = Staff::create([
        'FirstName' => 'Leo',
        'LastName' => 'Reyes',
        'username' => 'leo.reyes',
        'password' => 'Password@123',
        'consultation_type' => 'General consultation',
        'is_active' => true,
    ]);
    $patient = makePatient([
        'first_name' => 'JUAN',
        'last_name' => 'DELA CRUZ',
        'username' => 'juan',
    ]);
    $service = Service::create(['service_name' => 'Face consultation']);
    $teleService = ServiceTele::create(['service_name' => 'Tele consultation']);

    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $teleService->id,
        'staff_id' => $doctor->id,
        'date' => today(),
        'time_slot' => '09:00 - 10:00',
        'status' => 'Booked',
        'mode' => 'TELE',
    ]);
    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'staff_id' => $doctor->id,
        'date' => today()->subDay(),
        'time_slot' => '10:00 - 11:00',
        'status' => 'Completed',
        'mode' => 'FACE',
    ]);
    $unassignedAppointment = Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $teleService->id,
        'date' => today()->addDay(),
        'time_slot' => '11:00 - 12:00',
        'status' => 'Booked',
        'mode' => 'TELE',
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.doctors.appointments.assign', $doctor), [
            'appointment_id' => $unassignedAppointment->id,
        ])
        ->assertRedirect(route('admin.doctors', ['view' => $doctor->id]))
        ->assertSessionHas('success', 'Appointment assigned to the provider.');

    expect($unassignedAppointment->fresh()->staff_id)->toBe($doctor->id);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors.show', $doctor))
        ->assertRedirect(route('admin.doctors', ['view' => $doctor->id]));

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors', ['view' => $doctor->id]))
        ->assertOk()
        ->assertSee('id="viewProviderModal"', false)
        ->assertSee('Provider profile')
        ->assertSee('Leo Reyes')
        ->assertSee('Tele consultation')
        ->assertSee('Face consultation')
        ->assertDontSee('Consultation type', false)
        ->assertSee('Consultation history');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors.appointments', $doctor))
        ->assertOk()
        ->assertSee('Doctor appointments');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.doctors.history', $doctor))
        ->assertOk()
        ->assertSee('Consultation history')
        ->assertSee('Completed');
});

test('the doctors management routes require an administrator', function () {
    doctorManagementTables();

    $this->get(route('admin.doctors'))
        ->assertRedirect(route('auth.login'));
});
