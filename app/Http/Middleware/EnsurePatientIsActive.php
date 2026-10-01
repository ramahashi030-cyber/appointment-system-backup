<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\Patient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs a patient out as soon as their account is no longer Active
 * (deactivated by an admin, or removed). Requests without a patient
 * session pass through untouched so existing controller checks, admin
 * and doctor sessions keep working exactly as before.
 */
class EnsurePatientIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $patientId = $session->get('patient_id');

        if ($session->get('user_type') !== 'patient' || ! $patientId) {
            return $next($request);
        }

        $status = Patient::query()->whereKey($patientId)->value('status');

        if ($status === 'Active') {
            return $next($request);
        }

        try {
            AuditLog::recordPatient('Logout', (int) $patientId, (string) $session->get('username', ''));
        } catch (\Throwable $exception) {
            report($exception);
        }

        $session->invalidate();
        $session->regenerateToken();

        $message = 'Your account has been deactivated. Please contact the administrator.';

        // Same flash that redirect()->withErrors() sets, so the login page shows
        // the message for both normal and AJAX-triggered sign-outs.
        $session->flash('errors', (new ViewErrorBag)->put('default', new MessageBag(['login' => $message])));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'redirect' => route('auth.login'),
            ], 401);
        }

        return redirect()->route('auth.login');
    }
}
