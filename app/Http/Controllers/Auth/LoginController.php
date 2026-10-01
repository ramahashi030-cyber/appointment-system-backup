<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Service;
use App\Support\Homis;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Unified patient and administrator sign-in.
 *
 * Administrators are checked first using the dedicated `admin` table. When
 * the credentials do not match an administrator, the patient login flow runs.
 *
 *  - hospital number + birthdate (MMDDYYYY) verified against HOMIS (patients only)
 *  - "not yet registered" hospital numbers are sent to the register screen
 *  - default credentials (username = hospital number, password = birthdate)
 *    force a password change
 *  - the optional tscode deep link picks the service on the booking page
 */
class LoginController extends Controller
{
    /**
     * GET / — the login screen.
     */
    public function show(Request $request): View
    {
        // legacy login.php?data=<base64 "tscode=..."> service deep link
        if ($data = (string) $request->query('data', '')) {
            parse_str(base64_decode($data), $params);

            if (! empty($params['tscode'])) {
                session(['tscode' => $params['tscode']]);
            }
        }

        return view('auth.patient/login');
    }

    /**
     * POST /login — same branching as legacy login_process.php.
     */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:100'],
            'use_hospital' => ['nullable', 'in:0,1'],
        ], [
            'username.required' => 'Please enter your username.',
            'password.required' => 'Please enter your password.',
        ]);

        $username = trim($data['username']);
        $password = $data['password'];

        if (($data['use_hospital'] ?? '0') === '1') {
            return $this->loginWithHospitalNumber($request, $username, $password);
        }

        return $this->loginWithCredentials($request, $username, $password);
    }

    /**
     * GET /logout — clears the patient session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $patientId = $request->session()->get('patient_id');

        if ($patientId && $request->session()->get('user_type') === 'patient') {
            AuditLog::recordPatient('Logout', (int) $patientId, (string) $request->session()->get('username', ''));
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login')->with('status', 'You have been signed out.');
    }

    /**
     * POST /admin/logout — clears the administrator session.
     */
    public function adminLogout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->forget([
            'patient_id',
            'staff_id',
            'admin_id',
            'admin_name',
            'firstname',
            'lastname',
            'email',
            'contact_no',
            'doctor_email',
            'doctor_name',
            'doctor_specialty',
            'tscode',
            'user_type',
        ]);
        $request->session()->regenerateToken();

        return redirect()->route('auth.login')->with('status', 'You have been signed out of the admin panel.');
    }

    /**
     * Hospital number + birthdate (MMDDYYYY) mode for patient accounts.
     */
    private function loginWithHospitalNumber(Request $request, string $hospitalNumber, string $birthdate): RedirectResponse
    {
        $patient = Patient::where('hospital_number', $hospitalNumber)->first(); // legacy $msqlcount
        $verified = Homis::verifyBirthdate($hospitalNumber, $birthdate);

        if ($verified === null) {
            // HOMIS unreachable — fall back to the local patients record so the
            // portal still works when the hospital link is down.
            if ($patient === null || $patient->dob === null) {
                return back()
                    ->withErrors(['login' => 'The hospital records system (HOMIS) is unreachable, so this hospital number cannot be verified right now. Please sign in with your username and password.'])
                    ->onlyInput('username');
            }

            $verified = $patient->dob->format('mdY') === $birthdate;
        }

        if (! $verified) {
            return back()
                ->withErrors(['login' => 'Hospital number and Birthdate combination is not correct.'])
                ->onlyInput('username');
        }

        // Known in patients → sign in; otherwise this is a first-time patient.
        if ($patient === null) {
            return redirect()->route('register', ['hn' => $hospitalNumber]);
        }

        // A deactivated (non-Active) account must not be able to sign back in.
        if ($patient->status !== 'Active') {
            return back()
                ->withErrors(['login' => 'Your account has been deactivated'])
                ->onlyInput('username');
        }

        return $this->signInPatient($request, $patient);
    }

    /**
     * Username + password mode for the unified patient/admin login.
     */
    private function loginWithCredentials(Request $request, string $username, string $password): RedirectResponse
    {
        $admin = Admin::where('username', $username)->first();

        if ($admin !== null && Hash::check($password, $admin->password)) {
            return $this->signInAdmin($request, $admin);
        }

        $patient = Patient::where('username', $username)->first();

        if ($patient === null || ! Hash::check($password, $patient->password)) {
            return back()
                ->withErrors(['login' => 'Invalid username or password'])
                ->onlyInput('username');
        }

        if ($patient->status !== 'Active') {
            return back()
                ->withErrors(['login' => 'Your account has been deactivated'])
                ->onlyInput('username');
        }

        // Default credentials (username = hospital number, password = birthdate)
        // must be replaced before the portal can be used — legacy register3.php?force_update=1.
        if ($this->hasDefaultCredentials($patient)) {
            $this->startPatientSession($patient);

            return redirect()->route('password.force');
        }

        return $this->signInPatient($request, $patient);
    }

    /**
     * Session + post-login redirect for a patient (legacy $_SESSION['patient_id'] + tscode map).
     */
    private function signInPatient(Request $request, Patient $patient): RedirectResponse
    {
        $this->startPatientSession($patient);

        $redirect = '/telemed';

        if ($tscode = session('tscode')) {
            session()->forget('tscode');

            $service = Service::where('homis_code', $tscode)->first();

            if ($service !== null) {
                $redirect = '/telemed/book?service_id='.$service->id.'&tsid='.$service->id;
            }
        }

        return redirect()->to($redirect);
    }

    private function signInAdmin(Request $request, Admin $admin): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $request->session()->forget([
            'patient_id',
            'staff_id',
            'admin_id',
            'admin_name',
            'firstname',
            'lastname',
            'email',
            'contact_no',
            'doctor_email',
            'doctor_name',
            'doctor_specialty',
            'tscode',
        ]);

        session(['user_type' => 'admin']);

        if ($admin->role === 'triager') {
            return redirect()->route('triager.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }

    private function startPatientSession(Patient $patient): void
    {
        Auth::guard('admin')->logout();
        request()->session()->regenerate();
        request()->session()->forget([
            'admin_id',
            'admin_name',
            'firstname',
            'lastname',
            'email',
            'contact_no',
            'staff_id',
            'doctor_email',
            'doctor_name',
            'doctor_specialty',
        ]);

        session([
            'patient_id' => $patient->id,
            'username' => $patient->username,
            'user_type' => 'patient',
        ]);

        AuditLog::recordPatient('Login', (int) $patient->id, (string) $patient->username);
    }

    /**
     * username === hospital_number AND password === birthdate (MMDDYYYY).
     */
    private function hasDefaultCredentials(Patient $patient): bool
    {
        if (empty($patient->hospital_number) || $patient->dob === null) {
            return false;
        }

        if ($patient->username !== $patient->hospital_number) {
            return false;
        }

        return Hash::check($patient->dob->format('mdY'), $patient->password);
    }
}
