<?php

use App\Models\Admin;
use App\Models\Appointment;
use App\Models\AppointmentCheckin;
use App\Models\Patient;
use App\Models\Service;
use App\Support\Kiosk\HomisGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * Fake HOMIS gateway: records every call and answers with configurable
 * results so the kiosk flow can be exercised (success, unreachable HOMIS,
 * failed registration) without touching the SQL Server connection.
 */
class FakeKioskHomisGateway implements HomisGateway
{
    /** @var list<string> */
    public array $lookups = [];

    /** @var list<array{person: array<string, string>, dob: string, tscode: string, diagtxt: string, enccode: string}> */
    public array $registrations = [];

    public function __construct(
        public array $lookupResult = ['status' => 'ok', 'found' => true, 'person' => [], 'message' => ''],
        public array $registrationResult = ['status' => 'ok', 'enccode' => '', 'message' => ''],
    ) {}

    public function lookupPerson(string $hospitalNumber): array
    {
        $this->lookups[] = $hospitalNumber;

        return $this->lookupResult;
    }

    public function registerOpd(array $person, string $dob, string $tscode, string $diagtxt, string $enccode): array
    {
        $this->registrations[] = compact('person', 'dob', 'tscode', 'diagtxt', 'enccode');

        if ($this->registrationResult['status'] === 'ok' && $this->registrationResult['enccode'] === '') {
            return ['status' => 'ok', 'enccode' => $enccode, 'message' => ''];
        }

        return $this->registrationResult;
    }
}

/**
 * The appointments / services tables are never created by a migration (the
 * production schema already had them), so the tests create them defensively
 * exactly like AdminDashboardTest does.
 */
function kioskTables(): void
{
    if (! Schema::hasTable('appointments')) {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('service_id')->nullable();
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
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('service_name');
            $table->string('availability_day')->nullable();
            $table->string('homis_code', 10)->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services_tele')) {
        Schema::create('services_tele', function (Blueprint $table): void {
            $table->id();
            $table->string('service_name');
            $table->string('availability_day')->nullable();
            $table->string('homis_code', 10)->nullable();
            $table->timestamps();
        });
    }
}

function kioskAdmin(array $overrides = []): Admin
{
    return Admin::create(array_merge([
        'firstname' => 'Kiosk',
        'lastname' => 'Administrator',
        'username' => 'kiosk-admin',
        'password' => 'Password@123',
        'email' => 'kiosk-admin@example.com',
        'contact_no' => '09170000000',
    ], $overrides));
}

function kioskAppointment(Patient $patient, array $overrides = []): Appointment
{
    $service = Service::firstOrCreate(
        ['service_name' => 'FAMILY MEDICINE'],
        ['homis_code' => 'OPD1'],
    );

    return Appointment::create(array_merge([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'complaint' => 'Fever and cough',
        'date' => now(config('app.display_timezone'))->toDateString(),
        'time_slot' => '09:00 AM - 10:00 AM',
        'status' => 'Booked',
        'mode' => 'FACE',
        'qr_code_token' => bin2hex(random_bytes(32)),
    ], $overrides));
}

function kioskQr(Appointment $appointment, ?string $token = null): string
{
    $token ??= $appointment->qr_code_token;

    return "Appointment ID: {$appointment->id}\nVerification: {$token}";
}

function kioskVerify(Appointment $appointment, ?string $token = null): TestResponse
{
    return test()->postJson(route('admin.kiosk.verify'), ['qr' => kioskQr($appointment, $token)]);
}

function kioskConfirmScan(string $scanId, bool $confirmed): TestResponse
{
    return test()->postJson(route('admin.kiosk.confirm'), ['scan_id' => $scanId, 'confirmed' => $confirmed]);
}

/**
 * Bind (or re-bind) the fake HOMIS gateway.
 *
 * The controller resolves its gateway once per test app, so a fake bound
 * before the first kiosk request is the one every request will use; swap its
 * result properties to change behaviour mid-test instead of calling this
 * again after the first request.
 */
function kioskGateway(array $lookup = [], array $registration = []): FakeKioskHomisGateway
{
    $gateway = new FakeKioskHomisGateway(
        array_merge([
            'status' => 'ok',
            'found' => true,
            'person' => [
                'hpercode' => '00012345',
                'patlast' => 'DELA CRUZ',
                'patfirst' => 'JUAN',
                'patmiddle' => 'S',
                'patbdate' => '1990-01-15',
                'patsex' => 'M',
            ],
            'message' => '',
        ], $lookup),
        array_merge(['status' => 'ok', 'enccode' => '', 'message' => ''], $registration),
    );

    app()->instance(HomisGateway::class, $gateway);

    return $gateway;
}

