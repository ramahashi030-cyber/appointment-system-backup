<?php

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Registration + OTP (legacy register_process.php / verify.php)
|--------------------------------------------------------------------------
*/

test('the registration screen renders', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Signup page')
        ->assertSee('otpModal', false)
        ->assertSee(route('register'), false);
});

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'JUAN',
        'middlename' => 'S',
        'last_name' => 'DELA CRUZ',
        'dob' => '1990-01-15',
        'gender' => 'Male',
        'contact_number' => '09170001111',
        'hospital_number' => '',
        'address' => '',
        'username' => 'juandelacruz',
        'password' => 'secret123',
    ], $overrides);
}

function pendingRegistrationSession(array $overrides = [], string $otp = '123456', ?int $generatedAt = null): array
{
    $payload = array_merge(registrationPayload(), $overrides);

    return [
        'contact' => $payload['contact_number'],
        'data' => [
            'first_name' => $payload['first_name'],
            'middlename' => $payload['middlename'],
            'last_name' => $payload['last_name'],
            'dob' => $payload['dob'],
            'gender' => $payload['gender'],
            'contact_number' => $payload['contact_number'],
            'hospital_number' => $payload['hospital_number'] ?: null,
            'address' => $payload['address'],
            'username' => $payload['username'],
            'password' => Hash::make($payload['password']),
        ],
        'otp_hash' => Hash::make($otp),
        'otp_generated_at' => $generatedAt ?? now()->getTimestamp(),
    ];
}

test('registering stores sign-up details without creating a patient', function () {
    Http::fake();

    $this->post(route('register.store'), registrationPayload())
        ->assertRedirect(route('register.verify', ['contact' => '09170001111']))
        ->assertSessionHas('patient_registration');

    expect(Patient::where('username', 'juandelacruz')->exists())->toBeFalse();
});

test('AJAX registration returns the OTP data needed by the modal', function () {
    Http::fake();

    $this->postJson(route('register.store'), registrationPayload())
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('contact', '09170001111')
        ->assertJsonStructure([
            'message',
            'contact',
            'window',
            'sent',
        ]);

    expect(Patient::where('username', 'juandelacruz')->exists())->toBeFalse()
        ->and(session('patient_registration'))->toBeArray();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '09170001111');
    });
});

test('AJAX OTP resend sends a new code', function () {
    Http::fake();

    $registration = pendingRegistrationSession([], '123456');
    $oldOtpHash = $registration['otp_hash'];

    $this->withSession(['patient_registration' => $registration])
        ->postJson(route('register.resend'), [
            'contact' => '09170001111',
        ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('contact', '09170001111');

    expect(Patient::where('username', 'juandelacruz')->exists())->toBeFalse()
        ->and(session('patient_registration.otp_hash'))->not->toBe($oldOtpHash);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '09170001111');
    });
});

