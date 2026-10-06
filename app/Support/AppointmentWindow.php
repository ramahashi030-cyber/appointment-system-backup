<?php

namespace App\Support;

use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * The scheduled window of an appointment — its date plus the end of the
 * "08:00 - 10:00" time slot written by the triage workflow — and the telemed
 * expiry rules built on top of it.
 *
 * The telemedicine workflow keeps a room available only while the appointment
 * window is open:
 *
 *  - while the window is open the doctor can create the room and the patient
 *    can join it;
 *  - once the end time passes the appointment is closed (status `Completed`),
 *    which drops it off the active Patient/Doctor dashboards and moves it to
 *    History on both sides;
 *  - opening or joining after that moment is refused.
 *
 * Slot times are wall-clock times in the hospital's timezone, so every
 * comparison runs in the display timezone (Asia/Manila) instead of the
 * application's storage timezone (UTC).
 */
final class AppointmentWindow
{
    /**
     * Timezone the appointment slots are written in.
     */
    public static function timezone(): string
    {
        return (string) config('app.display_timezone', 'Asia/Manila');
    }

    /**
     * Moment the appointment's slot ends, or null while it has no date yet
     * (a request that is still waiting for triage).
     */
    public static function endsAt(Appointment $appointment): ?Carbon
    {
        if ($appointment->date === null) {
            return null;
        }

        // The date column carries no time: re-parse it in the slot timezone so
        // "the end of the day" means the end of the local day.
        $day = Carbon::parse($appointment->date->toDateString(), self::timezone());
        $slot = trim((string) $appointment->time_slot);

        if ($slot === '') {
            return $day->copy()->endOfDay();
        }

        $parts = preg_split('/\s*-\s*/', $slot) ?: [];
        $end = trim((string) ($parts[1] ?? ''));

        if ($end === '') {
            return $day->copy()->endOfDay();
        }

        try {
            return Carbon::parse($day->toDateString().' '.$end, self::timezone());
        } catch (Throwable) {
            // Unreadable slot text must never make an appointment joinable
            // forever: fall back to "the rest of that day".
            return $day->copy()->endOfDay();
        }
    }

    /**
     * True once the scheduled end time has been reached (or the appointment
     * belongs to a past day). Requests without a date never expire.
     */
    public static function hasEnded(Appointment $appointment, ?Carbon $now = null): bool
    {
        $endsAt = self::endsAt($appointment);

        if ($endsAt === null) {
            return false;
        }

        $now ??= Carbon::now(self::timezone());

        return $now->greaterThanOrEqualTo($endsAt);
    }

    /**
     * Close every telemedicine appointment whose window has passed: the status
     * becomes `Completed`, which is what moves it off the active dashboards and
     * into History for the patient and the doctor at the same time.
     *
     * @param  int|null  $patientId  limit the sweep to one patient (patient portal reads)
     * @return int number of appointments that were closed
     */
    public static function closeExpired(?int $patientId = null): int
    {
        // `whereDate` rather than a plain comparison: the column is written as
        // a full timestamp, so `date <= '2026-10-06'` would exclude midnight.
        $query = Appointment::query()
            ->where('mode', 'TELE')
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->whereNotNull('date')
            ->whereDate('date', '<=', Carbon::now(self::timezone())->toDateString());

        if ($patientId !== null) {
            $query->where('patient_id', $patientId);
        }

        $closed = 0;

        foreach ($query->get() as $appointment) {
            if (! self::hasEnded($appointment)) {
                continue;
            }

            $appointment->status = 'Completed';
            $appointment->save();
            $closed++;
        }

        return $closed;
    }
}
