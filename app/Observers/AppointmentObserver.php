<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Support\Notifications\AppointmentNotifier;

class AppointmentObserver
{
    private const MODULE = 'Appointments';

    private const STATUS_ACTIONS = [
        'Approved' => 'Approve',
        'Rejected' => 'Reject',
        'Confirmed' => 'Confirm',
        'Cancelled' => 'Cancel',
        'In Progress' => 'Start',
        'Completed' => 'Complete',
    ];

    public function created(Appointment $appointment): void
    {
        AuditLog::record('Create', self::MODULE, $appointment->id);

        // Real-time notifications are attached after the existing audit entry.
        // They never alter how the appointment itself is stored.
        $notifier = app(AppointmentNotifier::class);

        if ((string) $appointment->triager_status === 'Pending') {
            $notifier->requestSubmitted($appointment);
        }

        if ($appointment->staff_id !== null) {
            $notifier->assigned($appointment);
        }
    }

    public function updated(Appointment $appointment): void
    {
        $action = 'Update';

        if ($appointment->wasChanged('status')) {
            $action = self::STATUS_ACTIONS[$appointment->status] ?? "Status: {$appointment->status}";
        }

        AuditLog::record($action, self::MODULE, $appointment->id);

        $notifier = app(AppointmentNotifier::class);

        if ($appointment->wasChanged('status') && $appointment->status === 'Cancelled') {
            $notifier->cancelled($appointment);
        }

        if ($appointment->wasChanged('staff_id') && $appointment->staff_id !== null) {
            $notifier->assigned($appointment);
        }
    }

    public function deleted(Appointment $appointment): void
    {
        AuditLog::record('Delete', self::MODULE, $appointment->id);
    }
}
