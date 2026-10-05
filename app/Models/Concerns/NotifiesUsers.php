<?php

namespace App\Models\Concerns;

use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * Real-time (broadcast + database) notifications for the people that use the
 * app: Patient, Staff (doctors) and Admin (triagers).
 *
 * The framework's `Notifiable` trait is intentionally NOT used here because:
 *  - `Patient` already uses it and overrides `notifications()` for the legacy
 *    patient notification box, and
 *  - Staff / Admin would otherwise gain a `notifications()` relation pointing at
 *    the legacy `notifications` table, whose schema is not Laravel's.
 *
 * Instead this trait exposes a dedicated relation and routes both the
 * `database` and `broadcast` channels through it.
 */
trait NotifiesUsers
{
    /**
     * Relation to this user's real-time notifications.
     *
     * @return MorphMany<UserNotification, $this>
     */
    public function realtimeNotifications(): MorphMany
    {
        return $this->morphMany(UserNotification::class, 'notifiable')
            ->orderByDesc('created_at');
    }

    /**
     * @return MorphMany<UserNotification, $this>
     */
    public function realtimeUnreadNotifications(): MorphMany
    {
        return $this->realtimeNotifications()->unread();
    }

    public function realtimeUnreadCount(): int
    {
        try {
            return $this->realtimeNotifications()->unread()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Store notifications through Laravel's `database` channel.
     */
    public function routeNotificationForDatabase($notification = null): MorphMany
    {
        return $this->realtimeNotifications();
    }

    /**
     * Private channel each user subscribes to, e.g. `private-user.patient.12`.
     *
     * The role segment keeps the id spaces of patients, staff and admins apart
     * so a patient can never land on a doctor's channel.
     */
    public function receivesBroadcastNotificationsOn($notification = null): string
    {
        return sprintf('user.%s.%s', $this->notificationRecipientType(), $this->getKey());
    }

    /**
     * Segment used in the private broadcast channel name.
     */
    public function notificationRecipientType(): string
    {
        return Str::lower(class_basename($this));
    }
}
