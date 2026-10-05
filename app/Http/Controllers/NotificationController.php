<?php

namespace App\Http\Controllers;

use App\Support\Notifications\NotificationActor;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints behind the notification bell.
 *
 * Every endpoint resolves the caller from the same session keys the rest of the
 * app uses, so a patient, a doctor and a triager can only ever read and mark
 * their own notifications.
 */
class NotificationController extends Controller
{
    /**
     * The feed shown by the modal. `?lite=1` is used by the cheap badge poll
     * and only returns the unread count.
     */
    public function index(Request $request): JsonResponse
    {
        $feed = NotificationFeed::current();

        if ($request->boolean('lite')) {
            return response()->json(['unread' => $feed['unread']]);
        }

        return response()->json($feed);
    }

    /**
     * Open a single notification — marks it read and returns the fresh feed so
     * the badge and the open modal stay in sync without a page refresh.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notifiable = NotificationActor::notifiable();

        if ($notifiable !== null) {
            try {
                $notification = $notifiable->realtimeNotifications()->whereKey($id)->first();

                if ($notification === null) {
                    return response()->json(['message' => 'Notification not found.'], 404);
                }

                $notification->markAsRead();
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $this->index($request);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $notifiable = NotificationActor::notifiable();

        if ($notifiable !== null) {
            try {
                $notifiable->realtimeNotifications()
                    ->unread()
                    ->update(['read_at' => now()]);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $this->index($request);
    }
}