/**
 * A refusal must answer with the patient-facing message and leave a recorded
 * "failed" scan row behind.
 */
function expectRefusal(TestResponse $response, string $reason, string $message): AppointmentCheckin
{
    $response->assertOk()->assertJson(['success' => false, 'message' => $message]);

    $checkin = AppointmentCheckin::latest('id')->firstOrFail();

    expect($checkin->result)->toBe('failed')
        ->and($checkin->reason)->toBe($reason);

    return $checkin;
}

beforeEach(function (): void {
    kioskTables();
    kioskGateway();
});

test('every kiosk endpoint requires an administrator session', function () {
    $this->get(route('admin.kiosk'))->assertRedirect(route('auth.login'));
    $this->post(route('admin.kiosk.verify'))->assertRedirect(route('auth.login'));
    $this->post(route('admin.kiosk.confirm'))->assertRedirect(route('auth.login'));
    $this->get(route('admin.kiosk-history'))->assertRedirect(route('auth.login'));
});

test('the kiosk opens as a standalone full-screen page outside the admin layout', function () {
    $this->actingAs(kioskAdmin(), 'admin')
        ->get(route('admin.kiosk'))
        ->assertOk()
        ->assertSee('Scan QR Code')
        ->assertSee('QMMC Patient Appointment System')
        ->assertSee(route('admin.kiosk.verify'))
        ->assertSee('kiosk-screensaver')
        ->assertDontSee('admin-shell');
});

test('the kiosk history page renders and filters scans by result', function () {
    $this->actingAs(kioskAdmin(), 'admin');

    $refused = kioskAppointment(makePatient([
        'first_name' => 'ANA',
        'middlename' => 'R',
        'last_name' => 'SANTOS',
        'username' => 'ana-kiosk',
        'hospital_number' => '00011111',
    ]), ['status' => 'Cancelled']);
    kioskVerify($refused)->assertOk();

    $done = kioskAppointment(makePatient([
        'first_name' => 'PEDRO',
        'middlename' => 'M',
        'last_name' => 'CRUZ',
        'username' => 'pedro-kiosk',
        'hospital_number' => '00022222',
    ]));
    kioskConfirmScan(kioskVerify($done)->assertOk()->json('scan_id'), false)->assertOk();

    $this->get(route('admin.kiosk-history'))
        ->assertOk()
        ->assertSee('Kiosk History')
        ->assertSee('ANA R SANTOS')
        ->assertSee('PEDRO M CRUZ')
        ->assertSee(config('kiosk.name'))
        ->assertSee('admin/kiosk-history');

    $this->get(route('admin.kiosk-history', ['result' => 'failed']))
        ->assertOk()
        ->assertSee('ANA R SANTOS')
        ->assertDontSee('PEDRO M CRUZ');

    $this->get(route('admin.kiosk-history', ['result' => 'completed']))
        ->assertOk()
        ->assertSee('PEDRO M CRUZ')
        ->assertDontSee('ANA R SANTOS');
});

test('empty, malformed and token-less payloads are refused and recorded', function () {
    $this->actingAs(kioskAdmin(), 'admin');

    expectRefusal(
        test()->postJson(route('admin.kiosk.verify'), ['qr' => '']),
        'qr-empty',
        'Invalid QR code.',
    );

    expectRefusal(
        test()->postJson(route('admin.kiosk.verify'), ['qr' => 'not a qr code']),
        'qr-format',
        'Invalid QR code format.',
    );

    expectRefusal(
        test()->postJson(route('admin.kiosk.verify'), ['qr' => 'Appointment ID: 42']),
        'qr-token-missing',
        'Appointment QR verification is missing.',
    );

    expect(AppointmentCheckin::count())->toBe(3);
});

test('a qr payload whose token does not match the appointment is refused', function () {
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));
    $wrongToken = str_repeat('9', 64);

    $checkin = expectRefusal(
        kioskVerify($appointment, $wrongToken),
        'qr-unverified',
        'Invalid or unverified appointment QR code.',
    );

    expect($checkin->appointment_id)->toBeNull()
        ->and($checkin->token_digest)->toBe(hash('sha256', $wrongToken));
});

