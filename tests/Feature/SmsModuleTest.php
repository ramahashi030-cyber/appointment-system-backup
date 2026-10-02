<?php

use App\Models\Admin;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\SmsLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function smsModuleTables(): void
{
    // `patients` is built by database/migrations/*_create_patients_table_when_missing.
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

    if (! Schema::hasTable('sms_logs')) {
        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('recipient', 100)->nullable();
            $table->text('message')->nullable();
            $table->boolean('sent_to_all')->default(false);
            $table->timestamp('sent_at')->useCurrent();
        });
    }
}

function smsModuleBookedAppointment(array $attributes = []): Appointment
{
    return Appointment::create(array_merge([
        'date' => '2026-11-05',
        'time_slot' => '09:00',
        'status' => 'Booked',
        'mode' => 'FACE',
    ], $attributes));
}

test('the SMS module lists real booked patients and the sidebar entry renders', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();
    $service = Service::create(['service_name' => 'General Consultation']);
    $patient = makePatient();
    smsModuleBookedAppointment(['patient_id' => $patient->id, 'service_id' => $service->id]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms'))
        ->assertOk()
        ->assertSee('SMS Module')
        ->assertSee('Booked Patients')
        ->assertSee(route('admin.sms'), false)
        ->assertSee('JUAN S DELA CRUZ')
        ->assertSee('Jan 15, 1990')
        ->assertSee('09171234567')
        ->assertSee('General Consultation')
        ->assertSee('Nov 5, 2026')
        ->assertSee('09:00')
        ->assertSee('Recently Sent Messages');
});

test('the SMS module only lists booked appointments', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();
    $booked = makePatient(['first_name' => 'BOOKED', 'middlename' => '', 'last_name' => 'PATIENT', 'username' => 'booked.patient']);
    $pending = makePatient(['first_name' => 'PENDING', 'middlename' => '', 'last_name' => 'PATIENT', 'username' => 'pending.patient']);

    smsModuleBookedAppointment(['patient_id' => $booked->id, 'service_id' => 1, 'status' => 'Booked']);
    smsModuleBookedAppointment(['patient_id' => $pending->id, 'service_id' => 1, 'status' => 'Pending']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms'))
        ->assertOk()
        ->assertSee('BOOKED PATIENT')
        ->assertDontSee('PENDING PATIENT');
});

test('the SMS module filters by date from, date to, and date range', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();
    $early = makePatient(['first_name' => 'EARLY', 'middlename' => '', 'last_name' => 'ONE', 'username' => 'early.one']);
    $middle = makePatient(['first_name' => 'MIDDLE', 'middlename' => '', 'last_name' => 'TWO', 'username' => 'middle.two']);
    $late = makePatient(['first_name' => 'LATE', 'middlename' => '', 'last_name' => 'THREE', 'username' => 'late.three']);

    smsModuleBookedAppointment(['patient_id' => $early->id, 'service_id' => 1, 'date' => '2026-11-01']);
    smsModuleBookedAppointment(['patient_id' => $middle->id, 'service_id' => 1, 'date' => '2026-11-10']);
    smsModuleBookedAppointment(['patient_id' => $late->id, 'service_id' => 1, 'date' => '2026-11-20']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms', ['from_date' => '2026-11-05']))
        ->assertOk()
        ->assertSee('MIDDLE TWO')
        ->assertSee('LATE THREE')
        ->assertDontSee('EARLY ONE');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms', ['to_date' => '2026-11-15']))
        ->assertOk()
        ->assertSee('EARLY ONE')
        ->assertSee('MIDDLE TWO')
        ->assertDontSee('LATE THREE');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms', ['from_date' => '2026-11-05', 'to_date' => '2026-11-15']))
        ->assertOk()
        ->assertSee('MIDDLE TWO')
        ->assertDontSee('EARLY ONE')
        ->assertDontSee('LATE THREE');
});

test('the SMS module rejects a date range where from is after to', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();
    $patient = makePatient();

    smsModuleBookedAppointment(['patient_id' => $patient->id, 'service_id' => 1]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms', ['from_date' => '2026-11-20', 'to_date' => '2026-11-01']))
        ->assertOk()
        ->assertSee('Date From cannot be later than Date To.')
        ->assertDontSee('JUAN S DELA CRUZ');
});

