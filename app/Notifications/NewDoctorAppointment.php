<?php

namespace App\Notifications;

use App\Models\Appointment;

/**
 * Sent to the assigned doctor when an appointment is created for them or an
 * existing appointment is assigned to them.
 */
class NewDoctorAppointment extends AppointmentNotification
{
    public function kind(): string
    {
        return 'appointment.new';
    }

    protected function build(Appointment $appointment, array $context): array
    {
        return array_merge([
            'title' => 'New Appointment',
            'message' => 'You have a new appointment requiring your attention.',
            'icon' => 'bi-person-plus-fill',
            'action_label' => 'View Appointments',
            'action_url' => route('doctor.appointments'),
        ], $context);
    }
}
