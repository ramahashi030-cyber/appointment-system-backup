<?php

namespace App\Notifications;

use App\Models\Appointment;

/**
 * Sent to the other party when either side creates the Jitsi room through the
 * existing telemed workflow. The action always points at the existing room:
 * patients go through the gated join route, doctors open the meeting link the
 * doctor dashboard already uses.
 */
class TelemedRoomCreated extends AppointmentNotification
{
    public function kind(): string
    {
        return 'telemed.room_created';
    }

    protected function build(Appointment $appointment, array $context): array
    {
        return array_merge([
            'title' => 'Telemed Room Created',
            'message' => 'A Jitsi room has been created. You may now join the consultation.',
            'icon' => 'bi-camera-video-fill',
            'action_label' => 'Join Jitsi',
            'action_url' => $appointment->meeting_link ?: route('doctor.appointments'),
            'action_external' => true,
        ], $context);
    }
}
