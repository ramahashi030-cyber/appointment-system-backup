<?php

namespace App\Support;

use App\ConsultationReason;
use App\Models\Appointment;
use App\Models\HolidayTele;
use App\Models\Patient;
use App\Models\ServiceTele;
use App\Models\ServiceTimeslotTele;
use Illuminate\Support\Carbon;

/**
 * Shared helpers for the telemedicine module.
 *
 * Most of this exists to bridge the legacy schema:
 *  - services_tele.availability_day is a MySQL SET('Mon','Tue',...) of 3-letter
 *    codes, while the Blade views work with full weekday names.
 *  - holidays_tele is turned into the { "Y-m-d": "description" } map the
 *    FullCalendar JS on the booking page expects.
 *  - patients sign in through the Laravel login flow (routes/web.php →
 *    Auth\LoginController), which stores their id in the session as
 *    patient_id, exactly like $_SESSION['patient_id'] in the legacy app.
 *    currentPatient() reads that session value.
 */
class Telemed
{
    /** Full weekday name => the 3-letter code stored in availability_day. */
    public const DAY_CODES = [
        'Sunday' => 'Sun',
        'Monday' => 'Mon',
        'Tuesday' => 'Tue',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
        'Friday' => 'Fri',
        'Saturday' => 'Sat',
    ];

    /** "Mon" (or "monday"/"1") => "Monday" — same map the legacy telemed.php used. */
    protected const DAY_NAMES = [
        'sun' => 'Sunday',    'sunday' => 'Sunday',    '0' => 'Sunday',
        'mon' => 'Monday',    'monday' => 'Monday',    '1' => 'Monday',
        'tue' => 'Tuesday',   'tuesday' => 'Tuesday',  '2' => 'Tuesday',
        'wed' => 'Wednesday', 'wednesday' => 'Wednesday', '3' => 'Wednesday',
        'thu' => 'Thursday',  'thursday' => 'Thursday', '4' => 'Thursday',
        'fri' => 'Friday',    'friday' => 'Friday',    '5' => 'Friday',
        'sat' => 'Saturday',  'saturday' => 'Saturday', '6' => 'Saturday',
    ];

    /**
     * "Mon,Tue,Wed" (SET column value) => ["Monday", "Tuesday", "Wednesday"].
     *
     * @return array<int, string>
     */
    public static function codesToFull(?string $set): array
    {
        if ($set === null || trim($set) === '') {
            return [];
        }

        $days = [];

        foreach (explode(',', $set) as $part) {
            $key = strtolower(trim(str_replace("'", '', $part)));

            if (isset(self::DAY_NAMES[$key]) && ! in_array(self::DAY_NAMES[$key], $days, true)) {
                $days[] = self::DAY_NAMES[$key];
            }
        }

        return $days;
    }

    /**
     * ["Monday", "Friday"] => "Mon,Fri" for the availability_day SET column.
     *
     * @param  array<int, string>  $days
     */
    public static function fullToCodes(?array $days): string
    {
        if (empty($days)) {
            return '';
        }

        $codes = [];

        foreach ($days as $day) {
            $code = self::DAY_CODES[trim((string) $day)] ?? null;

            if ($code === null) {
                $key = strtolower(trim((string) $day));
                $full = self::DAY_NAMES[$key] ?? null;
                $code = $full !== null ? self::DAY_CODES[$full] : null;
            }

            if ($code !== null && ! in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return implode(',', $codes);
    }

    /**
     * 3-letter code stored in the DB for a given date ("2026-09-22" => "Tue").
     */
    public static function codeForDate(string $date): string
    {
        return Carbon::parse($date)->format('D');
    }

    /**
     * { "Y-m-d" => "description" } — holidays used by the booking calendar.
     *
     * @return array<string, string>
     */
    public static function holidaysMap(): array
    {
        $map = [];

        foreach (HolidayTele::orderBy('holiday_date')->get() as $holiday) {
            $map[$holiday->holiday_date->format('Y-m-d')] = (string) $holiday->description;
        }

        return $map;
    }

    /**
     * Patient signed in for this request (null when nobody is signed in).
     */
    public static function currentPatient(): ?Patient
    {
        $id = (int) session('patient_id', 0);

        return $id > 0 ? Patient::find($id) : null;
    }

    public static function currentPatientId(): ?int
    {
        $patient = static::currentPatient();

        return $patient?->id;
    }

    /**
     * "MARITES ARISCON UMALAY" — the name shown to patients.
     */
    public static function patientFullName(?Patient $patient): string
    {
        if ($patient === null) {
            return '';
        }

        return trim($patient->first_name.' '.$patient->middlename.' '.$patient->last_name);
    }

    /**
     * @return array{display: string, value: float}
     */
    public static function age(?string $dob): array
    {
        if (empty($dob)) {
            return ['display' => '', 'value' => 0];
        }

        $born = Carbon::parse($dob);
        $now = Carbon::today();

        $years = (int) $born->diffInYears($now);
        $anchor = $born->copy()->addYears($years);
        $months = (int) $anchor->diffInMonths($now);
        $days = (int) $anchor->copy()->addMonths($months)->diffInDays($now);

        if ($years > 0) {
            return [
                'display' => $years.' year'.($years > 1 ? 's' : '').' old',
                'value' => (float) $years,
            ];
        }

        if ($months > 0) {
            return [
                'display' => $months.' month'.($months > 1 ? 's' : '').' old',
                'value' => round($months / 12, 2),
            ];
        }

        return [
            'display' => $days.' day'.($days > 1 ? 's' : '').' old',
            'value' => 0,
        ];
    }

    /**
     * The patient's current active telemedicine appointment, if any.
     *
     * Only scheduled appointments count. A pending request carries an active
     * status but has no date yet, so it must not read as a confirmed
     * appointment — otherwise it would block the other consultation type.
     *
     * @return array<string, mixed>|null
     */
    public static function activeAppointment(?int $patientId): ?array
    {
        if (! $patientId) {
            return null;
        }

        $row = Appointment::query()
            ->where('patient_id', $patientId)
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->where('mode', 'TELE')
            ->whereNotNull('date')
            ->orderByDesc('created_at')
            ->first();

        if ($row === null) {
            return null;
        }

        $service = ServiceTele::find($row->service_id);

        return [
            'id' => $row->id,
            'date' => $row->date?->format('Y-m-d'),
            'time_slot' => $row->time_slot,
            'status' => $row->status,
            'meeting_link' => $row->meeting_link,
            'service_name' => $service?->service_name
                ?? ConsultationReason::tryFrom((string) $row->consultation_reason)?->label()
                ?? 'Telemedicine',
            'is_expired' => $row->date?->lt(Carbon::today()) ?? false,
        ];
    }

    /**
     * Named time slots configured for a service, "08:00 - 10:00" etc.
     *
     * @return array<int, string>
     */
    public static function timeOptions(): array
    {
        $slots = ServiceTimeslotTele::query()
            ->distinct()
            ->orderBy('time_slot')
            ->pluck('time_slot')
            ->all();

        if (! empty($slots)) {
            return $slots;
        }

        return [
            '08:00 - 10:00', '10:00 - 12:00', '12:00 - 14:00', '14:00 - 16:00',
        ];
    }
}
