{{-- One row in the notification modal. The JavaScript renders the same shape. --}}
<article class="rtm-item {{ ($item['read'] ?? false) ? 'is-read' : 'is-unread' }}"
         data-rt-item="{{ $item['id'] }}"
         tabindex="0"
         role="button">
    <span class="rtm-item-icon" aria-hidden="true">
        <i class="bi {{ $item['icon'] ?? 'bi-bell-fill' }}"></i>
    </span>

    <div class="rtm-item-main">
        <div class="rtm-item-title-row">
            <strong class="rtm-item-title">{{ $item['title'] ?? 'Notification' }}</strong>
            @unless ($item['read'] ?? false)
                <span class="rtm-new">New</span>
            @endunless
        </div>

        <p class="rtm-item-message">{{ $item['message'] ?? '' }}</p>

        @if (! empty($item['service']))
            <p class="rtm-item-service">Service: <span class="rtm-chip">{{ $item['service'] }}</span></p>
        @endif

        @if (! empty($item['date']))
            <p class="rtm-item-service">
                Schedule:
                <span class="rtm-chip">{{ $item['date'] }}{{ filled($item['time'] ?? null) ? ' • '.$item['time'] : '' }}</span>
            </p>
        @elseif (filled($item['time'] ?? null))
            <p class="rtm-item-service">Time: <span class="rtm-chip">{{ $item['time'] }}</span></p>
        @endif

        @if (! empty($item['reason']))
            <p class="rtm-item-service">Reason: <span class="rtm-chip rtm-chip--reason">{{ $item['reason'] }}</span></p>
        @endif

        <div class="rtm-item-meta">
            <span><i class="bi bi-clock" aria-hidden="true"></i>{{ $item['created_label'] ?? '' }}</span>
        </div>

        @if (! empty($item['action_label']) && ! empty($item['action_url']))
            <div class="rtm-item-actions">
                <a class="rtm-action"
                   href="{{ $item['action_url'] }}"
                   @if (! empty($item['action_external'])) target="_blank" rel="noopener" @endif
                   data-rt-action>{{ $item['action_label'] }}</a>
            </div>
        @endif
    </div>
</article>
