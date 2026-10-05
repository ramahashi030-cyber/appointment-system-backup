<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

/**
 * A real-time notification row for a patient, doctor or admin/triager.
 *
 * Extends Laravel's own DatabaseNotification so read/unread scopes and
 * markAsRead() behave exactly like the framework's notification table, but
 * points at `user_notifications` to avoid colliding with the legacy
 * `notifications` table used by App\Models\PatientNotification.
 */
class UserNotification extends DatabaseNotification
{
    protected $table = 'user_notifications';
}
