<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDoctor
{
    public function handle(Request $request, Closure $next): Response
    {
        $staffId = (int) session('staff_id', 0);
        $sessionEmail = Str::lower((string) session('doctor_email'));
        $doctor = $staffId > 0
            ? Staff::query()->activeDoctors()->whereKey($staffId)->first()
            : null;

        if (
            session('user_type') !== 'doctor'
            || $doctor === null
            || $sessionEmail === ''
            || ! hash_equals(Str::lower($doctor->email), $sessionEmail)
        ) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please sign in to the doctor portal.'], 401);
            }

            return redirect()->guest(route('doctor.login'));
        }

        return $next($request);
    }
}
