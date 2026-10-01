<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private Settings $settings;

    public function __construct()
    {
        $this->settings = new Settings;
    }

    public function index(): View
    {
        $values = $this->settings->all();

        return view('admin.settings', [
            'settings' => $values,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $section = (string) $request->input('section', '');

        $validated = match ($section) {
            'hospital' => $request->validate([
                'hospital_name' => ['required', 'string', 'max:191'],
                'hospital_address' => ['nullable', 'string', 'max:500'],
                'hospital_phone' => ['nullable', 'string', 'max:20'],
                'hospital_email' => ['nullable', 'email', 'max:191'],
            ]),
            'system' => $request->validate([
                'system_name' => ['required', 'string', 'max:191'],
            ]),
            'appointment' => $request->validate([
                'appointment_duration' => ['required', 'integer', 'min:5', 'max:240'],
                'cancellation_window_hours' => ['required', 'integer', 'min:0', 'max:168'],
                'max_advance_booking_days' => ['required', 'integer', 'min:1', 'max:365'],
            ]),
            'notification' => $request->validate([
                'notification_email_enabled' => ['boolean'],
                'notification_sms_enabled' => ['boolean'],
                'notification_appointment_reminder' => ['boolean'],
                'notification_reminder_hours' => ['required', 'integer', 'min:1', 'max:72'],
            ]),
            'consultation' => $request->validate([
                'consultation_requires_approval' => ['boolean'],
                'consultation_auto_assign' => ['boolean'],
            ]),
            'maintenance' => $request->validate([
                'maintenance_mode' => ['boolean'],
                'maintenance_message' => ['nullable', 'string', 'max:500'],
            ]),
            default => [],
        };

        $this->settings->setMany($validated);

        return response()->json([
            'success' => true,
            'message' => ucfirst($section).' settings saved successfully.',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $section = (string) $request->input('section', '');

        $validated = match ($section) {
            'hospital' => $request->validate([
                'hospital_name' => ['required', 'string', 'max:191'],
                'hospital_address' => ['nullable', 'string', 'max:500'],
                'hospital_phone' => ['nullable', 'string', 'max:20'],
                'hospital_email' => ['nullable', 'email', 'max:191'],
            ]),
            'system' => $request->validate([
                'system_name' => ['required', 'string', 'max:191'],
            ]),
            'appointment' => $request->validate([
                'appointment_duration' => ['required', 'integer', 'min:5', 'max:240'],
                'cancellation_window_hours' => ['required', 'integer', 'min:0', 'max:168'],
                'max_advance_booking_days' => ['required', 'integer', 'min:1', 'max:365'],
            ]),
            'notification' => $request->validate([
                'notification_email_enabled' => ['boolean'],
                'notification_sms_enabled' => ['boolean'],
                'notification_appointment_reminder' => ['boolean'],
                'notification_reminder_hours' => ['required', 'integer', 'min:1', 'max:72'],
            ]),
            'consultation' => $request->validate([
                'consultation_requires_approval' => ['boolean'],
                'consultation_auto_assign' => ['boolean'],
            ]),
            'maintenance' => $request->validate([
                'maintenance_mode' => ['boolean'],
                'maintenance_message' => ['nullable', 'string', 'max:500'],
            ]),
            default => [],
        };

        $this->settings->setMany($validated);

        return redirect()
            ->route('admin.settings')
            ->with('success', ucfirst($section).' settings saved successfully.');
    }
}