test('cancelled completed and inactive appointments are refused before homis is queried', function () {
    $gateway = kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $patient = makePatient(['hospital_number' => '00012345']);

    $cancelled = kioskAppointment($patient, ['status' => 'Cancelled']);
    $checkin = expectRefusal(
        kioskVerify($cancelled),
        'cancelled',
        'This appointment has been cancelled.',
    );
    expect($checkin->patient_name)->toBe('JUAN S DELA CRUZ');

    $completed = kioskAppointment($patient, ['status' => 'Completed']);
    expectRefusal(
        kioskVerify($completed),
        'already-completed',
        'This appointment has already been completed. Please check the schedule in your portal.',
    );

    $inactive = kioskAppointment($patient, ['status' => 'Rejected']);
    expectRefusal(
        kioskVerify($inactive),
        'inactive',
        'This appointment is not active.',
    );

    expect($gateway->lookups)->toBeEmpty();
});

test('appointments not scheduled for today are refused with the matching message', function () {
    kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $patient = makePatient(['hospital_number' => '00012345']);
    $displayTimezone = config('app.display_timezone');

    $tomorrow = kioskAppointment($patient, ['date' => now($displayTimezone)->addDay()->toDateString()]);
    expectRefusal(
        kioskVerify($tomorrow),
        'not-today',
        'Your schedule is on '.$tomorrow->date->format('F d, Y').'. We only process those who have schedule for today.',
    );

    $yesterday = kioskAppointment($patient, ['date' => now($displayTimezone)->subDay()->toDateString()]);
    expectRefusal(
        kioskVerify($yesterday),
        'expired',
        'Your schedule dated '.$yesterday->date->format('F d, Y')
            .' has already expired, please login to Patient Appointment System, cancel your expired appointment then select new appointment.',
    );
});

test('a patient without a hospital number is refused before homis is queried', function () {
    $gateway = kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => null]));

    expectRefusal(
        kioskVerify($appointment),
        'no-hospital-number',
        "System has detected that you don't have a Hospital Number yet. Please proceed to the OPD encoder.",
    );

    expect($gateway->lookups)->toBeEmpty();
});

test('an unreachable or unknown homis record refuses the scan without a reservation', function () {
    $gateway = kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));

    // The controller resolves the gateway once per test app, so the fake is
    // reconfigured in place rather than rebound.
    $gateway->lookupResult = ['status' => 'error', 'found' => false, 'person' => null, 'message' => 'ODBC down'];

    expectRefusal(
        kioskVerify($appointment),
        'homis-unreachable',
        'The hospital records system is temporarily unavailable. Please try again or proceed to the OPD encoder.',
    );

    $gateway->lookupResult = ['status' => 'ok', 'found' => false, 'person' => null, 'message' => ''];

    expectRefusal(
        kioskVerify($appointment),
        'homis-not-found',
        'Hospital number 00012345 was not found in the hospital records system. Please proceed to the OPD encoder.',
    );

    expect($gateway->lookups)->toBe(['00012345', '00012345'])
        ->and(AppointmentCheckin::where('result', 'processing')->count())->toBe(0);
});

test('a verified scan returns confirmation data without exposing the token', function () {
    $gateway = kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $patient = makePatient(['hospital_number' => '00012345']);
    $appointment = kioskAppointment($patient);

    $response = kioskVerify($appointment);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('patient.last', 'DELA CRUZ')
        ->assertJsonPath('patient.first', 'JUAN')
        ->assertJsonPath('patient.middle', 'S')
        ->assertJsonPath('patient.birth_date', 'January 15, 1990')
        ->assertJsonPath('patient.hospital_number', '00012345')
        ->assertJsonPath('appointment.date', $appointment->date->format('F d, Y'));

    expect($response->getContent())->not->toContain($appointment->qr_code_token);

    $checkin = AppointmentCheckin::sole();
    expect($checkin->result)->toBe('processing')
        ->and($checkin->appointment_id)->toEqual($appointment->id)
        ->and($checkin->token_digest)->toBe(hash('sha256', $appointment->qr_code_token))
        ->and($checkin->homis_encounter_code)->not->toBeEmpty()
        ->and(strlen((string) $checkin->homis_encounter_code))->toBeLessThanOrEqual(48)
        ->and($response->json('scan_id'))->toBe($checkin->scan_id);

    // The scan ticket lives server-side only: neither the session nor the
    // response carries the raw verification token.
    expect(session('kiosk_scan'))->toBeArray()
        ->and(json_encode(session()->all()))->not->toContain($appointment->qr_code_token);

    expect($gateway->lookups)->toBe(['00012345'])
        ->and($gateway->registrations)->toBeEmpty();
});

