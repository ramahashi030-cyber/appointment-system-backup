<?php

namespace App\Support\Notifications;

use App\Models\Admin;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Staff;
use App\Models\UserNotification;
use App\Notifications\AppointmentApproved;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentNotification;
use App\Notifications\AppointmentScheduled;
use App\Notifications\AppointmentUpdated;
use App\Notifications\NewAppointmentRequest;
use App\Notifications\NewDoctorAppointment;
use App\Notifications\TelemedRoomCreated;
use App\Support\AppointmentJitsiRoom;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Single place where real-time notifications are attached to the existing
 * appointment workflow.
 *
 * Nothing in here changes how appointments move through the system — every
 * method is called *after* the existing action has already succeeded, and every
 * call is wrapped so that a broken WebSocket server (or a missing table) can
 * never break the appointment flow itself.
 */
final class AppointmentNotifier
{
    /**
     * A patient submitted a request — the triage queue has new work.
     */
    public function requestSubmitted(Appointment $appointment): void
    {
        $this->run(function () use ($appointment): void {
            $this->deliver($this->triagers(), new NewAppointmentRequest($appointment));
        });
    }

    /**
     * The triager approved the request.
     *
     * The approved service only exists once the triager has picked it in the
     * existing service-selection step, so a later call refreshes the same
     * notification with the real service name instead of creating a duplicate.
     */
    public function approved(Appointment $appointment): void
    {
        $this->run(function () use ($appointment): void {
            $patient = $appointment->patient;

            if ($patient === null) {
                return;
            }

            $existing = $this->existingApproval($patient, $appointment->id);

            if ($existing !== null) {
                $data = array_merge($existing->data, [
                    'service' => AppointmentNotification::serviceFor($appointment),
                    'date' => $appointment->date?->format('F j, Y'),
                    'time' => $appointment->time_slot ?: null,
                ]);

                $existing->forceFill(['data' => $data])->save();

                return;
            }

            $this->deliver([$patient], new AppointmentApproved($appointment));
        });
    }

    /**
     * The service, date and time slot were stored by the existing workflow.
     */
    public function scheduled(Appointment $appointment): void
    {
        $this->run(function () use ($appointment): void {
            $recipients = [];

            if ($appointment->patient !== null) {
                $recipients[] = $appointment->patient;
            }

            foreach ($this->assignedDoctors($appointment) as $doctor) {
                $recipients[] = $doctor;
            }

            $this->deliver($recipients, new AppointmentScheduled($appointment, [
                'message' => 'Your appointment has been scheduled.',
                'action_label' => 'View Appointment',
                'action_url' => Route::has('telemed.mine') ? route('telemed.mine') : null,
            ]));
        });
    }

    /**
     * The triager recorded an action other than approval (deny, suspend, ...).
     */
    public function triageAction(Appointment $appointment, string $action, ?string $remarks = null): void
    {
        $this->run(function () use ($appointment, $action, $remarks): void {
            $patient = $appointment->patient;

            if ($patient === null) {
                return;
            }

            $message = 'The triager recorded an action on your appointment request: '.$action.'.';

            if (filled($remarks)) {
                $message .= ' Remarks: '.$remarks;
            }

            $this->deliver([$patient], new AppointmentUpdated($appointment, [
                'message' => $message,
            ]));
        });
    }

    /**
     * An appointment was cancelled — the patient is told, and so is the doctor
     * who has to drop the visit from their day.
     *
     * Who else is told depends on who cancelled:
     *
     *  - a patient cancellation goes to the doctor only (with the reason the
     *    patient gave) — it never lands in the triage queue;
     *  - any other cancellation keeps the existing behaviour, triage queue
     *    included.
     */
    public function cancelled(Appointment $appointment, ?string $initiatedBy = null): void
    {
        $this->run(function () use ($appointment, $initiatedBy): void {
            $cancelledByPatient = $initiatedBy === 'patient';
            $reason = filled($appointment->cancellation_reason)
                ? trim((string) $appointment->cancellation_reason)
                : null;

            if ($appointment->patient !== null) {
                $this->deliver([$appointment->patient], new AppointmentCancelled($appointment, [
                    'message' => 'Your appointment has been cancelled.',
                ]));
            }

            if (! $cancelledByPatient) {
                $this->deliver($this->triagers(), new AppointmentCancelled($appointment, [
                    'message' => 'A patient has cancelled an appointment.',
                    'action_label' => 'Open Triage Queue',
                    'action_url' => Route::has('triager.dashboard') ? route('triager.dashboard') : null,
                ]));
            }

            $doctorMessage = $cancelledByPatient
                ? 'A patient has cancelled an appointment.'
                : 'An appointment has been cancelled.';

            if ($reason !== null) {
                $doctorMessage .= ' Reason: '.$reason;
            }

            $this->deliver($this->doctorRecipients($appointment), new AppointmentCancelled($appointment, [
                'message' => $doctorMessage,
                'action_label' => 'View Appointments',
                'action_url' => Route::has('doctor.appointments') ? route('doctor.appointments') : null,
            ]));
        });
    }

