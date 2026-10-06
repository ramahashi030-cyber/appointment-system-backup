{{--
    Patient: my appointments — scheduled visits + pending requests.

    Expected variables:
      $appointments  array  scheduled appointments (with date/time_slot)
      $requests      array  pending triage requests (no date yet)
--}} 
@extends('layouts.app')

@section('title', 'My Appointments')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="page-title h3 mb-0">
                <i class="bi bi-calendar-check me-2"></i>My Appointments
            </h1>
            <p class="page-subtitle mb-0">Scheduled visits and pending requests</p>
        </div>

        <a href="{{ route('telemed.book') }}" class="btn btn-primary btn-pill px-4">
            <i class="bi bi-calendar2-plus me-1"></i>Request Appointment
        </a>
    </div>

    {{-- PENDING REQUESTS --}}
    @if (! empty($requests))
        <h2 class="h5 fw-bold mb-3">
            <i class="bi bi-hourglass-split me-2 text-warning"></i>Pending Requests
        </h2>
        <div id="requestList">
            @foreach ($requests as $req)
                @php
                    $rId = $req['id'] ?? 0;
                    $rIsFace = $req['is_face'] ?? false;
                    $rModeLabel = $req['mode_label'] ?? ($rIsFace ? 'Face-to-Face' : 'Telemedicine');
                    $rSymptoms = $req['symptoms_text'] ?? '';
                    $rConsultation = $req['consultation_details'] ?? 'None';
                    $rComplaint = $req['complaint_details'] ?? null;
                    $rStatus = $req['display_status'] ?? $req['status'] ?? 'Pending';
                    $rTriagerStatus = $req['triager_status'] ?? 'Pending';
                    $rRequestedAt = $req['requested_at'] ?? null;
                    $rQrUrl = $req['qr_code_url'] ?? null;
                @endphp

                <div class="card request-card">
                    <div class="card-body p-0 d-flex flex-column flex-md-row">
                        <div class="request-mode {{ $rIsFace ? 'face' : 'tele' }}">
                            <i class="bi {{ $rIsFace ? 'bi-hospital' : 'bi-camera-video' }} d-block mb-1" aria-hidden="true"></i>
                            <span>{{ $rModeLabel }}</span>
                        </div>

                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <h2 class="h6 fw-bold mb-1">
                                    @if ($rSymptoms)
                                        <span class="text-muted small d-block">Symptoms</span>
                                        <strong>{{ $rSymptoms }}</strong>
                                    @endif
                                </h2>

                                <h2 class="h6 fw-bold mb-1">
                                    <span class="text-muted small d-block">Consultation Details</span>
                                    {{ $rConsultation }}
                                </h2>

                                @if ($rComplaint)
                                    <p class="text-muted small mb-1">
                                        <span class="text-muted small d-block">Complaint Details</span>
                                        {{ $rComplaint }}
                                    </p>
                                @endif

                                <p class="text-muted small mb-1">
                                    <i class="bi bi-clock me-1"></i>
                                    @if ($rRequestedAt)
                                        {{ \Carbon\Carbon::parse($rRequestedAt)->format('M j, Y h:i A') }}
                                    @else
                                        —
                                    @endif
                                </p>

                                @php
                                    $requestBadgeClass = $rTriagerStatus === 'Approved' || $rStatus === 'Approved'
                                        ? 'bg-success'
                                        : 'bg-warning text-dark';
                                @endphp
                                <span class="badge rounded-pill {{ $requestBadgeClass }}">
                                    Status: {{ $rTriagerStatus === 'Approved' || $rStatus === 'Approved' ? 'Approved' : $rTriagerStatus }}
                                </span>

                                @if ($rTriagerStatus === 'Completed' && $rIsFace && $rQrUrl)
                                    <div class="mt-2">
                                        <button
                                            type="button"
                                            class="btn btn-outline-dark btn-pill appointment-qr-button"
                                            data-qr-expand="#appointmentQrEnlargeModal"
                                            data-qr-src="{{ $rQrUrl }}"
                                            data-qr-alt="Appointment QR code"
                                        >
                                            <i class="bi bi-qr-code me-1" aria-hidden="true"></i>QR Code
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-2 no-print">
                                @if ($rTriagerStatus === 'Completed' && ! $rIsFace)
                                    <span class="btn btn-outline-secondary btn-pill disabled">
                                        <i class="bi bi-hourglass-split me-1"></i>Schedule pending
                                    </span>
                                @elseif ($rTriagerStatus === 'Completed' && $rIsFace)
                                    <span class="btn btn-success btn-pill disabled">
                                        <i class="bi bi-check-circle me-1"></i>Scheduled
                                    </span>
                                @else
                                    <span class="btn btn-outline-secondary btn-pill disabled">
                                        <i class="bi bi-hourglass-split me-1"></i>Waiting for triage
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- SCHEDULED APPOINTMENTS --}}
    <h2 class="h5 fw-bold mb-3 mt-4">
        <i class="bi bi-calendar2-week me-2"></i>Scheduled Appointments
    </h2>

    @if (empty($appointments))
        <div class="card soft-card">
            <div class="empty-state">
                <i class="bi bi-calendar-x d-block mb-2"></i>
                <p class="mb-2">You have no scheduled appointments yet.</p>
                <a href="{{ route('telemed.book') }}" class="btn btn-primary btn-pill px-4">Request an appointment</a>
            </div>
        </div>
    @else
        <div id="visitList">
            @foreach ($appointments as $appt)
                @php
                    $aId = $appt['id'] ?? $appt->id ?? 0;
                    $aService = $appt['service_name'] ?? $appt->service_name ?? 'Consultation';
                    $aDate = $appt['date'] ?? $appt->date ?? null;
                    $aTime = $appt['time_slot'] ?? $appt->time_slot ?? '—';
                    $aStatus = $appt['display_status'] ?? $appt['status'] ?? $appt->status ?? 'Booked';
                    $aLink = $appt['join_url'] ?? null;
                    $aWaitingForDoctor = $appt['waiting_for_doctor'] ?? false;
                    $aMode = $appt['mode'] ?? 'TELE';
                    $aExpired = $appt['is_expired'] ?? false;
                    // A visit stays "upcoming" while it is still open: the
                    // controller's can_cancel keeps a telemedicine visit that
                    // only reads Completed (because the patient joined) in play
                    // until its scheduled end, exactly like the dashboard.
                    $aIsActive = (bool) ($appt['can_cancel']
                        ?? in_array(strtolower((string) $aStatus), ['booked', 'pending', 'confirmed'], true));
                    $aGroup = ($aIsActive && !$aExpired) ? 'upcoming' : 'past';
                    $aQrUrl = $appt['qr_code_url'] ?? null;
                    $aReason = $appt['consultation_reason_label'] ?? null;
                    $aSymptoms = $appt['symptom_labels'] ?? [];
                    $aComplaintDetails = $appt['complaint_details'] ?? null;
                @endphp

                <div class="card visit-card"
                     data-group="{{ $aGroup }}"
                     data-patient-visit-row
                     data-appointment-id="{{ $appt['id'] ?? 0 }}"
                     data-room-status-url="{{ $appt['room_status_url'] ?? '' }}">
                    <div class="card-body p-0 d-flex flex-column flex-md-row">

                        <div class="visit-date">
                            @if ($aDate)
                                <span class="day">{{ \Carbon\Carbon::parse($aDate)->format('d') }}</span>
                                <span class="month">{{ \Carbon\Carbon::parse($aDate)->format('M') }}</span>
                                <span class="small">{{ \Carbon\Carbon::parse($aDate)->format('Y') }}</span>
                            @else
                                <span class="day">—</span>
                            @endif
                        </div>

                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <h2 class="h6 fw-bold mb-1">
                                    <i class="bi bi-camera-video text-primary me-1"></i>{{ $aService }}
                                </h2>
                                <p class="text-muted small mb-1">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $aDate ?? 'TBA' }}
                                    &middot;
                                    <i class="bi bi-clock me-1"></i>{{ $aTime }}
                                </p>

                                @if ($aReason || ! empty($aSymptoms) || $aComplaintDetails)
                                    <div class="appointment-intake-summary">
                                        @if ($aReason)
                                            <strong>{{ $aReason }}</strong>
                                        @endif
                                        @if (! empty($aSymptoms))
                                            <span>{{ implode(' · ', $aSymptoms) }}</span>
                                        @endif
                                        @if ($aComplaintDetails)
                                            <p>{{ $aComplaintDetails }}</p>
                                        @endif
                                    </div>
                                @endif

                                @if ($aExpired)
                                    <span class="badge bg-warning text-dark" data-patient-status-badge>Expired</span>
                                @else
                                    <span class="badge rounded-pill {{ $aIsActive ? 'bg-primary' : 'bg-secondary' }}" data-patient-status-badge>
                                        {{ $aStatus }}
                                    </span>
                                @endif
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-2 no-print">
                                @if ($aGroup === 'upcoming')
                                    @if ($aQrUrl)
                                        <button
                                            type="button"
                                            class="btn btn-outline-dark btn-pill appointment-qr-button"
                                            data-qr-expand="#appointmentQrEnlargeModal"
                                            data-qr-src="{{ $aQrUrl }}"
                                            data-qr-alt="Appointment QR code for {{ $aService }} on {{ $aDate }}"
                                        >
                                            <i class="bi bi-qr-code me-1" aria-hidden="true"></i>QR Code
                                        </button>
                                    @endif

                                    @if ($aMode === 'TELE' && ($appt['can_create_room'] ?? false))
                                        <form method="POST" action="{{ $appt['open_room_url'] ?? '#' }}" class="d-inline" data-patient-create-room-form>
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-pill" data-patient-room-action="create">
                                                <i class="bi bi-camera-video me-1"></i>Create a Room
                                            </button>
                                        </form>
                                    @elseif ($aMode === 'TELE' && ($appt['can_join'] ?? false))
                                        <a href="{{ $aLink }}" target="_blank" rel="noopener"
                                           class="btn btn-success btn-pill" data-patient-room-action="join">
                                            <i class="bi bi-camera-video me-1"></i>Join the Room
                                        </a>
                                    @elseif ($aMode === 'TELE')
                                        <span class="btn btn-outline-secondary btn-pill disabled" data-patient-room-action="pending">
                                            <i class="bi bi-hourglass-split me-1"></i>Room not ready
                                        </span>
                                    @endif

                                    @if ($appt['can_cancel'] ?? true)
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger btn-pill"
                                            data-open-appointment-cancel
                                            data-cancel-id="{{ $aId }}"
                                        >
                                            <i class="bi bi-x-circle me-1"></i>Cancel
                                        </button>
                                    @endif
                                @else
                                    <a href="{{ route('telemed.book') }}" class="btn btn-outline-primary btn-pill">
                                        <i class="bi bi-arrow-repeat me-1"></i>New Request
                                    </a>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @include('partials.appointment-cancel-modal')

    <div class="modal fade qr-enlarge-modal" id="appointmentQrEnlargeModal" tabindex="-1" aria-labelledby="appointmentQrEnlargeTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <header class="qr-enlarge-header">
                    <div>
                        <h2 id="appointmentQrEnlargeTitle">Appointment QR code</h2>
                        <span>Click close when you are ready to return to your appointments.</span>
                    </div>
                    <button type="button" class="qr-enlarge-close" data-bs-dismiss="modal" aria-label="Close enlarged QR code">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </header>
                <div class="modal-body qr-enlarge-body">
                    <img data-qr-enlarged-image alt="Enlarged appointment QR code" src="">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        // This page renders no notification modal, so it carries its own copy
        // of the realtime settings (same private channel, same Reverb socket)
        // for the shared connection in resources/js/realtime.js.
        $roomActor = \App\Support\Notifications\NotificationActor::current();
        $roomReverb = config('broadcasting.default') === 'reverb'
            && filled(config('broadcasting.connections.reverb.key'));
        $roomConfig = [
            'channel' => $roomActor !== null
                ? sprintf('user.%s.%s', $roomActor['type'], $roomActor['id'])
                : null,
            'broadcast' => $roomReverb ? [
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => config('broadcasting.connections.reverb.options.port'),
                'forceTLS' => config('broadcasting.connections.reverb.options.use_tls'),
                'authEndpoint' => route('notifications.auth'),
            ] : null,
            'csrf' => csrf_token(),
        ];
    @endphp
    <script>
        window.QMMC_TELEMED_ROOMS = @json($roomConfig);
    </script>

    <script>
        /* Upcoming / Past / All filter */
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                document.querySelectorAll('#visitList .visit-card').forEach(card => {
                    card.style.display =
                        (filter === 'all' || card.dataset.group === filter) ? '' : 'none';
                });
            });
        });
    </script>
@endpush