test('confirming correct registers one opd encounter and completes the appointment', function () {
    $gateway = kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $patient = makePatient(['hospital_number' => '00012345']);
    $appointment = kioskAppointment($patient);

    $scanId = kioskVerify($appointment)->assertOk()->json('scan_id');

    $response = kioskConfirmScan($scanId, true);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response->json('message'))->toBe(
        'Appointment completed, You have been Automatically Added to OPD list for today, Please wait for your batch to be called',
    );

    expect($appointment->fresh()->status)->toBe('Completed');

    $checkin = AppointmentCheckin::sole();
    expect($checkin->result)->toBe('completed')
        ->and($checkin->reason)->toBe('registered')
        ->and($checkin->confirmed_at)->not->toBeNull();

    expect($gateway->registrations)->toHaveCount(1);
    $registration = $gateway->registrations[0];
    expect($registration['person']['hpercode'])->toBe('00012345')
        ->and($registration['dob'])->toBe('1990-01-15')
        ->and($registration['tscode'])->toBe('OPD1')
        ->and($registration['diagtxt'])->toBe('Fever and cough')
        ->and($registration['enccode'])->toBe($checkin->homis_encounter_code)
        ->and($registration['enccode'])->toStartWith('0001818'.'00012345');

    // The scan ticket is consumed once the transaction finishes.
    expect(session('kiosk_scan'))->toBeNull();
});

test('not correct completes the appointment without any homis write', function () {
    $gateway = kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));
    $scanId = kioskVerify($appointment)->assertOk()->json('scan_id');

    $response = kioskConfirmScan($scanId, false);

    $response->assertOk()->assertJson(['success' => true]);
    expect($response->json('message'))->toBe(
        'Please Proceed to OPD encoder to Update Incorrect Data and for Manual Log in the iHOMIS.',
    );

    expect($appointment->fresh()->status)->toBe('Completed')
        ->and($gateway->registrations)->toBeEmpty();

    $checkin = AppointmentCheckin::sole();
    expect($checkin->result)->toBe('completed')
        ->and($checkin->reason)->toBe('not-correct');
});

test('a failed homis registration never marks the appointment completed', function () {
    kioskGateway(registration: ['status' => 'error', 'enccode' => '', 'message' => 'ODBC [SQL Server] henctr failed']);
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));
    $scanId = kioskVerify($appointment)->assertOk()->json('scan_id');

    $response = kioskConfirmScan($scanId, true);

    $response->assertOk()->assertJson(['success' => false]);
    expect($response->json('message'))->toBe(
        'Unable to complete your registration in the hospital records system. Please proceed to the OPD encoder.',
    );

    // The patient never sees database internals.
    expect($response->getContent())->not->toContain('ODBC')
        ->and($response->getContent())->not->toContain('henctr');

    expect($appointment->fresh()->status)->toBe('Booked')
        ->and(session('kiosk_scan'))->toBeNull();

    $checkin = AppointmentCheckin::sole();
    expect($checkin->result)->toBe('failed')
        ->and($checkin->reason)->toStartWith('homis-registration-failed');
});

test('a finished scan cannot be confirmed twice', function () {
    $gateway = kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));
    $scanId = kioskVerify($appointment)->assertOk()->json('scan_id');

    kioskConfirmScan($scanId, true)->assertOk()->assertJson(['success' => true]);

    kioskConfirmScan($scanId, true)->assertOk()->assertJson([
        'success' => false,
        'message' => 'Please scan your QR code again.',
    ]);

    expect($gateway->registrations)->toHaveCount(1)
        ->and($appointment->fresh()->status)->toBe('Completed')
        ->and(AppointmentCheckin::count())->toBe(1);
});

test('rescanning a completed appointment is refused before any homis lookup', function () {
    $gateway = kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));
    $scanId = kioskVerify($appointment)->assertOk()->json('scan_id');
    kioskConfirmScan($scanId, true)->assertOk()->assertJson(['success' => true]);

    expectRefusal(
        kioskVerify($appointment),
        'already-completed',
        'This appointment has already been completed. Please check the schedule in your portal.',
    );

    expect($gateway->lookups)->toHaveCount(1)
        ->and(AppointmentCheckin::count())->toBe(2)
        ->and($appointment->fresh()->status)->toBe('Completed');
});

