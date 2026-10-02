<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
        ];

        if ($this->hasMiddleName()) {
            $rules['middlename'] = ['nullable', 'string', 'max:100'];
        }

        $data = $request->validate($rules);

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
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed', 'different:current_password'],
        ]);

        $admin->forceFill(['password' => Hash::make($data['password'])])->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    private function hasMiddleName(): bool
    {
        return Schema::hasColumn('admin', 'middlename');
    }
}