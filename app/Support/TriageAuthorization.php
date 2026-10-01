<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;

/**
 * Server-side checks for triager actions (channel, lock ownership, approval).
 */
class TriageAuthorization
{
    public const CHANNEL_FACE = 'face';

    public const CHANNEL_TELE = 'tele';

    public const CHANNEL_BOTH = 'both';

    public static function triageChannel(?Admin $admin): string
    {
        $channel = strtolower(trim((string) ($admin?->triage_channel ?? self::CHANNEL_BOTH)));

        return in_array($channel, [self::CHANNEL_FACE, self::CHANNEL_TELE, self::CHANNEL_BOTH], true)
            ? $channel
            : self::CHANNEL_BOTH;
    }

    public static function canViewMode(?Admin $admin, string $requestMode): bool
    {
        $channel = self::triageChannel($admin);
        $mode = strtoupper($requestMode);

        return match ($channel) {
            self::CHANNEL_FACE => $mode === 'FACE',
            self::CHANNEL_TELE => $mode === 'TELE',
            default => true,
        };
    }

    public static function ownsRequest(Appointment $appointment, ?int $adminId): bool
    {
        if ($adminId === null) {
            return false;
        }

        return (int) $appointment->processed_by === (int) $adminId;
    }

    public static function isLockedByOther(Appointment $appointment, ?int $adminId): bool
    {
        if ($appointment->processed_by === null) {
            return false;
        }

        if ($appointment->triager_status === 'Pending') {
            return false;
        }

        return ! self::ownsRequest($appointment, $adminId);
    }

    public static function canStartProcessing(Appointment $appointment, ?Admin $admin): bool
    {
        if ($admin === null) {
            return false;
        }

        if (! self::canViewMode($admin, (string) $appointment->request_mode)) {
            return false;
        }

        if ($appointment->triager_status !== 'Pending') {
            return false;
        }

        return $appointment->processed_by === null;
    }

    public static function canUpdateRequest(Appointment $appointment, ?Admin $admin): bool
    {
        if ($admin === null) {
            return false;
        }

        if (! self::canViewMode($admin, (string) $appointment->request_mode)) {
            return false;
        }

        if (self::isLockedByOther($appointment, $admin->id)) {
            return false;
        }

        if ($appointment->triager_status === 'Pending') {
            return false;
        }

        return self::ownsRequest($appointment, $admin->id);
    }

    public static function canSchedule(Appointment $appointment, ?Admin $admin, string $expectedMode): bool
    {
        if ($admin === null) {
            return false;
        }

        if (strtoupper((string) $appointment->request_mode) !== strtoupper($expectedMode)) {
            return false;
        }

        if (! self::canViewMode($admin, $expectedMode)) {
            return false;
        }

        if (self::isLockedByOther($appointment, $admin->id)) {
            return false;
        }

        if (! self::ownsRequest($appointment, $admin->id)) {
            return false;
        }

        if ($appointment->triager_status !== 'Approved') {
            return false;
        }

        if ($appointment->triager_action !== 'Approved') {
            return false;
        }

        return $appointment->date === null;
    }

    public static function deny(string $message): RedirectResponse
    {
        return back()->with('error', $message);
    }
}
