<?php

use App\Models\Admin;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ServiceTele;
use App\Models\Staff;
use App\Notifications\AppointmentCancelled;
use App\Support\StaffDoctorSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Tables these tests need. Named uniquely because Pest loads every test file
 * in a single PHP process, so helpers must never collide across files.
 *
 * The `appointments` table carries every column the telemed workflow writes
 * (`staff_id` and friends are added by guarded migrations, so they have to be
 * part of the create statement here).
 */
function telemedWorkflowTables(): void
{
    if (! Schema::hasTable('appointments')) {
        Schema::create('appointments', function ($table): void {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
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
            $table->string('request_mode', 10)->nullable();
            $table->string('meeting_link')->nullable();
            $table->boolean('room_opened')->nullable();
            $table->integer('opened_by')->nullable();
            $table->string('room_opened_by', 20)->nullable();
            $table->string('triager_status', 30)->default('Pending');
            $table->string('triager_action', 50)->nullable();
            $table->text('triager_remarks')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->integer('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services')) {
        Schema::create('services', function ($table): void {
            $table->id();
            $table->string('service_name');
            $table->string('availability_day')->nullable();
            $table->string('homis_code')->nullable();
            $table->timestamp('created_at')->nullable();
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

    if (! Schema::hasTable('unavailable_timeslots_tele')) {
        Schema::create('unavailable_timeslots_tele', function ($table): void {
            $table->increments('id');
            $table->integer('service_id');
            $table->date('date');
            $table->string('time_slot');
            $table->string('reason')->nullable();
        });
    }

    if (! Schema::hasTable('holidays_tele')) {
        Schema::create('holidays_tele', function ($table): void {
            $table->increments('id');
            $table->date('holiday_date');
            $table->string('description')->nullable();
        });
    }

    if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function ($table): void {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('patient_consent')) {
        Schema::create('patient_consent', function ($table): void {
            $table->id();
            $table->integer('patient_id');
            $table->timestamp('consented_at')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();
        });
    }
}

/**
 * Signed-in doctor session (the doctor portal authenticates through the
 * session, not a guard).
 *
 * @return array<string, string|int>
 */
function telemedWorkflowDoctorSession(): array
{
    StaffDoctorSchema::migrateDoctorsIntoStaff();

    $doctor = Staff::query()->updateOrCreate(
        ['email' => 'liza.tembladora@qmmc.local'],
        [
            'username' => 'liza.tembladora',
            'FirstName' => 'Liza',
            'MiddleName' => null,
            'LastName' => 'Tembladora',
            'password' => Hash::make('Qmmc!Liza#2026'),
            'is_verified' => true,
            'is_active' => true,
            'is_doctor' => true,
            'site' => 'Telemedicine',
        ],
    );

    return [
        'user_type' => 'doctor',
        'staff_id' => $doctor->id,
        'doctor_email' => $doctor->email,
        'doctor_name' => 'Dr. Liza Tembladora',
        'doctor_specialty' => $doctor->specialty(),
    ];
}

/**
 * @return array<string, string|int>
 */
function telemedWorkflowPatientSession(Patient $patient): array
{
    return [
        'user_type' => 'patient',
        'patient_id' => $patient->getKey(),
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function telemedWorkflowAppointment(Patient $patient, array $overrides = []): Appointment
{
    $service = ServiceTele::query()->firstOrCreate(
        ['service_name' => 'Family Medicine'],
        ['availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun']
    );

    return Appointment::create(array_merge([
        'patient_id' => $patient->getKey(),
        'service_id' => $service->getKey(),
        'complaint' => 'Routine consultation',
        'consultation_reason' => 'general_check_up',
        'date' => now('Asia/Manila')->addDay()->toDateString(),
        'time_slot' => '08:00 - 10:00',
        'status' => 'Pending',
        'mode' => 'TELE',
        'triager_status' => 'Approved',
    ], $overrides));
}

test('the doctor creates the consultation room first, which books the appointment', function () {
    telemedWorkflowTables();

    $doctorSession = telemedWorkflowDoctorSession();
    $patient = makePatient(['username' => 'workflow-creator']);

    $appointment = telemedWorkflowAppointment($patient);

    // The patient never opens the room: before the doctor has created it the
    // endpoint refuses and the appointment keeps its status.
    $this->withSession(telemedWorkflowPatientSession($patient))
        ->postJson(route('telemed.appointment.open-room', $appointment))
        ->assertStatus(403);

    expect($appointment->fresh()->room_opened)->toBeFalsy()
        ->and($appointment->fresh()->status)->toBe('Pending');

    // The doctor's confirmation modal posts JSON to the same endpoint.
    $this->withSession($doctorSession)
        ->postJson(route('doctor.appointments.open-room', $appointment))
        ->assertOk()
        ->assertJsonPath('room.can_join', true)
        ->assertJsonPath('room.can_create_room', false)
        ->assertJsonPath('room.status', 'Booked');

    $appointment->refresh();

    expect($appointment->room_opened)->toBeTrue()
        ->and($appointment->room_opened_by)->toBe('doctor')
        ->and($appointment->meeting_link)->not->toBeEmpty()
        // Creating the room books it — it must not read as Completed yet.
        ->and($appointment->status)->toBe('Booked');

    // The patient dashboard swaps "Create a Room" for "Join the Room".
    $this->withSession(telemedWorkflowPatientSession($patient))
        ->get(route('telemed.home'))
        ->assertOk()
        ->assertSee('Join the Room')
        ->assertDontSee('Create a Room');
});

test('the patient joining the room completes the appointment on both dashboards', function () {
    telemedWorkflowTables();

    $doctorSession = telemedWorkflowDoctorSession();
    $patient = makePatient(['username' => 'workflow-joiner']);

    $appointment = telemedWorkflowAppointment($patient, [
        'status' => 'Booked',
        'room_opened' => true,
        'room_opened_by' => 'doctor',
        'meeting_link' => 'https://meet.jit.si/workflow-join-test',
    ]);

    $this->withSession(telemedWorkflowPatientSession($patient))
        ->get(route('telemed.appointment.join', $appointment))
        ->assertRedirect('https://meet.jit.si/workflow-join-test');

    expect($appointment->fresh()->status)->toBe('Completed');

    // Same row, same status, on the doctor's side — and still joinable while
    // the scheduled window is open.
    $this->withSession($doctorSession)
        ->getJson(route('doctor.appointments.room', $appointment))
        ->assertOk()
        ->assertJsonPath('room.status', 'Completed')
        ->assertJsonPath('room.is_expired', false)
        ->assertJsonPath('room.can_join', true);

    // It stays on the patient's active dashboard as the visit in progress.
    $this->withSession(telemedWorkflowPatientSession($patient))
        ->get(route('telemed.home'))
        ->assertOk()
        ->assertSee('Completed');
});

test('an expired telemedicine appointment is closed, leaves the dashboard and moves to history', function () {
    telemedWorkflowTables();

    $doctorSession = telemedWorkflowDoctorSession();
    $patient = makePatient(['username' => 'workflow-expired']);

    // 08:00 - 10:00 today, read five minutes after the slot ended.
    Carbon::setTestNow(Carbon::parse('10:05', 'Asia/Manila'));

    $appointment = telemedWorkflowAppointment($patient, [
        'date' => now('Asia/Manila')->toDateString(),
        'status' => 'Booked',
        'room_opened' => true,
        'room_opened_by' => 'doctor',
        'meeting_link' => 'https://meet.jit.si/workflow-expired-test',
    ]);

    // The patient dashboard drops the visit and history picks it up.
    $this->withSession(telemedWorkflowPatientSession($patient))
        ->get(route('telemed.home'))
        ->assertOk()
        ->assertDontSee('data-appointment-id="'.$appointment->id.'"', false);

    expect($appointment->fresh()->status)->toBe('Completed');

    $this->withSession(telemedWorkflowPatientSession($patient))
        ->getJson(route('telemed.history'))
        ->assertOk()
        ->assertJsonPath('history.0.id', $appointment->id);

    // Joining and opening the room are refused after the scheduled end time.
    $this->withSession(telemedWorkflowPatientSession($patient))
        ->get(route('telemed.appointment.join', $appointment))
        ->assertForbidden();

    $this->withSession(telemedWorkflowPatientSession($patient))
        ->getJson(route('telemed.appointment.room', $appointment))
        ->assertOk()
        ->assertJsonPath('room.is_expired', true)
        ->assertJsonPath('room.can_join', false)
        ->assertJsonPath('room.can_create_room', false);

    $this->withSession($doctorSession)
        ->getJson(route('doctor.appointments.room', $appointment))
        ->assertOk()
        ->assertJsonPath('room.is_expired', true)
        ->assertJsonPath('room.can_join', false)
        ->assertJsonPath('room.can_create_room', false);

    Carbon::setTestNow();
});

test('a patient cancellation reaches the doctor with the reason, never the triage queue', function () {
    telemedWorkflowTables();

    $doctorSession = telemedWorkflowDoctorSession();
    $doctor = Staff::query()->where('email', 'liza.tembladora@qmmc.local')->firstOrFail();
    $triager = Admin::query()->create([
        'firstname' => 'Trina',
        'lastname' => 'Triager',
        'username' => 'trina.triager',
        'password' => 'secret123',
        'email' => 'trina.triager@qmmc.local',
        'contact_no' => '09171234567',
        'role' => 'triager',
    ]);

    $patient = makePatient(['username' => 'workflow-canceller']);

    // Triager-scheduled telemedicine visits carry no staff_id, so the doctor
    // is reached through the fallback that notifies the active doctors.
    $appointment = telemedWorkflowAppointment($patient, ['status' => 'Booked']);

    expect($appointment->staff_id)->toBeNull();

    $this->withSession(telemedWorkflowPatientSession($patient))
        ->postJson(route('telemed.book.cancel'), [
            'cancel_id' => $appointment->getKey(),
            'cancellation_reason' => 'I already recovered from the cold.',
        ])
        ->assertOk();

    $appointment->refresh();

    expect($appointment->status)->toBe('Cancelled')
        ->and($appointment->cancellation_reason)->toBe('I already recovered from the cold.');

    $doctorNotification = $doctor->realtimeNotifications()
        ->where('type', AppointmentCancelled::class)
        ->first();

    expect($doctorNotification)->not->toBeNull()
        ->and($doctorNotification->data['message'])->toContain('I already recovered from the cold.');

    // The triage queue is not part of a patient-initiated cancellation.
    expect($triager->realtimeNotifications()->count())->toBe(0)
        ->and($patient->realtimeNotifications()->count())->toBe(1);

    // The cancellation is what the doctor's own bell shows, live.
    $this->withSession($doctorSession)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread', 1)
        ->assertJsonPath('notifications.0.title', 'Appointment Cancelled')
        ->assertJsonPath(
            'notifications.0.message',
            'A patient has cancelled an appointment. Reason: I already recovered from the cold.'
        );
});

test('the room buttons use the agreed labels on both dashboards', function () {
    telemedWorkflowTables();

    $doctorSession = telemedWorkflowDoctorSession();
    $patient = makePatient(['username' => 'workflow-labels']);

    $notOpened = telemedWorkflowAppointment($patient, ['status' => 'Booked']);
    $opened = telemedWorkflowAppointment($patient, [
        'status' => 'Booked',
        'room_opened' => true,
        'room_opened_by' => 'doctor',
        'meeting_link' => 'https://meet.jit.si/workflow-label-test',
    ]);

    $this->withSession($doctorSession)
        ->get(route('doctor.appointments'))
        ->assertOk()
        ->assertSee('Create a Room')
        ->assertSee('Join the Room')
        ->assertDontSee('Create Jitsi Room')
        ->assertDontSee('Join Jitsi');

    $this->withSession(telemedWorkflowPatientSession($patient))
        ->get(route('telemed.mine'))
        ->assertOk()
        ->assertSee('Join the Room')
        ->assertDontSee('Create Jitsi Room')
        ->assertDontSee('Join Jitsi');
});
