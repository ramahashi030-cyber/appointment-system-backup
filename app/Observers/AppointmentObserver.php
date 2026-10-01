<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\AuditLog;

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
    }

    public function updated(Appointment $appointment): void
    {
        $action = 'Update';

        if ($appointment->wasChanged('status')) {
            $action = self::STATUS_ACTIONS[$appointment->status] ?? "Status: {$appointment->status}";
        }

        AuditLog::record($action, self::MODULE, $appointment->id);
    }

    public function deleted(Appointment $appointment): void
    {
        AuditLog::record('Delete', self::MODULE, $appointment->id);
    }
}
