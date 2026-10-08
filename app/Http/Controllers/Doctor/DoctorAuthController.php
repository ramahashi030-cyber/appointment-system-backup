<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Support\StaffDoctorSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class DoctorAuthController extends Controller
{
    public function show(): View
    {
        $this->ensureDoctorStorage();

        return view('doctor.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->ensureDoctorStorage();

        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $email = Str::lower(trim($credentials['email']));
        $throttleKey = 'doctor-login|'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => "Too many login attempts. Please try again in {$seconds} seconds."]);
        }

        $doctor = Staff::query()
            ->activeDoctors()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($doctor === null || ! Hash::check($credentials['password'], $doctor->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'The doctor email or password is incorrect.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put([
            'staff_id' => $doctor->id,
            'doctor_email' => $doctor->email,
            'doctor_name' => $this->displayName($doctor),
            'doctor_specialty' => $doctor->specialty(),
            'doctor_profile_pic' => $doctor->profile_pic
                ? (Str::startsWith($doctor->profile_pic, ['http://', 'https://']) ? $doctor->profile_pic : asset($doctor->profile_pic))
                : null,
            'user_type' => 'doctor',
        ]);

        return redirect()->intended(route('doctor.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('doctor.login')->with('status', 'You have been signed out of the doctor portal.');
    }

    private function ensureDoctorStorage(): void
    {
        StaffDoctorSchema::migrateDoctorsIntoStaff();

        if (Staff::query()->doctors()->exists()) {
            return;
        }

        Staff::query()->create([
            'username' => 'aldrin.gwapo',
            'FirstName' => 'Aldrin',
            'MiddleName' => null,
            'LastName' => 'Gwapo',
            'email' => 'aldrin.gwapo@qmmc.local',
            'contactno' => 'N/A',
            'password' => '$2y$12$LHCBRJmEZGwpl1o778vRxe2LQOqnr3ijrzY9ntRNztXOiTx4XHPum',
            'is_verified' => true,
            'is_active' => true,
            'is_doctor' => true,
            'site' => 'TELE',
        ]);
    }

    private function authenticated(): bool
    {
        $staffId = (int) session('staff_id', 0);
        $sessionEmail = Str::lower((string) session('doctor_email'));

        if (session('user_type') !== 'doctor' || $staffId < 1 || $sessionEmail === '') {
            return false;
        }

        $doctor = Staff::query()
            ->activeDoctors()
            ->whereKey($staffId)
            ->first();

        return $doctor !== null && hash_equals(Str::lower($doctor->email), $sessionEmail);
    }

    private function displayName(Staff $doctor): string
    {
        $name = Str::headline(Str::lower($doctor->full_name));

        return str_starts_with(strtolower($name), 'dr.') ? $name : 'Dr. '.$name;
    }
}
