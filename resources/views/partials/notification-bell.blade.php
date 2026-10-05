{{--
    Notification bell for the doctor and admin/triager dashboards.

    The patient dashboard keeps its existing bell (data-open-notifications) so
    nothing in that header moves; this partial is only for the two headers that
    did not have a bell before.

    Expects (optional): $rtVariant (doctor|admin) and $rtModalId.
--}}
@php
    $rtVariant = $rtVariant ?? 'admin';
    $rtModalId = $rtModalId ?? 'realtimeNotificationsModal';
    $rtUnread = \App\Support\Notifications\NotificationFeed::current()['unread'];
@endphp

<button type="button"
        class="rtm-bell rtm-bell--{{ $rtVariant }}"
        data-rt-bell
        data-bs-toggle="modal"
        data-bs-target="#{{ $rtModalId }}"
        aria-label="{{ $rtUnread > 0 ? 'Notifications, '.$rtUnread.' unread' : 'Notifications' }}">
    <i class="bi bi-bell-fill" aria-hidden="true"></i>
    <span class="rtm-badge" data-rt-badge{{ $rtUnread > 0 ? '' : ' hidden' }}>{{ $rtUnread > 9 ? '9+' : $rtUnread }}</span>
</button>
