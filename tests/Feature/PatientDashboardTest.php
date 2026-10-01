<?php

use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\ServiceTimeslotTele;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function patientDashboardTables(): void
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
            $table->string('request_mode', 10)->nullable();
            $table->string('meeting_link')->nullable();
            $table->boolean('room_opened')->nullable();
            $table->integer('opened_by')->nullable();
            $table->string('room_opened_by', 20)->nullable();
            $table->string('triager_status', 30)->default('Pending');
            $table->string('triager_action', 50)->nullable();
            $table->text('triager_remarks')->nullable();
            $table->integer('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services_tele')) {
        Schema::create('services_tele', function ($table) {
            $table->id();
            $table->string('service_name')->nullable();
            $table->string('availability_day')->nullable();
            $table->string('homis_code')->nullable();
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

    if (! Schema::hasTable('holidays_tele')) {
        Schema::create('holidays_tele', function ($table) {
            $table->increments('id');
            $table->date('holiday_date');
            $table->string('description')->nullable();
        });
    }

    if (! Schema::hasTable('service_timeslots_tele')) {
        Schema::create('service_timeslots_tele', function ($table) {
            $table->increments('id');
            $table->integer('service_id');
            $table->string('time_slot');
            $table->integer('slots')->default(1);
        });
    }

    if (! Schema::hasTable('unavailable_timeslots_tele')) {
        Schema::create('unavailable_timeslots_tele', function ($table) {
            $table->increments('id');
            $table->integer('service_id');
            $table->date('date');
            $table->string('time_slot');
            $table->string('reason')->nullable();
        });
    }

    if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function ($table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('patient_consent')) {
        Schema::create('patient_consent', function ($table) {
            $table->id();
            $table->integer('patient_id');
            $table->timestamp('consented_at')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();
        });
    }
}

test('patient dashboard renders live patient visits services and unread notifications', function () {
    patientDashboardTables();

    $patient = makePatient([
        'first_name' => 'JOMA',
        'middlename' => 'ROQUE',
        'last_name' => 'PORTILLO REVILLA',
    ]);

    $service = ServiceTele::create([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri',
        'homis_code' => 'FAM',
    ]);

    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'complaint' => 'Follow-up consultation',
        'date' => Carbon::today()->addDay()->toDateString(),
        'time_slot' => '08:00 - 10:00',
        'status' => 'Booked',
        'mode' => 'TELE',
        'meeting_link' => 'https://meet.jit.si/qmmc-preview',
    ]);

    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'complaint' => 'Cancelled consultation',
        'date' => Carbon::today()->addDays(3)->toDateString(),
        'time_slot' => '12:00 - 14:00',
        'status' => 'Cancelled',
        'mode' => 'TELE',
        'meeting_link' => 'https://meet.jit.si/qmmc-cancelled',
    ]);

    DB::table('notifications')->insert([
        ['patient_id' => $patient->id, 'message' => 'Appointment confirmed', 'is_read' => false, 'created_at' => now()],
        ['patient_id' => $patient->id, 'message' => 'Prescription ready', 'is_read' => false, 'created_at' => now()],
        ['patient_id' => $patient->id, 'message' => 'Profile reminder', 'is_read' => false, 'created_at' => now()],
    ]);

    $response = $this->withSession(['patient_id' => $patient->id])
        ->get('/telemed')
        ->assertOk()
        ->assertSee('Telemedicine Consultation')
        ->assertSee('Joma Roque Portillo Revilla')
        ->assertSee('Your Next Appointment')
        ->assertSee('Upcoming Visits')
        ->assertSee('Family Medicine')
        ->assertSee('Cancelled')
        ->assertSee('Mon, Tue, Wed, Thu, Fri')
        ->assertDontSee('telemedicineServices', false)
        ->assertDontSee('dashboard-service-grid', false)
        ->assertSee('Consent for Care and Data Processing')
        ->assertSee('Data Privacy Statement')
        ->assertSee('Telemedicine & Face-to-Face Consultation')
        ->assertSee('Mga Karapatan ng Pasyente')
        ->assertSee('careConsentModal', false)
        ->assertSee('myVisitsModal', false)
        ->assertSee('servicesModal', false)
        ->assertSee('recordsModal', false)
        ->assertSee('notificationsModal', false)
        ->assertSee('profileModal', false)
        ->assertSee('activeAppointmentModal', false)
        ->assertSee('You already have an active appointment', false)
        ->assertSee('Cancel or complete it before booking another visit.', false)
        ->assertDontSee('id="bookingModal"', false)
        ->assertSee('cancelledAppointmentModal', false)
        ->assertSee('data-open-cancelled-appointment', false)
        ->assertSee('data-sidebar-action="visits"', false)
        ->assertSee('data-open-notifications', false)
        ->assertSee('Notifications, 3 unread', false)
        ->assertSee('patient-dashboard-shell', false)
        ->assertSee('patient-mobile-menu', false)
        ->assertDontSee('href="https://meet.jit.si/qmmc-cancelled"', false)
        ->assertDontSee('navbar-expand-lg app-navbar', false);

    expect($response->getContent())
        ->toContain('patient-sidebar-badge">3</span>')
        ->toContain('patient-header-badge" aria-live="polite">3</span>');

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/consent', ['service_id' => $service->id])
        ->assertCreated()
        ->assertJsonPath('service_id', $service->id)
        ->assertJsonPath('active_appointment.date', Carbon::today()->addDay()->toDateString())
        ->assertJsonPath('active_appointment.time_slot', '08:00 - 10:00');

    expect(DB::table('patient_consent')->where('patient_id', $patient->id)->count())->toBe(1);
});