test('AJAX OTP verification creates and activates the patient', function () {
    $this->withSession([
        'patient_registration' => pendingRegistrationSession(),
    ])->postJson(route('register.verify.attempt'), [
        'contact' => '09170001111',
        'otp' => '123456',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Verified successfully! You may now sign in.');

    $patient = Patient::where('username', 'juandelacruz')->firstOrFail();

    expect($patient->status)->toBe('Active')
        ->and($patient->otp_code)->toBeNull();
});

test('a missing sign-up session returns to the registration page', function () {
    $this->get(route('register.verify', ['contact' => '09170009999']))
        ->assertRedirect(route('register'))
        ->assertSessionHas('error', 'Your sign-up session has expired. Please register again.');
});

test('the verification page does not expose the OTP in debug mode', function () {
    $this->withSession([
        'patient_registration' => pendingRegistrationSession(),
    ])->get(route('register.verify', ['contact' => '09170001111']))
        ->assertOk()
        ->assertDontSee('Debug mode', false);
});

test('registration rejects a duplicate username', function () {
    makePatient(['username' => 'juandelacruz']);

    $this->from(route('register'))
        ->post(route('register.store'), registrationPayload())
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('username', 'Username already exists');
});

test('registration rejects a contact number used too many times', function () {
    foreach (range(1, 3) as $i) {
        makePatient([
            'username' => 'reuse-'.$i,
            'contact_number' => '09170001111',
        ]);
    }

    $this->from(route('register'))
        ->post(route('register.store'), registrationPayload())
        ->assertSessionHasErrors('contact_number');
});

test('a correct OTP creates and activates the account', function () {
    $this->withSession([
        'patient_registration' => pendingRegistrationSession(),
    ])->post(route('register.verify.attempt'), ['contact' => '09170001111', 'otp' => '123456'])
        ->assertRedirect(route('auth.login'))
        ->assertSessionHas('status', 'Verified successfully! You may now sign in.');

    $patient = Patient::where('username', 'juandelacruz')->firstOrFail();

    expect($patient->status)->toBe('Active')
        ->and($patient->otp_code)->toBeNull();
});

test('the activation login works with the new password', function () {
    Patient::create([
        'first_name' => 'JUAN',
        'last_name' => 'DELA CRUZ',
        'dob' => '1990-01-15',
        'gender' => 'Male',
        'contact_number' => '09170001111',
        'username' => 'juandelacruz',
        'password' => 'secret123',
        'status' => 'Active',
    ]);

    $this->post('/login', ['username' => 'juandelacruz', 'password' => 'secret123'])
        ->assertRedirect('/telemed');
});

test('a wrong OTP does not create the patient', function () {
    $this->withSession([
        'patient_registration' => pendingRegistrationSession(),
    ])->from(route('register.verify', ['contact' => '09170001111']))
        ->post(route('register.verify.attempt'), ['contact' => '09170001111', 'otp' => '654321'])
        ->assertSessionHasErrors('otp', 'Invalid OTP. Please try again.');

    expect(Patient::where('username', 'juandelacruz')->exists())->toBeFalse()
        ->and(session('patient_registration'))->toBeArray();
});

test('an expired OTP does not create the patient', function () {
    $this->withSession([
        'patient_registration' => pendingRegistrationSession([], '123456', now()->subSeconds(120)->getTimestamp()),
    ])->post(route('register.verify.attempt'), ['contact' => '09170001111', 'otp' => '123456'])
        ->assertRedirect(route('register'))
        ->assertSessionHas('error', 'OTP expired or incorrect. Please register again.');

    expect(Patient::where('username', 'juandelacruz')->exists())->toBeFalse()
        ->and(session('patient_registration'))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Password reset
|--------------------------------------------------------------------------
*/

test('the reset password screen renders', function () {
    $this->get('/reset-password')
        ->assertOk()
        ->assertSee('Reset Password')
        ->assertSee(route('password.reset.attempt'), false);
});

test('a verified patient can reset their password', function () {
    $patient = makePatient();

    $this->post('/reset-password', [
        'identifier' => 'juan',
        'birthdate' => '01151990',
        'password' => 'brand-new-pass',
        'password_confirmation' => 'brand-new-pass',
    ])->assertRedirect(route('auth.login'))
        ->assertSessionHas('status', 'Your password has been updated. You may now sign in.');

    expect(Hash::check('brand-new-pass', $patient->fresh()->password))->toBeTrue();
});

test('reset works with the hospital number as identifier', function () {
    $patient = makePatient([
        'username' => 'HN-0001',
        'hospital_number' => 'HN-0001',
        'password' => '01151990',
    ]);

    $this->post('/reset-password', [
        'identifier' => 'HN-0001',
        'birthdate' => '01151990',
        'password' => 'brand-new-pass',
        'password_confirmation' => 'brand-new-pass',
    ])->assertRedirect(route('auth.login'));

    expect(Hash::check('brand-new-pass', $patient->fresh()->password))->toBeTrue();
});

test('reset rejects a wrong birthdate', function () {
    makePatient();

    $this->from('/reset-password')
        ->post('/reset-password', [
            'identifier' => 'juan',
            'birthdate' => '01011980',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])
        ->assertRedirect('/reset-password')
        ->assertSessionHasErrors('identifier');
});

test('reset rejects an unknown identifier', function () {
    makePatient();

    $this->post('/reset-password', [
        'identifier' => 'nobody',
        'birthdate' => '01151990',
        'password' => 'brand-new-pass',
        'password_confirmation' => 'brand-new-pass',
    ])->assertSessionHasErrors('identifier');
});
