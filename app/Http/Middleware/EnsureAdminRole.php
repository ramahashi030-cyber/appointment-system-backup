<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the logged-in admin account has the admin role (not triager).
 */
class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        if ($admin === null || $admin->role !== 'admin') {
            return redirect()->route('triager.dashboard')->with('status', 'You do not have permission to access that area.');
        }

        return $next($request);
    }
}
