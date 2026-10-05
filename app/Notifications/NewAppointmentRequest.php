<?php

namespace App\Notifications;

use App\Models\Appointment;

/**
 * Sent to the triage team when a patient submits a new appointment request.
 */
class NewAppointmentRequest extends AppointmentNotification
{
    public function kind(): string
    {
        return 'request.new';
    }

    protected function build(Appointment $appointment, array $context): array
    {
        return array_merge([
            'title' => 'New Appointment Request',
            'message' => 'A new patient appointment request requires processing.',
            'icon' => 'bi-inbox-fill',
            'action_label' => 'Open Triage Queue',
            'action_url' => route('triager.dashboard'),
        ], $context);
    }
}
