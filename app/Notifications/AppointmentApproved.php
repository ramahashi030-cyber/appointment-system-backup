<?php

namespace App\Notifications;

use App\Models\Appointment;

/**
 * Sent to the patient once the triager approves the request and has picked the
 * actual service (Telemed or Face-to-Face) through the existing triage flow.
 */
class AppointmentApproved extends AppointmentNotification
{
    public function kind(): string
    {
        return 'appointment.approved';
    }

    protected function build(Appointment $appointment, array $context): array
    {
        return array_merge([
            'title' => 'Appointment Approved',
            'message' => 'Your appointment request has been approved by the triager.',
            'icon' => 'bi-check-circle-fill',
            'action_label' => 'View Appointment',
            'action_url' => route('telemed.mine'),
        ], $context);
    }
}
