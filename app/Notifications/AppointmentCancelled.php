<?php

namespace App\Notifications;

use App\Models\Appointment;

/**
 * Sent to the patient, the triage team and the assigned doctor when an
 * appointment is cancelled. The cancellation reason is only shown when the
 * existing system actually stored one.
 */
class AppointmentCancelled extends AppointmentNotification
{
    public function kind(): string
    {
        return 'appointment.cancelled';
    }

    protected function build(Appointment $appointment, array $context): array
    {
        return array_merge([
            'title' => 'Appointment Cancelled',
            'message' => 'Your appointment has been cancelled.',
            'icon' => 'bi-x-circle-fill',
            'reason' => filled($appointment->cancellation_reason)
                ? (string) $appointment->cancellation_reason
                : null,
            'action_label' => 'View Appointment',
            'action_url' => route('telemed.mine'),
        ], $context);
    }
}
