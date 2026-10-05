<?php

namespace App\Support\Notifications;

use App\Models\UserNotification;

/**
 * Builds the payload the notification bell and modal render.
 *
 * Used by both the JSON endpoint and the Blade modal so the server-rendered
 * first paint and the JavaScript refresh always agree.
 */
final class NotificationFeed
{
    public const LIMIT = 30;

    /**
     * @return array{unread: int, notifications: list<array<string, mixed>>}
     */
    public static function current(): array
    {
        $notifiable = NotificationActor::notifiable();

        if ($notifiable === null) {
            return ['unread' => 0, 'notifications' => []];
        }

        try {
            $unread = $notifiable->realtimeUnreadCount();

            $notifications = $notifiable->realtimeNotifications()
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (UserNotification $notification): array => self::present($notification))
                ->values()
                ->all();

            return [
                'unread' => $unread,
                'notifications' => $notifications,
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return ['unread' => 0, 'notifications' => []];
        }
    }

    /** @return array<string, mixed> */
    public static function present(UserNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return array_merge($data, [
            'id' => $notification->id,
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toDateTimeString(),
            'created_label' => $notification->created_at?->format('F j, Y \a\t g:i A'),
        ]);
    }
}
