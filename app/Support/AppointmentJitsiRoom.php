<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

final class AppointmentJitsiRoom
{
    public const OPENED_BY_DOCTOR = 'doctor';

    public const OPENED_BY_PATIENT = 'patient';

    /**
     * @return array{
     *     created: bool,
     *     appointment: Appointment,
     *     peer_notice: ?string
     * }
     */
    public function openForDoctor(Appointment $appointment, int $staffId): array
    {
        return $this->open($appointment, self::OPENED_BY_DOCTOR, $staffId);
    }

    /**
     * @return array{
     *     created: bool,
     *     appointment: Appointment,
     *     peer_notice: ?string
     * }
     */
    public function openForPatient(Appointment $appointment): array
    {
        return $this->open($appointment, self::OPENED_BY_PATIENT, null);
    }

    public function buildMeetingLink(Patient $patient, Carbon $date): string
    {
        $clean = fn ($value) => preg_replace('/[^A-Za-z0-9]/', '', (string) $value);

        $room = $clean($patient->last_name)
            .$clean($patient->first_name)
            .$clean($patient->hospital_number ?: 'P'.$patient->id)
            .$date->format('Ymd');

        return 'https://meet.jit.si/'.$room;
    }

    /**
     * @return array{
     *     room_opened: bool,
     *     can_create_room: bool,
     *     can_join: bool,
     *     meeting_link: ?string,
     *     join_url: ?string,
     *     join_path: ?string,
     *     open_room_path: ?string,
     *     room_opened_by: ?string,
     *     peer_notice: ?string,
     *     status: string,
     *     is_expired: bool
     * }
     */
    public function presentRoomState(Appointment $appointment, ?string $viewer = null): array
    {
        $appointment->loadMissing('patient');
        $appointment->refresh();

        $status = (string) $appointment->status;
        $isActive = Appointment::hasActiveStatus($status);
        $isCompleted = $status === 'Completed';
        $hasEnded = AppointmentWindow::hasEnded($appointment);
        $roomOpened = (bool) $appointment->room_opened;
        $isPatientViewer = $viewer === self::OPENED_BY_PATIENT;
        $meetingLink = filled($appointment->meeting_link)
            ? (string) $appointment->meeting_link
            : $this->provisionalMeetingLink($appointment);

        // The doctor always creates the room first; the patient only ever joins
        // one. Both sides stop as soon as the scheduled window has ended.
        $canCreate = $appointment->mode === 'TELE'
            && ! $isPatientViewer
            && $isActive
            && filled($meetingLink)
            && ! $roomOpened
            && ! $hasEnded;

        // A consultation the patient already joined stays re-joinable until the
        // scheduled end time, even though the appointment is completed.
        $canJoin = $appointment->mode === 'TELE'
            && ($isActive || $isCompleted)
            && filled($meetingLink)
            && $roomOpened
            && ! $hasEnded;

        $peerNotice = null;

        if ($roomOpened && $viewer !== null && $appointment->room_opened_by !== null) {
            if ($viewer === self::OPENED_BY_PATIENT && $appointment->room_opened_by === self::OPENED_BY_DOCTOR) {
                $peerNotice = 'The doctor has created the consultation room. You may now join.';
            }

            if ($viewer === self::OPENED_BY_DOCTOR && $appointment->room_opened_by === self::OPENED_BY_PATIENT) {
                $peerNotice = 'The patient already created a consultation room. You may now join.';
            }
        }

        return [
            'room_opened' => $roomOpened,
            'can_create_room' => $canCreate,
            'can_join' => $canJoin,
            'meeting_link' => $meetingLink ?: null,
            'join_url' => $canJoin ? $meetingLink : null,
            'join_path' => $this->joinPath($appointment, $canJoin, $isPatientViewer),
            'open_room_path' => $this->openRoomPath($appointment, $isPatientViewer),
            'room_opened_by' => $appointment->room_opened_by,
            'peer_notice' => $peerNotice,
            'status' => $status,
            'is_expired' => $hasEnded,
        ];
    }

    /**
     * Where the viewer's "Join the Room" action goes: the gated patient route,
     * or the meeting link itself for the doctor.
     */
    private function joinPath(Appointment $appointment, bool $canJoin, bool $isPatientViewer): ?string
    {
        if (! $canJoin) {
            return null;
        }

        if ($isPatientViewer) {
            return Route::has('telemed.appointment.join')
                ? route('telemed.appointment.join', $appointment, false)
                : null;
        }

        return filled($appointment->meeting_link) ? (string) $appointment->meeting_link : null;
    }

    /**
     * Where the viewer's "Create a Room" form posts — only the doctor opens
     * the consultation room, so patients get null.
     */
    private function openRoomPath(Appointment $appointment, bool $isPatientViewer): ?string
    {
        if ($appointment->mode !== 'TELE' || $isPatientViewer) {
            return null;
        }

        return Route::has('doctor.appointments.open-room')
            ? route('doctor.appointments.open-room', $appointment, false)
            : null;
    }

    /**
     * @return array{
     *     created: bool,
     *     appointment: Appointment,
     *     peer_notice: ?string
     * }
     */
    private function open(Appointment $appointment, string $openedBy, ?int $staffId): array
    {
        abort_unless($appointment->mode === 'TELE', 404);
        abort_unless(Appointment::hasActiveStatus((string) $appointment->status), 403);
        abort_if(
            AppointmentWindow::hasEnded($appointment),
            403,
            'This appointment has ended, so the consultation room can no longer be opened.'
        );

        $created = false;
        $peerNotice = null;

        $appointment = DB::transaction(function () use ($appointment, $openedBy, $staffId, &$created): Appointment {
            /** @var Appointment $locked */
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('patient');

            if ((bool) $locked->room_opened) {
                return $locked;
            }

            $meetingLink = filled($locked->meeting_link)
                ? (string) $locked->meeting_link
                : $this->provisionalMeetingLink($locked);

            abort_unless(filled($meetingLink), 403, 'A Jitsi room cannot be created for this appointment yet.');

            $locked->forceFill([
                'meeting_link' => $meetingLink,
                'room_opened' => true,
                'room_opened_by' => $openedBy,
                'opened_by' => $openedBy === self::OPENED_BY_DOCTOR ? $staffId : null,
                // Creating the room books the appointment — the step the doctor
                // confirms in the "Create Consultation Room?" modal.
                'status' => Appointment::hasActiveStatus((string) $locked->status)
                    ? 'Booked'
                    : $locked->status,
            ])->save();

            $created = true;

            return $locked->fresh(['patient']);
        });

        if ($created) {
            $peerNotice = $openedBy === self::OPENED_BY_DOCTOR
                ? 'The doctor has created the consultation room. You may now join.'
                : 'The patient already created a consultation room. You may now join.';
        }

        return [
            'created' => $created,
            'appointment' => $appointment,
            'peer_notice' => $peerNotice,
        ];
    }

    private function provisionalMeetingLink(Appointment $appointment): ?string
    {
        if ($appointment->patient === null || $appointment->date === null) {
            return null;
        }

        return $this->buildMeetingLink($appointment->patient, $appointment->date);
    }
}
