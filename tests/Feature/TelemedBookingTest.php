<?php

use App\Models\Appointment;
use App\Models\ServiceTele;
use App\Models\ServiceTimeslotTele;
use App\Support\AppointmentQrCode;
use App\Support\AppointmentSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function telemedBookingTables(): void
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
        Schema::create('services_tele', function ($table): void {
            $table->id();
            $table->string('service_name');
            $table->string('availability_day')->nullable();
            $table->string('homis_code')->nullable();
            $table->timestamp('created_at')->nullable();
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

    if (! Schema::hasTable('holidays_tele')) {
        Schema::create('holidays_tele', function ($table): void {
            $table->increments('id');
            $table->date('holiday_date');
            $table->string('description')->nullable();
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
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function telemedBookingPayload(ServiceTele $service, Carbon $date, array $overrides = []): array
{
    return array_merge([
        'service_id' => $service->id,
        'date' => $date->toDateString(),
        'time_slot' => '08:00 - 10:00',
        'consultation_reason' => 'general_check_up',
        'symptoms' => ['headache', 'dizziness'],
        'complaint_details' => 'Persistent headache since yesterday.',
    ], $overrides);
}

function createTelemedTestService(): ServiceTele
{
    $service = ServiceTele::create([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
        'homis_code' => 'FAM',
    ]);

    ServiceTimeslotTele::create([
        'service_id' => $service->id,
        'time_slot' => '08:00 - 10:00',
        'slots' => 2,
    ]);

    return $service;
}

test('booking requires one reason one to three symptoms and complaint details', function () {
    telemedBookingTables();

    $service = createTelemedTestService();
    $date = Carbon::today()->addDay()->startOfDay();

    // The reason decides the consultation type, so it is required first.
    $this->withSession(['patient_id' => makePatient()->id])
        ->postJson('/telemed/book', telemedBookingPayload($service, $date, [
            'consultation_reason' => null,
            'symptoms' => [],
            'complaint_details' => '',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['consultation_reason']);

    // Telemedicine requests ("none of the above") need symptoms and details.
    $this->withSession(['patient_id' => makePatient(['username' => 'tele-empty'])->id])
        ->postJson('/telemed/book', telemedBookingPayload($service, $date, [
            'consultation_reason' => 'none_of_the_above',
            'symptoms' => [],
            'complaint_details' => '',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['symptoms', 'complaint_details']);

    $this->withSession(['patient_id' => makePatient(['username' => 'too-many'])->id])
        ->postJson('/telemed/book', telemedBookingPayload($service, $date, [
            'consultation_reason' => 'none_of_the_above',
            'symptoms' => ['headache', 'dizziness', 'cough', 'fever_or_chills'],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['symptoms']);

    $this->withSession(['patient_id' => makePatient(['username' => 'invalid-symptom'])->id])
        ->postJson('/telemed/book', telemedBookingPayload($service, $date, [
            'consultation_reason' => 'none_of_the_above',
            'symptoms' => ['not-a-real-symptom'],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['symptoms.0']);
});

test('booking additively repairs a legacy appointments table before inserting', function () {
    telemedBookingTables();

    $legacyColumns = array_values(array_filter([
        'consultation_reason',
        'symptoms',
        'complaint_details',
        'qr_code_token',
    ], fn (string $column): bool => Schema::hasColumn('appointments', $column)));

    if ($legacyColumns !== []) {
        Schema::table('appointments', function ($table) use ($legacyColumns): void {
            if (in_array('qr_code_token', $legacyColumns, true)) {
                $table->dropUnique(['qr_code_token']);
            }

            $table->dropColumn($legacyColumns);
        });
    }

    $schemaProperty = (new ReflectionClass(AppointmentSchema::class))->getProperty('isReady');
    $schemaProperty->setAccessible(true);
    $schemaProperty->setValue(null, false);

    $patient = makePatient();
    $service = createTelemedTestService();
    $date = Carbon::today()->addDay()->startOfDay();

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/book', telemedBookingPayload($service, $date))
        ->assertCreated();

    expect(Schema::hasColumn('appointments', 'consultation_reason'))->toBeTrue()
        ->and(Schema::hasColumn('appointments', 'symptoms'))->toBeTrue()
        ->and(Schema::hasColumn('appointments', 'complaint_details'))->toBeTrue()
        ->and(Schema::hasColumn('appointments', 'qr_code_token'))->toBeTrue();
});

test('a telemedicine request stores structured intake data and awaits triage', function () {
    telemedBookingTables();

    $patient = makePatient();
    $service = createTelemedTestService();
    $date = Carbon::today()->addDay()->startOfDay();

    // "None of the above" is what routes a request to telemedicine.
    $response = $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/book', telemedBookingPayload($service, $date, [
            'consultation_reason' => 'none_of_the_above',
        ]))
        ->assertCreated()
        ->assertJsonPath('appointments_url', route('telemed.mine'));

    $appointment = Appointment::where('patient_id', $patient->id)->firstOrFail();

    expect($appointment->consultation_reason)->toBe('none_of_the_above')
        ->and($appointment->symptoms)->toBe(['headache', 'dizziness'])
        ->and($appointment->complaint_details)->toBe('Persistent headache since yesterday.')
        ->and($appointment->mode)->toBe('TELE')
        ->and($appointment->request_mode)->toBe('TELE')
        ->and($appointment->status)->toBe('Pending')
        ->and($appointment->triager_status)->toBe('Pending')
        // The triage team schedules the visit, so no date or time yet.
        ->and($appointment->date)->toBeNull()
        ->and($appointment->time_slot)->toBeNull()
        ->and($response->json('request_id'))->toBe($appointment->id);

    $pagePatient = makePatient([
        'username' => 'booking-page-patient',
        'contact_number' => '09170000008',
    ]);

    $this->withSession(['patient_id' => $pagePatient->id])
        ->get(route('telemed.book'))
        ->assertOk()
        ->assertSee('Ano ang ipapakonsulta? (Pumili ng Isa)', false)
        ->assertSee('Please select at least 1 and maximum of 3 symptoms.', false)
        ->assertSee('Enter here...', false);
});

test('a specific consultation reason stores a face-to-face request', function () {
    telemedBookingTables();

    $patient = makePatient();
    $service = createTelemedTestService();
    $date = Carbon::today()->addDay()->startOfDay();

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/book', telemedBookingPayload($service, $date))
        ->assertCreated()
        ->assertJsonPath('appointments_url', route('telemed.mine'));

    $appointment = Appointment::where('patient_id', $patient->id)->firstOrFail();

    expect($appointment->consultation_reason)->toBe('general_check_up')
        ->and($appointment->mode)->toBe('FACE')
        ->and($appointment->request_mode)->toBe('FACE')
        ->and($appointment->status)->toBe('Pending')
        ->and($appointment->date)->toBeNull();
});

test('pending and confirmed appointments block another telemedicine booking', function (string $status) {
    telemedBookingTables();

    $patient = makePatient();
    $service = createTelemedTestService();
    $date = Carbon::today()->addDay()->startOfDay();

    $activeAppointment = Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'complaint' => 'Existing appointment',
        'date' => $date->toDateString(),
        'time_slot' => '08:00 - 10:00',
        'status' => $status,
        'mode' => 'TELE',
        'qr_code_token' => str_repeat('a', 64),
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/consent', ['service_id' => $service->id])
        ->assertCreated()
        ->assertJsonPath('active_appointment.id', $activeAppointment->id)
        ->assertJsonPath('active_appointment.date', $date->toDateString())
        ->assertJsonPath('active_appointment.time_slot', '08:00 - 10:00')
        // Telemedicine is taken; face-to-face is still available.
        ->assertJsonPath('booking_limits.tele', true)
        ->assertJsonPath('booking_limits.face', false);

    $this->withSession(['patient_id' => $patient->id])
        ->get('/telemed')
        ->assertOk()
        ->assertSee('You already have an active appointment', false)
        ->assertSee('Cancel or complete it before booking another visit.', false)
        // The booking modal stays open so the patient can still request
        // face-to-face, with telemedicine flagged as unavailable.
        ->assertSee('id="bookingModal"', false)
        ->assertSee('data-limit-face="false"', false)
        ->assertSee('data-limit-tele="true"', false);

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/book', telemedBookingPayload($service, $date, [
            'consultation_reason' => 'none_of_the_above',
        ]))
        ->assertUnprocessable()
        ->assertJsonPath(
            'message',
            'You already have an active telemedicine appointment. Please wait for it to be completed before requesting another.'
        );

    expect(Appointment::where('patient_id', $patient->id)->count())->toBe(1);
})->with(['Booked', 'Pending', 'Confirmed']);

test('the patient QR is an SVG containing a kiosk verification token', function () {
    telemedBookingTables();

    $patient = makePatient();
    $otherPatient = makePatient([
        'username' => 'other-qr-patient',
        'contact_number' => '09170000009',
    ]);
    $service = createTelemedTestService();
    $date = Carbon::today()->addDay()->startOfDay();

    $this->withSession(['patient_id' => $patient->id])
        ->postJson('/telemed/book', telemedBookingPayload($service, $date))
        ->assertCreated();

    $appointment = Appointment::where('patient_id', $patient->id)->firstOrFail();
    $qrCode = new AppointmentQrCode;

    $this->withSession(['patient_id' => $patient->id])
        ->get(route('telemed.appointment.qr', $appointment))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
        ->assertSee('<svg', false);

    expect($qrCode->payload($appointment))->toBe(
        "Appointment ID: {$appointment->id}\nVerification: {$appointment->qr_code_token}"
    );

    $this->withSession(['patient_id' => $otherPatient->id])
        ->get(route('telemed.appointment.qr', $appointment))
        ->assertForbidden();
});
