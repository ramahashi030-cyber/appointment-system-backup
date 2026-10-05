<?php

namespace App\Support\Notifications;

use App\Models\Admin;
use App\Models\Patient;
use App\Models\Staff;
use Illuminate\Support\Facades\Route;

/**
 * Resolves the signed-in person for the notification bell.
 *
 * The app has three separate authentication mechanisms (session-only patients,
 * session-only doctors and the `admin` guard used by triagers), so the bell
 * cannot rely on `auth()->user()`.
 */
final class NotificationActor
{
    public const TYPE_PATIENT = 'patient';

    public const TYPE_DOCTOR = 'staff';

    public const TYPE_ADMIN = 'admin';

    /**
     * @return array{type: string, id: int}|null
     */
    public static function current(): ?array
    {
        try {
            if (auth('admin')->check()) {
                /** @var Admin $admin */
                $admin = auth('admin')->user();

                return ['type' => self::TYPE_ADMIN, 'id' => (int) $admin->getKey()];
            }

            $userType = (string) session('user_type');

            if ($userType === 'patient') {
                $patientId = (int) session('patient_id');

                return $patientId > 0 ? ['type' => self::TYPE_PATIENT, 'id' => $patientId] : null;
            }

            if ($userType === 'doctor') {
                $staffId = (int) session('staff_id');

                return $staffId > 0 ? ['type' => self::TYPE_DOCTOR, 'id' => $staffId] : null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    public static function type(): ?string
    {
        return self::current()['type'] ?? null;
    }

    public static function id(): ?int
    {
        return self::current()['id'] ?? null;
    }

    /**
     * Private channel the browser subscribes to, e.g. `user.patient.12`.
     */
    public static function channel(): ?string
    {
        $actor = self::current();

        return $actor === null ? null : sprintf('user.%s.%s', $actor['type'], $actor['id']);
    }

    /**
     * The model the notifications are stored against, or null when nobody is
     * signed in.
     */
    public static function notifiable(): ?object
    {
        $actor = self::current();

        if ($actor === null) {
            return null;
        }

        return match ($actor['type']) {
            self::TYPE_PATIENT => Patient::find($actor['id']),
            self::TYPE_DOCTOR => Staff::find($actor['id']),
            self::TYPE_ADMIN => auth('admin')->user(),
            default => null,
        };
    }

    /**
     * Where the "View Appointment" style actions should land for this person.
     */
    public static function homeRoute(string $type): ?string
    {
        return match ($type) {
            self::TYPE_PATIENT => Route::has('telemed.home') ? route('telemed.home') : null,
            self::TYPE_DOCTOR => Route::has('doctor.dashboard') ? route('doctor.dashboard') : null,
            default => Route::has('triager.dashboard') ? route('triager.dashboard') : null,
        };
    }
}
