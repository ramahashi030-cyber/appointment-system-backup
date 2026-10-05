{{--
    Notification detail modal + the configuration the JavaScript needs.

    Opening this modal never marks anything as read: only clicking a single
    notification or the "Mark All as Read" button does that.

    Expects (optional):
      $rtVariant — patient | doctor | admin (drives the accent colour and,
                   for the patient, keeps the existing dashboard-modal shell)
      $rtModalId — the element id so the bell can target it
--}}
@php
    $rtVariant = $rtVariant ?? 'admin';
    $rtModalId = $rtModalId ?? 'realtimeNotificationsModal';
    $rtIsPatient = $rtVariant === 'patient';
    $rtFeed = \App\Support\Notifications\NotificationFeed::current();
    $rtActor = \App\Support\Notifications\NotificationActor::current();
    $rtReverb = config('broadcasting.default') === 'reverb'
        && filled(config('broadcasting.connections.reverb.key'));

    $rtConfig = [
        'channel' => $rtActor !== null
            ? sprintf('user.%s.%s', $rtActor['type'], $rtActor['id'])
            : null,
        'modalId' => $rtModalId,
        'broadcast' => $rtReverb ? [
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('broadcasting.connections.reverb.options.host'),
            'port' => (int) config('broadcasting.connections.reverb.options.port'),
            'forceTLS' => in_array(config('broadcasting.connections.reverb.options.scheme'), ['https', 'wss'], true),
            'authEndpoint' => route('notifications.auth'),
        ] : null,
        'endpoints' => [
            'index' => route('notifications.index'),
            'readAll' => route('notifications.read-all'),
        ],
        'csrf' => csrf_token(),
    ];
@endphp

<div class="modal fade {{ $rtIsPatient ? 'dashboard-modal' : '' }}"
     id="{{ $rtModalId }}"
     data-rt-modal
     tabindex="-1"
     aria-labelledby="{{ $rtModalId }}Title"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content {{ $rtIsPatient ? 'dashboard-modal-content ' : '' }}rtm rtm--{{ $rtVariant }}">

            <header class="{{ $rtIsPatient ? 'dashboard-modal-header' : 'rtm-header' }}">
                <div class="{{ $rtIsPatient ? 'dashboard-modal-title-group' : 'rtm-header-copy' }}">
                    <span class="{{ $rtIsPatient ? 'dashboard-modal-title-icon' : 'rtm-header-icon' }}" aria-hidden="true">
                        <i class="bi bi-bell-fill"></i>
                    </span>
                    <span>
                        <h2 id="{{ $rtModalId }}Title">Notifications</h2>
                        <small data-rt-unread-label>{{ $rtFeed['unread'] }} unread update{{ $rtFeed['unread'] === 1 ? '' : 's' }}</small>
                    </span>
                </div>
                <button type="button"
                        class="{{ $rtIsPatient ? 'dashboard-modal-close' : 'rtm-close' }}"
                        data-bs-dismiss="modal"
                        aria-label="Close notifications modal">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            <div class="{{ $rtIsPatient ? 'dashboard-modal-body' : 'rtm-body' }}" data-rt-body>
                {{-- Hidden by default: the list below is already server rendered,
                     so the modal is correct even if JavaScript never loads. --}}
                <div class="rtm-loading" data-rt-loading hidden>
                    Loading notifications…
                </div>

                <div class="rtm-empty" data-rt-empty{{ $rtFeed['notifications'] === [] ? '' : ' hidden' }}>
                    <i class="bi bi-bell-slash" aria-hidden="true"></i>
                    <strong>You are all caught up</strong>
                    <span>New appointment updates will appear here.</span>
                </div>

                <div class="rtm-list" data-rt-list>
                    @foreach ($rtFeed['notifications'] as $item)
                        @include('partials.notification-item', ['item' => $item])
                    @endforeach
                </div>
            </div>

            <footer class="{{ $rtIsPatient ? 'dashboard-modal-footer' : 'rtm-footer' }}">
                <button type="button" class="rtm-mark-all" data-rt-mark-all>Mark All as Read</button>
                <button type="button" class="{{ $rtIsPatient ? 'dashboard-modal-button secondary' : 'rtm-secondary' }}" data-bs-dismiss="modal">Close</button>
                @if ($rtVariant === 'patient' && $rtActor !== null)
                    <a class="{{ $rtIsPatient ? 'dashboard-modal-button primary' : 'rtm-link' }}" href="{{ route('patient.notifications') }}">Open notification center</a>
                @elseif ($rtVariant === 'doctor')
                    <a class="rtm-link" href="{{ route('doctor.notifications') }}">Open notification center</a>
                @endif
            </footer>

        </div>
    </div>
</div>

<script>
    window.QMMC_NOTIFICATIONS = @json($rtConfig);
</script>