test('the SMS module filters by type of service using both service tables', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();
    $faceService = Service::create(['service_name' => 'Face Consultation']);
    $teleService = ServiceTele::create(['service_name' => 'Tele Consultation']);
    $facePatient = makePatient(['first_name' => 'FACE', 'middlename' => '', 'last_name' => 'CASE', 'username' => 'face.case']);
    $telePatient = makePatient(['first_name' => 'TELE', 'middlename' => '', 'last_name' => 'CASE', 'username' => 'tele.case']);

    smsModuleBookedAppointment(['patient_id' => $facePatient->id, 'service_id' => $faceService->id, 'mode' => 'FACE']);
    smsModuleBookedAppointment(['patient_id' => $telePatient->id, 'service_id' => $teleService->id, 'mode' => 'TELE']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms', ['service' => 'FACE:'.$faceService->id]))
        ->assertOk()
        ->assertSee('Face Consultation')
        ->assertSee('FACE CASE')
        ->assertDontSee('TELE CASE');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms', ['service' => 'TELE:'.$teleService->id]))
        ->assertOk()
        ->assertSee('Tele Consultation')
        ->assertSee('TELE CASE')
        ->assertDontSee('FACE CASE');
});

test('the SMS module searches booked patients by first or last name', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();
    $delaCruz = makePatient(['first_name' => 'JUAN', 'middlename' => '', 'last_name' => 'DELA CRUZ', 'username' => 'juan.dc']);
    $santos = makePatient(['first_name' => 'PEDRO', 'middlename' => 'JUAN', 'last_name' => 'SANTOS', 'username' => 'pedro.santos']);

    smsModuleBookedAppointment(['patient_id' => $delaCruz->id, 'service_id' => 1]);
    smsModuleBookedAppointment(['patient_id' => $santos->id, 'service_id' => 1]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms', ['search' => 'juan']))
        ->assertOk()
        ->assertSee('JUAN DELA CRUZ')
        ->assertSee('PEDRO JUAN SANTOS');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms', ['search' => 'dela']))
        ->assertOk()
        ->assertSee('JUAN DELA CRUZ')
        ->assertDontSee('PEDRO JUAN SANTOS');
});

test('sending with no selection shows the selection error', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->from(route('admin.sms'))
        ->post(route('admin.sms.send'), ['message' => 'Hello there'])
        ->assertRedirect(route('admin.sms'))
        ->assertSessionHas('error', 'Please select at least one patient.');
});

test('sending with an empty message shows the message error', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();
    $patient = makePatient();
    $appointment = smsModuleBookedAppointment(['patient_id' => $patient->id, 'service_id' => 1]);

    $this->actingAs($admin, 'admin')
        ->from(route('admin.sms'))
        ->post(route('admin.sms.send'), ['appointments' => [$appointment->id], 'message' => '   '])
        ->assertRedirect(route('admin.sms'))
        ->assertSessionHas('error', 'Please enter a message.');
});

test('sending uses the stored contact number and writes the sms history', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();
    $patient = makePatient();
    $appointment = smsModuleBookedAppointment(['patient_id' => $patient->id, 'service_id' => 1]);

    Http::fake(['*' => Http::response('OK', 200)]);

    $this->actingAs($admin, 'admin')
        ->from(route('admin.sms'))
        ->post(route('admin.sms.send'), [
            'appointments' => [$appointment->id],
            'message' => 'Hi patientname, see you soon.',
        ])
        ->assertRedirect(route('admin.sms'))
        ->assertSessionHas('success', 'Message sent to 1 patient.');

    $log = SmsLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->recipient)->toBe('09171234567')
        ->and($log->message)->toBe('Hi JUAN S DELA CRUZ, see you soon.');

    Http::assertSentCount(1);
});

test('sending skips patients without a valid contact number', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();
    $patient = makePatient(['contact_number' => '']);
    $appointment = smsModuleBookedAppointment(['patient_id' => $patient->id, 'service_id' => 1]);

    Http::fake();

    $this->actingAs($admin, 'admin')
        ->from(route('admin.sms'))
        ->post(route('admin.sms.send'), [
            'appointments' => [$appointment->id],
            'message' => 'Hello',
        ])
        ->assertRedirect(route('admin.sms'))
        ->assertSessionHas('error', 'One or more selected patients do not have a valid contact number. Skipped: JUAN S DELA CRUZ.');

    expect(SmsLog::query()->count())->toBe(0);
    Http::assertNothingSent();
});

test('the SMS history Sent At is displayed in Asia/Manila', function (): void {
    smsModuleTables();
    $admin = Admin::factory()->create();

    $log = new SmsLog([
        'recipient' => '09171234567',
        'message' => 'Reminder',
        'sent_to_all' => false,
    ]);
    $log->sent_at = Carbon::parse('2026-10-02 06:00:00', 'UTC');
    $log->save();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sms'))
        ->assertOk()
        ->assertSee('10/02/2026 02:00 PM')
        ->assertDontSee('10/02/2026 06:00 AM');
});

test('guests cannot open the SMS module', function (): void {
    smsModuleTables();

    $this->get(route('admin.sms'))->assertRedirect(route('auth.login'));
    $this->post(route('admin.sms.send'))->assertRedirect(route('auth.login'));
});
