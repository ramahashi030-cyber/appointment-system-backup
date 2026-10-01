<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the logged-in admin account has the triager role.
 */
class EnsureTriagerRole
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        if ($admin === null || $admin->role !== 'triager') {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