test('a pending face-to-face request does not block a telemedicine request', function () {
    patientDashboardTables();

    $patient = makePatient();

    Appointment::create([
        'patient_id' => $patient->id,
        'complaint' => 'Pending face-to-face request',
        'status' => 'Pending',
        'mode' => 'FACE',
        'request_mode' => 'FACE',
        'triager_status' => 'Pending',
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/book', [
            'consultation_reason' => 'none_of_the_above',
            'symptoms' => ['headache'],
            'complaint_details' => 'Telemedicine request while a face-to-face request is pending',
        ])
        ->assertCreated();

    expect(DB::table('appointments')->where('mode', 'TELE')->count())->toBe(1);
});

test('a pending telemedicine request does not block a face-to-face request', function () {
    patientDashboardTables();

    $patient = makePatient();

    Appointment::create([
        'patient_id' => $patient->id,
        'complaint' => 'Pending telemedicine request',
        'status' => 'Pending',
        'mode' => 'TELE',
        'request_mode' => 'TELE',
        'triager_status' => 'Pending',
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/book', ['consultation_reason' => 'general_check_up'])
        ->assertCreated();

    expect(DB::table('appointments')->where('mode', 'FACE')->count())->toBe(1);
});

test('a second request of the same consultation type is rejected', function () {
    patientDashboardTables();

    $patient = makePatient();

    Appointment::create([
        'patient_id' => $patient->id,
        'complaint' => 'Pending face-to-face request',
        'status' => 'Pending',
        'mode' => 'FACE',
        'request_mode' => 'FACE',
        'triager_status' => 'Pending',
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/book', ['consultation_reason' => 'general_check_up'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'You already have a pending face-to-face request. Please wait for the triage team to process it before submitting another.');

    expect(DB::table('appointments')->where('mode', 'FACE')->count())->toBe(1);
});

test('the dashboard booking modal stays available while one consultation type is taken', function () {
    patientDashboardTables();

    $patient = makePatient();

    Appointment::create([
        'patient_id' => $patient->id,
        'complaint' => 'Pending telemedicine request',
        'status' => 'Pending',
        'mode' => 'TELE',
        'request_mode' => 'TELE',
        'triager_status' => 'Pending',
    ]);

    // The modal must still render, carrying the per-type limits.
    expect($this->withSession(['patient_id' => $patient->id])
        ->get('/telemed')
        ->assertOk()
        ->assertSee('id="bookingModal"', false)
        ->assertSee('data-limit-face="false"', false)
        ->assertSee('data-limit-tele="true"', false)
        ->getContent())->toContain('Request an Appointment');
});

test('the booking modal is hidden once both consultation types are taken', function () {
    patientDashboardTables();

    $patient = makePatient();

    foreach (['FACE', 'TELE'] as $mode) {
        Appointment::create([
            'patient_id' => $patient->id,
            'complaint' => "Pending {$mode} request",
            'status' => 'Pending',
            'mode' => $mode,
            'request_mode' => $mode,
            'triager_status' => 'Pending',
        ]);
    }

    $this->withSession(['patient_id' => $patient->id])
        ->get('/telemed')
        ->assertOk()
        ->assertDontSee('id="bookingModal"', false);
});

test('consent reports the per-type booking limits', function () {
    patientDashboardTables();

    $patient = makePatient();

    Appointment::create([
        'patient_id' => $patient->id,
        'complaint' => 'Pending face-to-face request',
        'status' => 'Pending',
        'mode' => 'FACE',
        'request_mode' => 'FACE',
        'triager_status' => 'Pending',
    ]);

    // A pending request has no date, so it must not read as an active appointment.
    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/consent', ['service_mode' => 'TELE'])
        ->assertCreated()
        ->assertJsonPath('booking_limits.face', true)
        ->assertJsonPath('booking_limits.tele', false)
        ->assertJsonPath('active_appointment', null);
});

test('services modal lists face-to-face and telemedicine services with their consultation type', function () {
    patientDashboardTables();

    Service::create([
        'service_name' => 'EHWC',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
        'homis_code' => 'EHWC',
    ]);

    ServiceTele::create([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri',
        'homis_code' => 'FAM',
    ]);

    $response = $this->get('/telemed')
        ->assertOk()
        ->assertSee('EHWC')
        ->assertSee('Family Medicine')
        ->assertSee('Face-to-Face')
        ->assertSee('Telemedicine')
        ->assertSee('data-service-mode="FACE"', false)
        ->assertSee('data-service-mode="TELE"', false);

    expect($response->getContent())
        ->toContain('class="modal-service-mode face"')
        ->toContain('class="modal-service-mode tele"');
});

test('consent accepts a face-to-face service id from the services table', function () {
    patientDashboardTables();

    $patient = makePatient();

    // Both tables count from 1, so ids are pinned explicitly to keep them
    // distinct: an id that exists only in `services_tele` must not satisfy a
    // face-to-face consent request.
    $teleId = 501;
    $faceId = 601;

    DB::table('services_tele')->insert(['id' => $teleId, 'service_name' => 'Family Medicine', 'homis_code' => 'FAM']);
    DB::table('services')->insert(['id' => $faceId, 'service_name' => 'EHWC', 'homis_code' => 'EHWC']);

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/consent', ['service_mode' => 'FACE', 'service_id' => $faceId])
        ->assertCreated()
        ->assertJsonPath('service_id', $faceId);

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/consent', ['service_mode' => 'FACE', 'service_id' => $teleId])
        ->assertStatus(422)
        ->assertJsonValidationErrors('service_id');
});

test('calendar marks a date with no remaining slots as fully booked', function () {
    patientDashboardTables();

    $service = ServiceTele::create([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
        'homis_code' => 'FAM',
    ]);
    $date = Carbon::today()->addDays(2)->startOfDay();

    ServiceTimeslotTele::create([
        'service_id' => $service->id,
        'time_slot' => '08:00 - 10:00',
        'slots' => 1,
    ]);
    Appointment::create([
        'patient_id' => makePatient()->id,
        'service_id' => $service->id,
        'complaint' => 'Fully booked test',
        'date' => $date->toDateString(),
        'time_slot' => '08:00 - 10:00',
        'status' => 'Booked',
        'mode' => 'TELE',
    ]);

    $response = $this->getJson('/telemed/calendar?service_id='.$service->id.'&month='.$date->format('Y-m'))
        ->assertOk()
        ->assertJsonPath('month', $date->format('Y-m'));

    $day = collect($response->json('days'))->firstWhere('date', $date->toDateString());

    expect($day)
        ->not->toBeNull()
        ->and($day['available'])->toBeFalse()
        ->and($day['fully_booked'])->toBeTrue()
        ->and($day['reason'])->toBe('Fully booked');
});

test('dashboard booking modal can create an appointment through JSON', function () {
    patientDashboardTables();

    $patient = makePatient();
    $service = ServiceTele::create([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
        'homis_code' => 'FAM',
    ]);
    $date = Carbon::today()->addDays(2)->startOfDay();

    ServiceTimeslotTele::create([
        'service_id' => $service->id,
        'time_slot' => '10:00 - 12:00',
        'slots' => 2,
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/book', [
            'service_id' => $service->id,
            'date' => $date->toDateString(),
            'time_slot' => '10:00 - 12:00',
            'consultation_reason' => 'general_check_up',
            'symptoms' => ['headache', 'dizziness'],
            'complaint_details' => 'Dashboard modal consultation details',
        ])
        ->assertCreated()
        ->assertJsonPath('appointment.service_name', 'Family Medicine')
        ->assertJsonPath('appointment.date', $date->toDateString())
        ->assertJsonStructure(['appointment' => ['qr_code_url']]);

    $appointment = Appointment::where('patient_id', $patient->id)
        ->where('mode', 'TELE')
        ->where('status', 'Booked')
        ->firstOrFail();

    expect($appointment->consultation_reason)->toBe('general_check_up')
        ->and($appointment->symptoms)->toBe(['headache', 'dizziness'])
        ->and($appointment->complaint_details)->toBe('Dashboard modal consultation details')
        ->and($appointment->qr_code_token)->toHaveLength(64)
        ->and($appointment->qr_code_path)->toContain('/telemed/appointments/'.$appointment->id.'/qr');
});
