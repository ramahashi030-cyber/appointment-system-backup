<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Admin: create another administrator account.
 *
 * Opened from the profile menu in the top bar ("New Admin"). Accounts are
 * stored in the `admin` table with role = 'admin'. The triage_channel column
 * is left to its database default ('both'); triager accounts are managed on
 * their own page (TriagerAccountController).
 *
 * Name and password rules mirror Admin\ProfileController so both forms agree.
 */
class NewAdminController extends Controller
{
    /** Letters only, single spaces between them (same rule as the profile form). */
    private const NAME_PATTERN = '/^[A-Za-z]+(?: +[A-Za-z]+)*$/';

    /** Letters, numbers, dot, underscore and dash. */
    private const USERNAME_PATTERN = '/^[A-Za-z0-9._-]+$/';

    /** Exactly 11 digits, the same format the patient forms use. */
    private const CONTACT_PATTERN = '/^[0-9]{11}$/';

    /**
     * POST /admin/admins — create an admin account.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100', 'regex:'.self::NAME_PATTERN],
            'lastname' => ['required', 'string', 'max:100', 'regex:'.self::NAME_PATTERN],
            'username' => ['required', 'string', 'max:100', 'regex:'.self::USERNAME_PATTERN, 'unique:admin,username'],
            'email' => ['required', 'email', 'max:191', 'unique:admin,email'],
            'contact_no' => ['required', 'string', 'regex:'.self::CONTACT_PATTERN],
            'password' => [
                'required',
                Password::min(ProfileController::PASSWORD_MIN)
                    ->max(ProfileController::PASSWORD_MAX)
                    ->mixedCase()
                    ->numbers(),
                'confirmed',
                $this->requireSymbolWhenLongerThan(ProfileController::PASSWORD_SYMBOL_THRESHOLD),
            ],
        ], [
            'firstname.regex' => 'The first name may only contain letters and spaces.',
            'lastname.regex' => 'The last name may only contain letters and spaces.',
            'username.regex' => 'The username may only contain letters, numbers, dots, dashes and underscores.',
            'username.unique' => 'That username is already taken.',
            'email.unique' => 'That email address is already in use.',
            'contact_no.regex' => 'The contact number must be 11 digits (numbers only).',
            'password.min' => 'The password must be at least '.ProfileController::PASSWORD_MIN.' characters.',
            'password.max' => 'The password may not be longer than '.ProfileController::PASSWORD_MAX.' characters.',
        ]);

        // The Admin model casts `password` to hashed, so the plain value is
        // passed here and hashed exactly once on save.
        $admin = Admin::create([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'username' => $data['username'],
            'email' => $data['email'],
            'contact_no' => $data['contact_no'],
            'password' => $data['password'],
            'role' => 'admin',
        ]);

        return response()->json([
            'message' => 'New admin account created successfully.',
            'full_name' => trim($admin->firstname.' '.$admin->lastname),
        ], 201);
    }

    /**
     * Require a special character in passwords longer than the given length
     * (same behaviour as the profile password form).
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
}