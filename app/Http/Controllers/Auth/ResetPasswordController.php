<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

/**
 * Password recovery.
 *
 * The legacy app has no reset screen — patients recover access with the same
 * two facts login_process.php already trusts: username (or hospital number)
 * and birthdate in MMDDYYYY format.
 */
class ResetPasswordController extends Controller
{
    /**
     * GET /reset-password
     */
    public function show()
    {
        return view('auth.patient/reset-password');
    }

    /**
     * POST /reset-password
     */
    public function reset(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:100'],
            'birthdate' => ['required', 'digits:8', 'numeric'],
            'password' => ['required', 'string', 'min:6', 'max:60', 'confirmed'],
        ], [
            'birthdate.digits' => 'Birthdate must be entered as MMDDYYYY (8 digits).',
            'password.min' => 'Please choose a password of at least 6 characters.',
        ]);

        $identifier = trim($data['identifier']);

        $patient = Patient::where('username', $identifier)
            ->orWhere('hospital_number', $identifier)
            ->first();

        if ($patient === null || $patient->dob === null || $patient->dob->format('mdY') !== $data['birthdate']) {
            return back()
                ->withErrors(['identifier' => 'We could not verify your details. Check your username (or hospital number) and birthdate.'])
                ->onlyInput('identifier');
        }

        $patient->forceFill(['password' => $data['password']])->save();

        return redirect()
            ->route('auth.login')
            ->with('status', 'Your password has been updated. You may now sign in.');
    }
}
