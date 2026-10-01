<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Support\Homis;
use App\Support\Sms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Registration + OTP activation + forced password change.
 *
 * Port of QALINGA1 register.php, register_process.php, verify.php and
 * register3.php (the screen patients land on after login_process.php decides
 * they are new or still using default credentials).
 */
class RegisterController extends Controller
{
    private const REGISTRATION_SESSION_KEY = 'patient_registration';

    /**
     * GET /register
     *
     * ?hn=XXXX            → prefill from HOMIS (legacy register3.php?hn=)
     * ?force_update=1     → change default credentials (legacy register3.php?force_update=1)
     */
    public function show(Request $request)
    {
        if ($request->query('force_update') && session('patient_id')) {
            return redirect()->route('password.force');
        }

        $hn = trim((string) $request->query('hn', ''));
        $person = null;
        $address = '';
        $notice = null;

        if ($hn !== '') {
            $profile = Homis::profile($hn);

            if ($profile === null) {
                $notice = 'HOMIS could not be reached, so the form was left blank. You can still fill it in manually.';
            } else {
                $person = $profile['person'];
                $address = $profile['address'];
            }
        }

        return view('auth.patient/register', [
            'hn' => $hn,
            'person' => $person,
            'address' => $address,
            'notice' => $notice,
        ]);
    }

    /**
     * POST /register — temporarily stores the sign-up details and asks for the
     * OTP. The patient row is created only after the OTP is verified.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'dob' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'in:Male,Female'],
            'contact_number' => ['required', 'regex:/^[0-9]{11}$/'],
            'hospital_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:patients,username'],
            'password' => ['required', 'string', 'min:4', 'max:60'],
        ], [
            'contact_number.regex' => 'The cellphone number must be an 11-digit number.',
            'username.unique' => 'Username already exists',
        ]);

        $contactNumber = trim($data['contact_number']);

        // Legacy guard: one contact number may only be used a few times.
        if (Patient::where('contact_number', $contactNumber)->count() > 2) {
            $message = 'The cellphone number you provided has already reached the maximum number of patient registrations. Please use a different contact number or register directly at the QMMC-OPD kiosk.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['contact_number' => [$message]],
                ], 422);
            }

            return back()
                ->withErrors(['contact_number' => $message])
                ->withInput();
        }

        $registration = [
            'contact' => $contactNumber,
            'data' => [
                'first_name' => trim($data['first_name']),
                'middlename' => trim($data['middlename'] ?? ''),
                'last_name' => trim($data['last_name']),
                'dob' => $data['dob'],
                'gender' => $data['gender'],
                'contact_number' => $contactNumber,
                'hospital_number' => trim($data['hospital_number'] ?? '') ?: null,
                'address' => trim($data['address'] ?? ''),
                'username' => trim($data['username']),
                'password' => Hash::make($data['password']),
            ],
            'otp_hash' => null,
            'otp_generated_at' => null,
        ];

        $otp = $this->storeRegistrationOtp($registration);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your sign-up details are ready. Enter the OTP we sent to your cellphone number.',
                'contact' => $contactNumber,
                'window' => $this->window(),
                'sent' => $otp['sent'],
            ]);
        }

        return redirect()
            ->route('register.verify', ['contact' => $contactNumber])
            ->with([
                'status' => 'Your sign-up details are ready. Enter the OTP we sent to your cellphone number.',
                'otp_sent' => $otp['sent'],
            ]);
    }

    /**
     * GET /register/verify — shows the temporary sign-up OTP screen.
     */
    public function showVerify(Request $request)
    {
        $contact = trim((string) $request->query('contact', ''));

        if ($contact === '') {
            return redirect()->route('register');
        }

        $registration = $this->pendingRegistration($contact);

        if ($registration === null) {
            return redirect()->route('register')
                ->with('error', 'Your sign-up session has expired. Please register again.');
        }

        $otp = $this->ensureRegistrationOtp($registration);

        return view('auth.patient.verify', [
            'contact' => $contact,
            'sent' => (bool) session('otp_sent', $otp['sent']),
            'window' => $this->window(),
        ]);
    }

    /**
     * POST /register/verify — activates the account.
     */
    public function verify(Request $request)
    {
        $result = $this->verifyOtp(
            trim((string) $request->input('contact', '')),
            trim((string) $request->input('otp', '')),
        );

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            return redirect()
                ->route('auth.login')
                ->with('status', $result['message']);
        }

        if (isset($result['redirect'])) {
            return redirect($result['redirect'])
                ->with('error', $result['message']);
        }

