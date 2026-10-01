<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * GET /patients/session-check
 *
 * Lightweight endpoint the patient pages poll. The patient.active middleware
 * does the real work: a deactivated patient gets a 401 + login redirect.
 */
class SessionCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['active' => true]);
    }
}
