<?php

namespace App\Notifications;

use App\Models\Appointment;

/**
 * Sent when the existing workflow stores the service, date and time slot.
 */
class AppointmentScheduled extends AppointmentNotification
{
    public function kind(): string
    {
        return 'appointment.scheduled';
    }

    protected function build(Appointment $appointment, array $context): array
    {
        return array_merge([
            'title' => 'Appointment Scheduled',
            'message' => 'Your appointment has been scheduled.',
            'icon' => 'bi-calendar-check-fill',
            'action_label' => 'View Appointment',
            'action_url' => route('telemed.mine'),
        ], $context);
    }
}
