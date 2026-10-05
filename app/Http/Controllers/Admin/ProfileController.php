<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

/**
 * Admin: own profile (name and password).
 *
 * Works on the signed-in administrator only (auth:admin guard), so there is
 * no id in the URL and no way to edit another account from here.
 *
 * The `middlename` field is only used when that column exists on the `admin`
 * table, so this controller never touches the schema.
 */
class ProfileController extends Controller
{
    /**
     * First and last name accept letters only. Spaces are kept so compound
     * names ("Mary Ann", "Dela Cruz") stay possible, while leading, trailing
     * and repeated spaces are rejected to keep stored values clean.
     */
    private const NAME_PATTERN = '/^[A-Za-z]+(?: +[A-Za-z]+)*$/';

    /** Shortest password the profile form accepts. */
    public const PASSWORD_MIN = 6;

    /** Longest password the profile form accepts. */
    public const PASSWORD_MAX = 15;

    /**
     * A password needs a special character only once it is longer than this.
     * Up to eight characters an uppercase + lowercase + number combination is
     * enough, which keeps short but still mixed passwords usable.
     */
    public const PASSWORD_SYMBOL_THRESHOLD = 8;

    /**
     * GET /admin/profile — the profile is a modal in the top bar now, so a
     * direct visit (for example an old bookmark) just goes to the dashboard.
     */
    public function show(): RedirectResponse
    {
        return redirect()->route('admin.dashboard');
    }

    /**
     * PUT /admin/profile — change first, middle and last name.
     */
    public function update(Request $request): JsonResponse
    {
        $admin = $request->user('admin');

        $rules = [
            'firstname' => ['required', 'string', 'max:100', 'regex:'.self::NAME_PATTERN],
            'lastname' => ['required', 'string', 'max:100', 'regex:'.self::NAME_PATTERN],
        ];

        $messages = [
            'firstname.regex' => 'The first name may only contain letters and spaces.',
            'lastname.regex' => 'The last name may only contain letters and spaces.',
        ];

        if ($this->hasMiddleName()) {
            $rules['middlename'] = ['nullable', 'string', 'max:100'];
        }

        $data = $request->validate($rules, $messages);

        $attributes = [
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
        ];

        if ($this->hasMiddleName()) {
            $attributes['middlename'] = $data['middlename'] ?? null;
        }

        $admin->forceFill($attributes)->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'full_name' => trim($admin->firstname.' '.$admin->lastname),
        ]);
    }

    /**
     * PUT /admin/profile/password — change the password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $admin = $request->user('admin');

        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password:admin'],
            'password' => [
                'required',
                Password::min(self::PASSWORD_MIN)->max(self::PASSWORD_MAX)->mixedCase()->numbers(),
                'confirmed',
                'different:current_password',
                $this->requireSymbolWhenLongerThan(self::PASSWORD_SYMBOL_THRESHOLD),
            ],
        ], [
            'password.min' => 'The password must be at least '.self::PASSWORD_MIN.' characters.',
            'password.max' => 'The password may not be longer than '.self::PASSWORD_MAX.' characters.',
        ]);

        $admin->forceFill(['password' => Hash::make($data['password'])])->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    /**
     * Build the closure that requires a special character in passwords longer
     * than the given length. Characters are counted the same way the framework
     * counts them for the `Password` rule: spaces, symbols and punctuation.
     */
    private function requireSymbolWhenLongerThan(int $length): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($length): void {
            if (! is_string($value)) {
                return;
            }

            if (strlen($value) > $length && preg_match('/\p{Z}|\p{S}|\p{P}/u', $value) !== 1) {
                $fail('The password must contain at least one special character.');
            }
        };
    }

    private function hasMiddleName(): bool
    {
        return Schema::hasColumn('admin', 'middlename');
    }
}
