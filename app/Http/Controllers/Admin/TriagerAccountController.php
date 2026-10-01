<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Triager account management.
 *
 * Triagers are stored in the `admin` table with role = 'triager'.
 * Admins can create, edit, and delete triager accounts from this page.
 */
class TriagerAccountController extends Controller
{
    /**
     * GET /admin/triagers — list triager accounts.
     */
    public function index(Request $request): View
    {
        $triagers = Admin::query()
            ->where('role', 'triager')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        $editTriager = null;
        $createMode = $request->has('create');

        if ($request->has('edit')) {
            $editTriager = Admin::query()
                ->where('role', 'triager')
                ->whereKey($request->integer('edit'))
                ->first();
        }

        return view('admin.triagers', [
            'triagers' => $triagers,
            'editTriager' => $editTriager,
            'createMode' => $createMode,
        ]);
    }

    /**
     * POST /admin/triagers — create a triager account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:admin,username'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'email' => ['required', 'email', 'max:191'],
            'contact_no' => ['nullable', 'string', 'max:20'],
        ]);

        Admin::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'email' => $validated['email'],
            'contact_no' => $validated['contact_no'] ?? null,
            'role' => 'triager',
        ]);

        return redirect()->route('admin.triagers')->with('success', 'Triager account created successfully.');
    }

    /**
     * PUT /admin/triagers/{admin} — update a triager account.
     */
    public function update(Request $request, Admin $admin): RedirectResponse
    {
        if ($admin->role !== 'triager') {
            return redirect()->route('admin.triagers')->with('error', 'That account is not a triager.');
        }

        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:admin,username,'.$admin->id],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'email' => ['required', 'email', 'max:191'],
            'contact_no' => ['nullable', 'string', 'max:20'],
        ]);

        $data = [
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'contact_no' => $validated['contact_no'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $admin->update($data);

        return redirect()->route('admin.triagers')->with('success', 'Triager account updated successfully.');
    }

    /**
     * DELETE /admin/triagers/{admin} — delete a triager account.
     */
    public function destroy(Admin $admin): RedirectResponse
    {
        if ($admin->role !== 'triager') {
            return redirect()->route('admin.triagers')->with('error', 'That account is not a triager.');
        }

        if (Admin::where('role', 'triager')->count() <= 1) {
            return redirect()->route('admin.triagers')->with('error', 'You cannot delete the last triager account.');
        }

        if ($admin->id === auth('admin')->id()) {
            return redirect()->route('admin.triagers')->with('error', 'You cannot delete your own account.');
        }

        $admin->delete();

        return redirect()->route('admin.triagers')->with('success', 'Triager account deleted successfully.');
    }
}