    /**
     * The patient entered the consultation room, so the appointment is now
     * completed on both dashboards. The assigned doctor hears it immediately
     * instead of discovering it on the next page load.
     */
    public function consultationJoined(Appointment $appointment): void
    {
        $this->run(function () use ($appointment): void {
            $this->deliver($this->doctorRecipients($appointment), new AppointmentUpdated($appointment, [
                'message' => 'The patient has joined the consultation. The appointment is now completed.',
                'action_label' => 'View Appointments',
                'action_url' => Route::has('doctor.appointments') ? route('doctor.appointments') : null,
            ]));
        });
    }

    /**
     * An appointment was created for (or assigned to) a doctor.
     */
    public function assigned(Appointment $appointment): void
    {
        $this->run(function () use ($appointment): void {
            $this->deliver($this->assignedDoctors($appointment), new NewDoctorAppointment($appointment));
        });
    }

    /**
     * Either side created the Jitsi room — the other side is told, using the
     * existing room/link (no second Jitsi workflow is introduced).
     */
    public function roomCreated(Appointment $appointment, string $openedBy): void
    {
        $this->run(function () use ($appointment, $openedBy): void {
            $createdByDoctor = $openedBy === AppointmentJitsiRoom::OPENED_BY_DOCTOR;

            if ($createdByDoctor) {
                $patient = $appointment->patient;

                if ($patient === null) {
                    return;
                }

                $this->deliver([$patient], new TelemedRoomCreated($appointment, [
                    'message' => 'The doctor has created the consultation room. You may now join the consultation.',
                    'action_label' => 'Join the Room',
                    'action_url' => Route::has('telemed.appointment.join')
                        ? route('telemed.appointment.join', $appointment)
                        : ($appointment->meeting_link ?: null),
                ]));

                return;
            }

            $this->deliver($this->doctorRecipients($appointment), new TelemedRoomCreated($appointment, [
                'message' => 'The patient has created a consultation room. You may now join the consultation.',
                'action_label' => 'Join the Room',
                'action_url' => $appointment->meeting_link ?: null,
            ]));
        });
    }

    /**
     * @return array<int, Patient>
     */
    private function existingApproval(Patient $patient, int $appointmentId): ?UserNotification
    {
        return $patient->realtimeNotifications()
            ->where('type', AppointmentApproved::class)
            ->limit(25)
            ->get()
            ->first(fn (UserNotification $notification): bool => (int) data_get($notification->data, 'appointment_id') === $appointmentId);
    }

    /**
     * Everyone who works the triage queue (the same people the triager
     * dashboard is guarded for).
     *
     * @return array<int, Admin>
     */
    private function triagers(): array
    {
        return Admin::query()->where('role', 'triager')->get()->all();
    }

    /**
     * @return array<int, Staff>
     */
    private function assignedDoctors(Appointment $appointment): array
    {
        if ($appointment->staff_id === null) {
            return [];
        }

        $doctor = Staff::find((int) $appointment->staff_id);

        return $doctor === null ? [] : [$doctor];
    }

    /**
     * Doctors who should hear about this appointment.
     *
     * The doctor dashboard lists every telemedicine appointment, so a request
     * that has not been assigned to a specific doctor yet still needs to reach
     * the doctors who can take it — that is how an unassigned telemedicine
     * cancellation still reaches a doctor.
     *
     * @return array<int, Staff>
     */
    private function doctorRecipients(Appointment $appointment): array
    {
        $assigned = $this->assignedDoctors($appointment);

        if ($assigned !== [] || $appointment->mode !== 'TELE') {
            return $assigned;
        }

        return Staff::query()->activeDoctors()->get()->all();
    }

    /**
     * @param  iterable<mixed>  $recipients
     */
    private function deliver(iterable $recipients, AppointmentNotification $notification): void
    {
        foreach ($recipients as $recipient) {
            if (! is_object($recipient)) {
                continue;
            }

            try {
                // The `database` channel runs first, so the notification is
                // persisted even when the WebSocket broadcast afterwards fails.
                Notification::send($recipient, $notification);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function run(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
