<?php

use App\Models\Appointment;
use App\Models\ServiceTele;
use App\Models\ServiceTimeslotTele;
use App\Models\Staff;
use App\Support\StaffDoctorSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function doctorDashboardTables(): void
{
    if (! Schema::hasTable('appointments')) {
        Schema::create('appointments', function ($table): void {
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

    if (! Schema::hasTable('services_tele')) {
        Schema::create('services_tele', function ($table): void {
            $table->id();
            $table->string('service_name');
            $table->string('availability_day')->nullable();
            $table->string('homis_code')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('service_timeslots_tele')) {
        Schema::create('service_timeslots_tele', function ($table): void {
            $table->increments('id');
            $table->integer('service_id');
            $table->string('time_slot');
            $table->integer('slots')->default(1);
        });
    }
}

/**
 * @return array<string, string|int>
 */
function doctorPortalSession(): array
{
    $doctor = Staff::query()->where('email', 'aldrin.gwapo@qmmc.local')->firstOrFail();

    return [
        'user_type' => 'doctor',
        'staff_id' => $doctor->id,
        'doctor_email' => $doctor->email,
        'doctor_name' => 'Dr. Aldrin Gwapo',
        'doctor_specialty' => $doctor->specialty(),
    ];
}

function configureDoctorPortal(): void
{
    StaffDoctorSchema::migrateDoctorsIntoStaff();

    Staff::query()->updateOrCreate(
        ['email' => 'aldrin.gwapo@qmmc.local'],
        [
            'username' => 'aldrin.gwapo',
            'FirstName' => 'Aldrin',
            'MiddleName' => null,
            'LastName' => 'Gwapo',
            'password' => Hash::make('Qmmc!Aldrin#2026'),
            'is_verified' => true,
            'is_active' => true,
            'is_doctor' => true,
            'site' => 'Telemedicine',
        ],
    );
}

test('the doctor login is isolated and valid credentials start a doctor session', function () {
    configureDoctorPortal();

    $this->get(route('doctor.dashboard'))
        ->assertRedirect(route('doctor.login'));

    $this->get(route('doctor.login'))
        ->assertOk()
        ->assertSee('Welcome, Doctor')
        ->assertSee('Sign in to dashboard');

    $response = $this->post(route('doctor.login.attempt'), [
        'email' => 'aldrin.gwapo@qmmc.local',
        'password' => 'Qmmc!Aldrin#2026',
    ]);

    $response->assertRedirect(route('doctor.dashboard'));
    $response->assertSessionHas('user_type', 'doctor');
    $response->assertSessionHas('staff_id', Staff::query()->where('email', 'aldrin.gwapo@qmmc.local')->value('id'));
    $response->assertSessionHas('doctor_email', 'aldrin.gwapo@qmmc.local');
    $response->assertSessionHas('doctor_name', 'Dr. Aldrin Gwapo');

    $this->post(route('doctor.logout'))
        ->assertRedirect(route('doctor.login'))
        ->assertSessionMissing('doctor_email');
});

test('invalid doctor credentials are rejected', function () {
    configureDoctorPortal();

    $this->from(route('doctor.login'))
        ->post(route('doctor.login.attempt'), [
            'email' => 'aldrin.gwapo@qmmc.local',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('doctor.login'))
        ->assertSessionHasErrors('email')
        ->assertSessionMissing('doctor_email');
});

test('the doctor dashboard shows telemedicine appointments counts and Jitsi links', function () {
    configureDoctorPortal();
    doctorDashboardTables();
    Carbon::setTestNow(Carbon::parse('2026-09-25 08:30:00', 'Asia/Manila'));

    $service = ServiceTele::create([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
        'homis_code' => 'FAM',
    ]);
    ServiceTimeslotTele::create([
        'service_id' => $service->id,
        'time_slot' => '09:00 - 10:30',
        'slots' => 4,
    ]);

    $bookedPatient = makePatient([
        'first_name' => 'ANA',
        'middlename' => null,
        'last_name' => 'CRUZ',
        'hospital_number' => 'HN-1001',
    ]);
    $completedPatient = makePatient([
        'first_name' => 'JUAN',
        'middlename' => null,
        'last_name' => 'DELA CRUZ',
        'username' => 'juan-doctor-test',
        'contact_number' => '09170000010',
        'hospital_number' => 'HN-1002',
    ]);
    $facePatient = makePatient([
        'first_name' => 'FACE',
        'middlename' => null,
        'last_name' => 'PATIENT',
        'username' => 'face-doctor-test',
        'contact_number' => '09170000011',
    ]);

    Appointment::create([
        'patient_id' => $bookedPatient->id,
        'service_id' => $service->id,
        'complaint' => 'Persistent headache',
        'consultation_reason' => 'general_check_up',
        'symptoms' => ['headache', 'dizziness'],
        'complaint_details' => 'Headache since yesterday with mild dizziness.',
        'date' => '2026-09-25',
        'time_slot' => '09:00 - 10:30',
        'status' => 'Booked',
        'mode' => 'TELE',
        'meeting_link' => 'https://meet.jit.si/doctor-test-ana',
    ]);
    Appointment::create([
        'patient_id' => $completedPatient->id,
        'service_id' => $service->id,
        'complaint' => 'Follow-up',
        'consultation_reason' => 'results_interpretation',
        'symptoms' => ['weakness'],
        'complaint_details' => 'Review recent results.',
        'date' => '2026-09-25',
        'time_slot' => '11:00 - 12:00',
        'status' => 'Completed',
        'mode' => 'TELE',
        'meeting_link' => 'https://meet.jit.si/doctor-test-juan',
    ]);
    Appointment::create([
        'patient_id' => $facePatient->id,
        'service_id' => $service->id,
        'date' => '2026-09-25',
        'time_slot' => '13:00 - 14:00',
        'status' => 'Booked',
        'mode' => 'FACE',
    ]);

    $response = $this->withSession(doctorPortalSession())
        ->get(route('doctor.dashboard'))
        ->assertOk()
        ->assertSee('Good morning, Dr. Aldrin Gwapo')
        ->assertSee('Ana Cruz')
        ->assertSee('Juan Dela Cruz')
        ->assertSee('Family Medicine')
        ->assertSee('Headache since yesterday with mild dizziness.')
        ->assertSee('https://meet.jit.si/doctor-test-ana', false)
        ->assertSee('Completed')
        ->assertDontSee('Face Patient');

    expect($response->getContent())
        ->toContain('Today\'s Overview')
        ->toContain('Next Patient');

    Carbon::setTestNow();
});

test('doctor appointment and patient pages search the telemedicine data', function () {
    configureDoctorPortal();
    doctorDashboardTables();
    $today = Carbon::now('Asia/Manila')->toDateString();

    $service = ServiceTele::create([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
    ]);
    $patient = makePatient([
        'first_name' => 'MARIA',
        'middlename' => null,
        'last_name' => 'REYES',
        'hospital_number' => 'HN-2001',
    ]);
    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'complaint' => 'Routine consultation',
        'consultation_reason' => 'general_check_up',
        'symptoms' => ['headache'],
        'complaint_details' => 'Routine checkup.',
        'date' => $today,
        'time_slot' => '09:00 - 10:00',
        'status' => 'Booked',
        'mode' => 'TELE',
        'meeting_link' => 'https://meet.jit.si/doctor-test-maria',
    ]);

    $this->withSession(doctorPortalSession())
        ->get(route('doctor.appointments', ['q' => 'Maria']))
        ->assertOk()
        ->assertSee('Maria Reyes')
        ->assertSee('HN-2001')
        ->assertSee('https://meet.jit.si/doctor-test-maria', false);

    $this->withSession(doctorPortalSession())
        ->get(route('doctor.patients', ['q' => 'HN-2001']))
        ->assertOk()
        ->assertSee('Maria Reyes')
        ->assertSee('HN-2001')
        ->assertSee('Telemedicine Patients');

    $this->withSession(doctorPortalSession())
        ->get(route('doctor.notifications'))
        ->assertOk()
        ->assertSee('Active Appointment Alerts')
        ->assertSee('Maria Reyes');

    $this->withSession(doctorPortalSession())
        ->get(route('doctor.profile'))
        ->assertOk()
        ->assertSee('Dr. Aldrin Gwapo')
        ->assertSee('aldrin.gwapo@qmmc.local')
        ->assertSee('All telemedicine appointments');
});

test('the doctor sidebar no longer links to a separate schedule page', function () {
    configureDoctorPortal();

    $this->withSession(doctorPortalSession())
        ->get(route('doctor.profile'))
        ->assertOk()
        ->assertDontSee('bi-calendar-week-fill');
});

test('the doctor profile page offers an edit modal with photo, info, and password forms', function () {
    configureDoctorPortal();

    $this->withSession(doctorPortalSession())
        ->get(route('doctor.profile'))
        ->assertOk()
        ->assertSee('Edit profile')
        ->assertSee('id="doctorProfileModal"', false)
        ->assertSee('name="first_name"', false)
        ->assertSee('name="profile_pic"', false)
        ->assertSee('name="current_password"', false)
        ->assertSee('name="password_confirmation"', false);
});

test('a doctor can edit profile details and upload a new photo', function () {
    configureDoctorPortal();

    $photo = new UploadedFile(
        tempnam(sys_get_temp_dir(), 'drpic'),
        'dr-photo.jpg',
        'image/jpeg',
        null,
        true
    );
    file_put_contents($photo->getPathname(), base64_decode(
        '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q=='
    ));

    $this->withSession(doctorPortalSession())
        ->put(route('doctor.profile.update'), [
            'first_name' => 'Aldrin',
            'middle_name' => null,
            'last_name' => 'Gwapo',
            'email' => 'aldrin.gwapo@qmmc.local',
            'contactno' => '09171234567',
            'profile_pic' => $photo,
        ])
        ->assertRedirect(route('doctor.profile'))
        ->assertSessionHas('success');

    $doctor = Staff::query()->where('email', 'aldrin.gwapo@qmmc.local')->firstOrFail();

    $photoPath = public_path($doctor->profile_pic);

    expect($doctor->contactno)->toBe('09171234567')
        ->and($doctor->profile_pic)->toStartWith('uploads/doctor-profiles/')
        ->and(file_exists($photoPath))->toBeTrue();

    @unlink($photoPath);
});

test('profile edits reject an email already used by another doctor', function () {
    configureDoctorPortal();

    Staff::query()->create([
        'username' => 'second.doc',
        'password' => Hash::make('password'),
        'FirstName' => 'Second',
        'LastName' => 'Doctor',
        'email' => 'second.doc@qmmc.local',
        'is_active' => true,
        'is_doctor' => true,
    ]);

    $this->withSession(doctorPortalSession())
        ->put(route('doctor.profile.update'), [
            'first_name' => 'Aldrin',
            'middle_name' => null,
            'last_name' => 'Gwapo',
            'email' => 'second.doc@qmmc.local',
            'contactno' => null,
        ])
        ->assertSessionHasErrors('email');
});

test('a doctor can change the password after confirming the current one', function () {
    configureDoctorPortal();

    $this->from(route('doctor.profile'))
        ->withSession(doctorPortalSession())
        ->put(route('doctor.profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'NewPass!2026',
            'password_confirmation' => 'NewPass!2026',
        ])
        ->assertSessionHasErrors('current_password');

    $doctor = Staff::query()->where('email', 'aldrin.gwapo@qmmc.local')->firstOrFail();

    expect(Hash::check('Qmmc!Aldrin#2026', $doctor->password))->toBeTrue();

    $this->withSession(doctorPortalSession())
        ->put(route('doctor.profile.password'), [
            'current_password' => 'Qmmc!Aldrin#2026',
            'password' => 'NewPass!2026',
            'password_confirmation' => 'NewPass!2026',
        ])
        ->assertRedirect(route('doctor.profile'))
        ->assertSessionHas('success');

    $doctor->refresh();

    expect(Hash::check('NewPass!2026', $doctor->password))->toBeTrue();
});

test('doctor appointment pagination renders with bootstrap markup', function () {
    configureDoctorPortal();
    doctorDashboardTables();

    $service = ServiceTele::create(['service_name' => 'Family Medicine']);
    $patient = makePatient([
        'first_name' => 'PAG',
        'middlename' => null,
        'last_name' => 'TEST',
    ]);

    for ($index = 0; $index < 25; $index++) {
        Appointment::create([
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'date' => '2026-09-'.str_pad((string) (($index % 28) + 1), 2, '0', STR_PAD_LEFT),
            'time_slot' => '08:00 - 10:00',
            'status' => 'Booked',
            'mode' => 'TELE',
        ]);
    }

    $this->withSession(doctorPortalSession())
        ->get(route('doctor.appointments'))
        ->assertOk()
        ->assertSee('class="pagination"', false);
});
