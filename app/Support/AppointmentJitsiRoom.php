<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
     *     room_opened_by: ?string,
     *     peer_notice: ?string
     * }
     */
    public function presentRoomState(Appointment $appointment, ?string $viewer = null): array
    {
        $appointment->loadMissing('patient');
        $appointment->refresh();

        $isActive = Appointment::hasActiveStatus((string) $appointment->status);
        $roomOpened = (bool) $appointment->room_opened;
        $meetingLink = filled($appointment->meeting_link)
            ? (string) $appointment->meeting_link
            : $this->provisionalMeetingLink($appointment);

        $canCreate = $appointment->mode === 'TELE'
            && $isActive
            && filled($meetingLink)
            && ! $roomOpened;

        $canJoin = $appointment->mode === 'TELE'
            && $isActive
            && filled($meetingLink)
            && $roomOpened;

        $peerNotice = null;

        if ($roomOpened && $viewer !== null && $appointment->room_opened_by !== null) {
            if ($viewer === self::OPENED_BY_PATIENT && $appointment->room_opened_by === self::OPENED_BY_DOCTOR) {
                $peerNotice = 'The doctor already created a room. You may now join Jitsi.';
            }

            if ($viewer === self::OPENED_BY_DOCTOR && $appointment->room_opened_by === self::OPENED_BY_PATIENT) {
                $peerNotice = 'The patient already created a room. You may now join Jitsi.';
            }
        }

        return [
            'room_opened' => $roomOpened,
            'can_create_room' => $canCreate,
            'can_join' => $canJoin,
            'meeting_link' => $meetingLink ?: null,
            'join_url' => $canJoin ? $meetingLink : null,
            'room_opened_by' => $appointment->room_opened_by,
            'peer_notice' => $peerNotice,
        ];
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
            ])->save();

            $created = true;

            return $locked->fresh(['patient']);
        });

        if ($created) {
            $peerNotice = $openedBy === self::OPENED_BY_DOCTOR
                ? 'The doctor already created a room. You may now join Jitsi.'
                : 'The patient already created a room. You may now join Jitsi.';
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
