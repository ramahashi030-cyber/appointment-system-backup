<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Holiday;
use App\Models\HolidayTele;
use App\Models\ServiceTimeslot;
use App\Models\ServiceTimeslotTele;
use App\Models\UnavailableTimeslot;
use App\Models\UnavailableTimeslotTele;
use Illuminate\Support\Carbon;

/**
 * Monthly availability powering the schedule modals (telemedicine and
 * face-to-face).
 *
 * Both consultation modes share the same shape — service availability days,
 * per-slot capacity minus active bookings, blocked slots, and holidays — but
 * read from mirrored face/tele tables, so the mode selects the models.
 */
class ScheduleCalendar
{
    /**
     * Every day of the requested month reports whether it can be booked and,
     * when it cannot, why — plus an explicit holiday flag so the calendar can
     * colour holidays differently from ordinary unavailable days.
     *
     * @param  string  $mode  Appointment mode: 'TELE' or 'FACE'
     * @return array{month: string, month_label: string, days: array<int, array<string, mixed>>}
     */
    public static function month(string $mode, int $serviceId, ?string $availabilityDay, string $month): array
    {
        [$slotModel, $blockedModel, $holidayModel] = self::models($mode);

        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $availableWeekdays = Telemed::codesToFull($availabilityDay);
        $holidays = $holidayModel::orderBy('holiday_date')->get()
            ->mapWithKeys(fn ($holiday) => [$holiday->holiday_date->format('Y-m-d') => (string) $holiday->description]);
        $slots = $slotModel::query()
            ->where('service_id', $serviceId)
            ->orderBy('time_slot')
            ->get()
            ->keyBy('time_slot');

        $booked = Appointment::query()
            ->where('mode', $mode)
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->where('service_id', $serviceId)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['date', 'time_slot'])
            ->groupBy(fn (Appointment $appointment) => $appointment->date->format('Y-m-d').'|'.$appointment->time_slot)
            ->map(fn ($appointments) => $appointments->count());

        $blocked = $blockedModel::query()
            ->where('service_id', $serviceId)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->mapWithKeys(fn ($slot) => [
                $slot->date->format('Y-m-d').'|'.$slot->time_slot => $slot->reason ?: 'Unavailable',
            ]);

        $days = [];
        $date = $monthStart->copy();

        while ($date->lessThanOrEqualTo($monthEnd)) {
            $dateKey = $date->toDateString();
            $remainingSlots = 0;

            foreach ($slots as $timeSlot => $slot) {
                $slotKey = $dateKey.'|'.$timeSlot;

                if (! $blocked->has($slotKey)) {
                    $remainingSlots += max(
                        0,
                        (int) $slot->slots - (int) $booked->get($slotKey, 0)
                    );
                }
            }

            $isPast = $date->isBefore(Carbon::today()->startOfDay());
            $isClosedToday = $date->isSameDay(Carbon::today()) && now()->hour >= 18;
            $isServiceDay = in_array($date->format('l'), $availableWeekdays, true);
            $holiday = $holidays[$dateKey] ?? null;
            $isAvailable = ! $isPast
                && ! $isClosedToday
                && $isServiceDay
                && $holiday === null
                && $remainingSlots > 0;
            $isFullyBooked = ! $isPast
                && ! $isClosedToday
                && $isServiceDay
                && $holiday === null
                && $remainingSlots === 0;

            $reason = match (true) {
                $isPast => 'Date has passed',
                $isClosedToday => 'Booking is closed for today',
                $holiday !== null => $holiday,
                ! $isServiceDay => 'Service is not available on this day',
                $isFullyBooked => 'Fully booked',
                default => $remainingSlots.' slot'.($remainingSlots === 1 ? '' : 's').' remaining',
            };

            $days[] = [
                'date' => $dateKey,
                'day' => (int) $date->format('j'),
                'available' => $isAvailable,
                'fully_booked' => $isFullyBooked,
                'holiday' => $holiday !== null,
                'remaining_slots' => $remainingSlots,
                'reason' => $reason,
            ];

            $date->addDay();
        }

        return [
            'month' => $monthStart->format('Y-m'),
            'month_label' => $monthStart->format('F Y'),
            'days' => $days,
        ];
    }

    /**
     * Face and telemedicine store their data in mirrored tables.
     *
     * @return array{0: class-string, 1: class-string, 2: class-string}
     */
    private static function models(string $mode): array
    {
        return match ($mode) {
            'TELE' => [ServiceTimeslotTele::class, UnavailableTimeslotTele::class, HolidayTele::class],
            'FACE' => [ServiceTimeslot::class, UnavailableTimeslot::class, Holiday::class],
            default => throw new \InvalidArgumentException("Unsupported schedule mode [{$mode}]."),
        };
    }
}