        return back()->withErrors($result['errors']);
    }

    /**
     * POST /register/resend — new OTP for the same temporary registration.
     */
    public function resend(Request $request)
    {
        $contact = trim((string) $request->input('contact', ''));
        $registration = $this->pendingRegistration($contact);

        if ($registration === null) {
            $message = 'Your sign-up session has expired. Please register again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['contact' => [$message]],
                ], 422);
            }

            return redirect()->route('register')->with('error', $message);
        }

        $otp = $this->storeRegistrationOtp($registration);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'A new OTP was sent to your cellphone number.',
                'contact' => $contact,
                'window' => $this->window(),
                'sent' => $otp['sent'],
            ]);
        }

        return redirect()
            ->route('register.verify', ['contact' => $contact])
            ->with('status', 'A new OTP was sent to your cellphone number.');
    }

    /**
     * GET /register/update-password — replace default credentials.
     * (legacy register3.php?force_update=1)
     */
    public function showUpdatePassword(Request $request)
    {
        $patient = $this->currentPatient();

        if ($patient === null) {
            return redirect()->route('auth.login');
        }

        return view('auth.patient.update-password', ['patient' => $patient]);
    }

    /**
     * POST /register/update-password
     */
    public function updatePassword(Request $request)
    {
        $patient = $this->currentPatient();

        if ($patient === null) {
            return redirect()->route('auth.login');
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:patients,username,'.$patient->id],
            'password' => ['required', 'string', 'min:6', 'max:60', 'confirmed'],
        ], [
            'username.unique' => 'Username already exists',
            'password.min' => 'Please choose a password of at least 6 characters.',
        ]);

        $patient->forceFill([
            'username' => trim($data['username']),
            'password' => $data['password'],
        ])->save();

        session(['username' => $patient->username]);

        return redirect()
            ->to('/telemed')
            ->with('status', 'Your username and password have been updated.');
    }

    /**
     * Generate and send an OTP for a temporary registration. The patient is not
     * inserted into the database until the OTP is verified.
     *
     * @param  array{contact: string, data: array<string, mixed>, otp_hash: ?string, otp_generated_at: ?int}  $registration
     * @return array{sent: bool}
     */
    private function storeRegistrationOtp(array $registration): array
    {
        $otp = (string) random_int(100000, 999999);
        $registration['otp_hash'] = Hash::make($otp);
        $registration['otp_generated_at'] = now()->getTimestamp();

        session()->put(self::REGISTRATION_SESSION_KEY, $registration);

        $sent = Sms::sendOtp($registration['contact'], $otp);

        Log::info('Registration OTP generated', [
            'contact' => $registration['contact'],
            'sent' => $sent,
        ]);

        return ['sent' => $sent];
    }

    /**
     * @return array{contact: string, data: array<string, mixed>, otp_hash: ?string, otp_generated_at: ?int}|null
     */
    private function pendingRegistration(string $contact): ?array
    {
        $registration = session(self::REGISTRATION_SESSION_KEY);

        if (! is_array($registration)
            || ($registration['contact'] ?? null) !== $contact
            || ! is_array($registration['data'] ?? null)) {
            return null;
        }

        return $registration;
    }

    /**
     * @param  array{contact: string, data: array<string, mixed>, otp_hash: ?string, otp_generated_at: ?int}  $registration
     * @return array{sent: bool}
     */
    private function ensureRegistrationOtp(array $registration): array
    {
        $generatedAt = (int) ($registration['otp_generated_at'] ?? 0);
        $isFresh = ! empty($registration['otp_hash'])
            && $generatedAt >= now()->subSeconds($this->window())->getTimestamp();

        if ($isFresh) {
            return ['sent' => true];
        }

        return $this->storeRegistrationOtp($registration);
    }

    /**
     * @return array{success: bool, message: string, redirect?: string, errors?: array<string, array<int, string>>}
     */
    private function verifyOtp(string $contact, string $otp): array
    {
        if ($contact === '' || $otp === '') {
            $message = 'Contact number and OTP are required.';

            return [
                'success' => false,
                'message' => $message,
                'redirect' => route('register'),
                'errors' => ['otp' => [$message]],
            ];
        }

        $registration = $this->pendingRegistration($contact);

        if ($registration === null) {
            $message = 'Your sign-up session has expired. Please register again.';

            return [
                'success' => false,
                'message' => $message,
                'redirect' => route('register'),
                'errors' => ['otp' => [$message]],
            ];
        }

        $otpHash = (string) ($registration['otp_hash'] ?? '');
        $otpMatches = $otpHash !== '' && Hash::check($otp, $otpHash);
        $generatedAt = (int) ($registration['otp_generated_at'] ?? 0);
        $isFresh = $generatedAt >= now()->subSeconds($this->window())->getTimestamp();

        if ($otpMatches && $isFresh) {
            if (Patient::where('username', $registration['data']['username'])->exists()) {
                session()->forget(self::REGISTRATION_SESSION_KEY);

                $message = 'That username is already registered. Please register again.';

                return [
                    'success' => false,
                    'message' => $message,
                    'redirect' => route('register'),
                    'errors' => ['username' => [$message]],
                ];
            }

            $this->createPatientFromRegistration($registration);
            session()->forget(self::REGISTRATION_SESSION_KEY);

            return [
                'success' => true,
                'message' => 'Verified successfully! You may now sign in.',
                'redirect' => route('auth.login'),
            ];
        }

        if ($otpMatches) {
            session()->forget(self::REGISTRATION_SESSION_KEY);

            $message = 'OTP expired or incorrect. Please register again.';

            return [
                'success' => false,
                'message' => $message,
                'redirect' => route('register'),
                'errors' => ['otp' => [$message]],
            ];
        }

        $message = 'Invalid OTP. Please try again.';

        return [
            'success' => false,
            'message' => $message,
            'errors' => ['otp' => [$message]],
        ];
    }

    /**
     * Create the patient only after successful OTP verification.
     *
     * @param  array{contact: string, data: array<string, mixed>, otp_hash: ?string, otp_generated_at: ?int}  $registration
     */
    private function createPatientFromRegistration(array $registration): Patient
    {
        return Patient::create(array_merge($registration['data'], ['status' => 'Active']));
    }

    private function currentPatient(): ?Patient
    {
        $id = (int) session('patient_id', 0);

        return $id > 0 ? Patient::find($id) : null;
    }

    private function window(): int
    {
        return max(30, (int) config('services.otp.window', 60));
    }
}
