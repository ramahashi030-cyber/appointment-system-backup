<?php

namespace App\Models\Concerns;

/**
 * Shared handling for the `availability_day` column.
 *
 * Both `services` and `services_tele` declare it as
 * SET('Mon','Tue','Wed','Thu','Fri','Sat','Sun'), stored as a comma separated
 * string. Reading and writing that encoding in one place keeps the two service
 * tables — and the admin editors that maintain them — from drifting apart, and
 * guarantees values are always written in canonical week order so the same
 * selection always round-trips to the same string.
 */
trait InterpretsAvailabilityDays
{
    /**
     * Week days in canonical order, matching the SET column definition.
     *
     * @var list<string>
     */
    public const WEEK_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    /**
     * The service's available days, decoded and ordered Mon..Sun.
     *
     * @return list<string>
     */
    public function availableDays(): array
    {
        $stored = array_filter(
            array_map('trim', explode(',', (string) $this->availability_day)),
            static fn (string $day): bool => $day !== ''
        );

        return array_values(array_intersect(self::WEEK_DAYS, $stored));
    }

    /**
     * Stage the given days for saving, canonicalised and de-duplicated.
     *
     * Unknown values are dropped rather than throwing, so a tampered request can
     * never write a value the SET column would reject.
     *
     * @param  array<int, string|null>  $days
     */
    public function setAvailableDays(array $days): static
    {
        $selected = array_filter(
            array_map(static fn ($day): string => trim((string) $day), $days),
            static fn (string $day): bool => in_array($day, self::WEEK_DAYS, true)
        );

        $this->availability_day = implode(',', array_intersect(self::WEEK_DAYS, array_unique($selected)));

        return $this;
    }

    /**
     * Full name for a day abbreviation, for display in admin tables.
     */
    public static function dayLabel(string $day): string
    {
        return match ($day) {
            'Mon' => 'Monday',
            'Tue' => 'Tuesday',
            'Wed' => 'Wednesday',
            'Thu' => 'Thursday',
            'Fri' => 'Friday',
            'Sat' => 'Saturday',
            'Sun' => 'Sunday',
            default => $day,
        };
    }
}