test('a fresh reservation held by another terminal refuses the scan', function () {
    kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));

    kioskVerify($appointment)->assertOk();

    // Another terminal holds no claim on this session's reservations.
    session()->forget('kiosk_reserved');

    expectRefusal(
        kioskVerify($appointment),
        'another-terminal',
        'This appointment is being processed at another kiosk. Please wait a moment.',
    );

    expect(AppointmentCheckin::where('result', 'processing')->count())->toBe(1)
        ->and(AppointmentCheckin::latest('id')->first()->patient_name)->toBe('JUAN S DELA CRUZ');
});

test('the same kiosk may re-scan its own pending reservation without duplicates', function () {
    kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));

    $firstScanId = kioskVerify($appointment)->assertOk()->json('scan_id');

    $second = kioskVerify($appointment);
    $second->assertOk()->assertJsonPath('success', true);

    $rows = AppointmentCheckin::orderBy('id')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->result)->toBe('failed')
        ->and($rows[0]->reason)->toBe('superseded')
        ->and($rows[1]->result)->toBe('processing')
        ->and($rows[1]->homis_encounter_code)->toBe($rows[0]->homis_encounter_code)
        ->and($second->json('scan_id'))->not->toBe($firstScanId);
});

test('a stale reservation from another terminal is taken over and reuses its encounter code', function () {
    $gateway = kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));
    $staleCode = '0001818'.'00012345'.now('Asia/Manila')->format('YmdHis').sprintf('%06d', $appointment->id);

    // A reservation another terminal left behind 5 minutes ago.
    AppointmentCheckin::create([
        'appointment_id' => $appointment->id,
        'scan_id' => (string) Str::uuid(),
        'result' => 'processing',
        'session_hash' => hash('sha256', 'another-terminal'),
        'homis_encounter_code' => $staleCode,
        'scanned_at' => now()->subMinutes(5),
    ]);

    $scanId = kioskVerify($appointment)->assertOk()->json('scan_id');
    kioskConfirmScan($scanId, true)->assertOk()->assertJson(['success' => true]);

    expect($gateway->registrations)->toHaveCount(1)
        ->and($gateway->registrations[0]['enccode'])->toBe($staleCode)
        ->and($appointment->fresh()->status)->toBe('Completed');

    $rows = AppointmentCheckin::orderBy('id')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->result)->toBe('failed')
        ->and($rows[0]->reason)->toBe('superseded')
        ->and($rows[1]->result)->toBe('completed')
        ->and($rows[1]->homis_encounter_code)->toBe($staleCode);
});

test('an expired scan ticket cannot be confirmed', function () {
    kioskGateway();
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));
    $scanId = kioskVerify($appointment)->assertOk()->json('scan_id');

    $this->travel((int) config('kiosk.scan_ticket_ttl') + 60)->seconds();

    kioskConfirmScan($scanId, true)->assertOk()->assertJson([
        'success' => false,
        'message' => 'Your scan has expired. Please scan your QR code again.',
    ]);

    $checkin = AppointmentCheckin::sole();
    expect($appointment->fresh()->status)->toBe('Booked')
        ->and($checkin->result)->toBe('failed')
        ->and($checkin->reason)->toBe('expired');
});

test('a retry after a failed registration reuses the same encounter code', function () {
    $gateway = kioskGateway(registration: ['status' => 'error', 'enccode' => '', 'message' => 'connection failed']);
    $this->actingAs(kioskAdmin(), 'admin');

    $appointment = kioskAppointment(makePatient(['hospital_number' => '00012345']));

    $scanId = kioskVerify($appointment)->assertOk()->json('scan_id');
    kioskConfirmScan($scanId, true)->assertOk()->assertJson(['success' => false]);

    expect($appointment->fresh()->status)->toBe('Booked');

    // HOMIS recovers; the kiosk scans again and succeeds.
    $gateway->registrationResult = ['status' => 'ok', 'enccode' => '', 'message' => ''];

    $scanId = kioskVerify($appointment)->assertOk()->json('scan_id');
    kioskConfirmScan($scanId, true)->assertOk()->assertJson(['success' => true]);

    expect($gateway->registrations)->toHaveCount(2)
        ->and($gateway->registrations[1]['enccode'])->toBe($gateway->registrations[0]['enccode'])
        ->and($appointment->fresh()->status)->toBe('Completed');
});
