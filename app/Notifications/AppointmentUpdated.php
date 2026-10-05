<?php

namespace App\Notifications;

use App\Models\Appointment;

/**
 * Sent to the patient when the triager records an action other than approval
 * (deny, suspend, referral, ...) together with the triager's remarks.
 */
class AppointmentUpdated extends AppointmentNotification
{
    public function kind(): string
    {
        return 'appointment.updated';
    }

    protected function build(Appointment $appointment, array $context): array
    {
        return array_merge([
            'title' => 'Appointment Update',
            'message' => 'Your appointment request has been updated by the triager.',
            'icon' => 'bi-pencil-square',
            'action_label' => 'View Appointment',
            'action_url' => route('telemed.mine'),
        ], $context);
    }
}
