{{--
    Patient telemedicine dashboard.

    Expected variables:
      $patient                   ?App\Models\Patient
      $patientName               string
      $activeAppointment         ?array
      $upcoming                  array
      $services                  array
      $unreadNotificationCount   int
--}}
@extends('layouts.app')

@section('title', 'Patient Dashboard')

@section('body-class', 'patient-dashboard-body')

@section('dashboard-sidebar')
    @include('partials.patient-dashboard-sidebar')
@endsection

@section('dashboard-header')
    @include('partials.patient-dashboard-header')
@endsection

@section('content')
    @php
        $dashboardName = \Illuminate\Support\Str::headline($patientName !== '' ? $patientName : 'Guest');
        $nextAppointment = $activeAppointment;
        $nextAppointmentExpired = (bool) ($nextAppointment['is_expired'] ?? false);
    @endphp

    <div class="patient-dashboard-content">
        <div class="dashboard-search-empty" data-dashboard-search-empty hidden>
            <i class="bi bi-search" aria-hidden="true"></i>
            <span>No appointments match your search.</span>
        </div>

        <section class="dashboard-hero-grid" aria-label="Telemedicine overview">
            <article class="dashboard-hero">
                <div class="dashboard-hero-glow" aria-hidden="true"></div>
                <div class="dashboard-hero-content">
                    <div class="dashboard-hero-mark" aria-hidden="true">
                        <i class="bi bi-heart-fill"></i>
                        <span><i class="bi bi-camera-video-fill"></i></span>
                    </div>
                    <div class="dashboard-hero-copy">
                        <h1>Telemedicine Consultation</h1>
                        <p class="dashboard-welcome">Welcome, {{ $dashboardName }}!</p>
                        <p class="dashboard-hero-description">Book a virtual visit with a QMMC doctor from home.</p>
                        <div class="dashboard-trust-list" aria-label="Service benefits">
                            <span><i class="bi bi-camera-video-fill" aria-hidden="true"></i> Safe</span>
                            
                            <span>Convenient</span>
                    
                            <span>Quality Care</span>
                        </div>
                    </div>
                </div>
            </article>

            <article class="dashboard-next-appointment">
                <header class="dashboard-next-header">
                    <div>
                        <span class="dashboard-next-icon" aria-hidden="true"><i class="bi bi-calendar2-week-fill"></i></span>
                        <h2>Your Next Appointment</h2>
                    </div>
                    @if ($nextAppointment)
                        <span class="dashboard-next-status {{ $nextAppointmentExpired ? 'expired' : 'upcoming' }}">
                            {{ $nextAppointmentExpired ? 'Expired' : 'Upcoming' }}
                        </span>
                    @endif
                </header>

                @if ($nextAppointment)
                    <a class="dashboard-next-body" href="{{ route('telemed.mine') }}">
                        <span class="dashboard-next-service-icon" aria-hidden="true"><i class="bi bi-heart-pulse-fill"></i></span>
                        <span class="dashboard-next-details">
                            <strong>{{ $nextAppointment['service_name'] ?? 'Consultation' }}</strong>
                            <span class="dashboard-next-meta">
                                <span><i class="bi bi-calendar3" aria-hidden="true"></i> {{ $nextAppointment['date'] ?? 'â€”' }}</span>
                                <span><i class="bi bi-clock" aria-hidden="true"></i> {{ $nextAppointment['time_slot'] ?? 'â€”' }}</span>
                            </span>
                        </span>
                        <i class="bi bi-chevron-right dashboard-next-chevron" aria-hidden="true"></i>
                    </a>
                @else
                    <button type="button" class="dashboard-next-empty" data-open-consent>
                        <span class="dashboard-next-service-icon" aria-hidden="true"><i class="bi bi-calendar-plus"></i></span>
                        <span><strong>No visit scheduled</strong><small>Book your telemedicine appointment</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                @endif
            </article>
        </section>

        <section class="dashboard-shortcuts" aria-label="Telemedicine shortcuts">
            @php
                // Default to available mode: prefer Face-to-Face if free, else Telemedicine
                $defaultMode = ($bookingLimits["face"] ?? false) ? "TELE" : "FACE";
                $defaultModeLabel = $defaultMode === "FACE" ? "Face-to-Face" : "Telemedicine";
                $defaultServiceName = $defaultMode === "FACE" ? "Face-to-Face consultation" : "Telemedicine consultation";
                $shortcutHint = $defaultMode === "FACE" ? "Book face-to-face consultation" : "Book telemedicine (face-to-face taken)";
            @endphp
            <button type="button" class="dashboard-shortcut" data-open-consent
                    data-service-mode="{{ $defaultMode }}"
                    data-service-name="{{ $defaultServiceName }}">
                <span class="dashboard-shortcut-icon blue" aria-hidden="true"><i class="bi bi-calendar2-plus-fill"></i></span>
                <span class="dashboard-shortcut-copy">
                    <strong>Request an Appointment</strong>
                    <small>{{ $shortcutHint }}</small>
                </span>
                <i class="bi bi-chevron-right dashboard-shortcut-arrow" aria-hidden="true"></i>
            </button>

            <button type="button" class="dashboard-shortcut" data-bs-toggle="modal" data-bs-target="#myVisitsModal">
                <span class="dashboard-shortcut-icon purple" aria-hidden="true"><i class="bi bi-calendar2-check-fill"></i></span>
                <span class="dashboard-shortcut-copy">
                    <strong>My Visits</strong>
                    <small>View or cancel appointments</small>
                </span>
                <i class="bi bi-chevron-right dashboard-shortcut-arrow" aria-hidden="true"></i>
            </button>

            <button type="button" class="dashboard-shortcut" data-bs-toggle="modal" data-bs-target="#servicesModal">
                <span class="dashboard-shortcut-icon green" aria-hidden="true"><i class="bi bi-heart-pulse-fill"></i></span>
                <span class="dashboard-shortcut-copy">
                    <strong>Services</strong>
                    <small>Face-to-face &amp; telemedicine services</small>
                </span>
                <i class="bi bi-chevron-right dashboard-shortcut-arrow" aria-hidden="true"></i>
            </button>

            <button type="button" class="dashboard-shortcut" data-bs-toggle="modal" data-bs-target="#recordsModal">
                <span class="dashboard-shortcut-icon navy" aria-hidden="true"><i class="bi bi-file-earmark-text-fill"></i></span>
                <span class="dashboard-shortcut-copy">
                    <strong>Records</strong>
                    <small>Prescriptions &amp; results</small>
                </span>
                <i class="bi bi-chevron-right dashboard-shortcut-arrow" aria-hidden="true"></i>
            </button>
        </section>

        <div class="dashboard-main-grid">
            <div class="dashboard-primary-column">
                <section class="dashboard-visits-card" aria-labelledby="upcomingVisitsTitle">
                    <header class="dashboard-section-header">
                        <div>
                            <i class="bi bi-calendar2-week-fill" aria-hidden="true"></i>
                            <h2 id="upcomingVisitsTitle">Upcoming Visits</h2>
                        </div>
                        <a href="{{ route('telemed.mine') }}">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </header>

                    @if (empty($upcoming))
                        <div class="dashboard-empty-state">
                            <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                            <strong>No upcoming visits.</strong>
                            <button type="button" data-open-consent>Book a visit</button>
                        </div>
                    @else
                        <div class="dashboard-table-wrap">
                            <table class="dashboard-visits-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Service</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Time</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="dashboard-action-heading">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($upcoming as $visit)
                                        @php
                                            $visitStatus = (string) ($visit['display_status'] ?? $visit['status'] ?? 'Booked');
                                            $visitDate = (string) ($visit['date'] ?? 'â€”');
                                            $visitTime = (string) ($visit['time_slot'] ?? 'â€”');
                                            $visitService = (string) ($visit['service_name'] ?? 'Consultation');
                                            $visitModeLabel = (string) ($visit['service_mode_label'] ?? '');
                                            $visitShowServiceName = $visitService !== $visitModeLabel;
                                            $visitLink = $visit['meeting_link'] ?? null;
                                            $visitId = (int) ($visit['id'] ?? 0);
                                            $visitStatusLower = strtolower($visitStatus);
                                            $visitIsCancelled = str_contains($visitStatusLower, 'cancel');
                                            $visitIsActive = in_array($visitStatusLower, ['booked', 'pending', 'confirmed', 'approved'], true);
                                            $statusClass = $visitIsCancelled ? 'cancelled' : 'booked';
                                            $searchText = strtolower(implode(' ', [$visitService, $visitDate, $visitTime, $visitStatus]));
                                        @endphp
                                        <tr
                                            data-dashboard-searchable
                                            data-search-text="{{ $searchText }}"
                                            data-patient-visit-row
                                            data-appointment-id="{{ $visitId }}"
                                            @if (! empty($visit['room_status_url'])) data-room-status-url="{{ $visit['room_status_url'] }}" @endif
                                        >
                                            <td data-label="Service">
                                                <span class="dashboard-table-service">
                                                    <span class="dashboard-table-service-icon" aria-hidden="true"><i class="bi bi-people-fill"></i></span>
                                                    @if ($visitShowServiceName)
                                                        <strong>{{ $visitService }}</strong>
                                                    @endif
                                                    @if ($visitModeLabel !== '')
                                                        <span class="dashboard-service-mode" data-mode="{{ $visit['mode'] === 'FACE' ? 'FACE' : 'TELE' }}">{{ $visitModeLabel }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td data-label="Date"><span>{{ $visitDate }}</span></td>
                                            <td data-label="Time"><span>{{ $visitTime }}</span></td>
                                            <td data-label="Status">
                                                <span class="dashboard-status {{ $statusClass }}">
                                                    {{ $visitStatus }}
                                                    <i class="bi bi-circle-fill" aria-hidden="true"></i>
                                                </span>
                                            </td>
                                            <td data-label="Action" class="dashboard-table-actions">
                                                <div class="dashboard-table-action-group">
                                                    @if ($visitIsCancelled)
                                                        <button type="button" class="dashboard-join-button cancelled"
                                                                data-open-cancelled-appointment
                                                                data-cancelled-service="{{ $visitService }}"
                                                                data-cancelled-date="{{ $visitDate }}"
                                                                data-cancelled-time="{{ $visitTime }}">
                                                            <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                                                            <span>Join</span>
                                                        </button>
                                                    @elseif (($visit['mode'] ?? '') === 'TELE' && ($visit['can_create_room'] ?? false))
                                                        <form method="POST" action="{{ $visit['open_room_url'] ?? '#' }}" class="d-inline" data-patient-create-room-form>
                                                            @csrf
                                                            <button type="submit" class="dashboard-join-button" data-patient-room-action="create">
                                                                <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                                                                <span>Create Jitsi Room</span>
                                                            </button>
                                                        </form>
                                                    @elseif (($visit['mode'] ?? '') === 'TELE' && ($visit['can_join'] ?? false))
                                                        <a class="dashboard-join-button" href="{{ $visit['join_url'] ?? $visitLink }}" target="_blank" rel="noopener" data-patient-room-action="join">
                                                            <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                                                            <span>Join Jitsi</span>
                                                        </a>
                                                    @elseif (($visit['mode'] ?? '') === 'TELE' && ! empty($visitLink))
                                                        <span class="dashboard-link-pending" data-patient-room-action="pending">Room not ready</span>
                                                    @else
                                                        <span class="dashboard-link-pending">Pending</span>
                                                    @endif
                                                    <p class="dashboard-room-notice small text-muted mb-0" data-patient-room-notice hidden>{{ $visit['room_peer_notice'] ?? '' }}</p>

                                                    @if (($visit['mode'] ?? '') === 'FACE' && !empty($visit['qr_code_url']))
                                                        <div class="dashboard-qr-section">
                                                            <img src="{{ $visit['qr_code_url'] }}" alt="Appointment QR Code" class="dashboard-qr-image">
                                                            <small>Show this QR code at the kiosk</small>
                                                        </div>
                                                    @endif

                                                    <div class="dropdown">
                                                        <button class="dashboard-more-button" type="button"
                                                                data-bs-toggle="dropdown"
                                                                aria-expanded="false"
                                                                aria-label="More actions for {{ $visitService }}">
                                                            <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="{{ route('telemed.mine') }}">View appointment</a></li>
                                                            @if ($visitId > 0 && $visitIsActive)
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <button
                                                                        type="button"
                                                                        class="dropdown-item text-danger"
                                                                        data-open-appointment-cancel
                                                                        data-cancel-id="{{ $visitId }}"
                                                                    >
                                                                        Cancel appointment
                                                                    </button>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

            <aside class="dashboard-quick-actions" aria-labelledby="quickActionsTitle">
                <h2 id="quickActionsTitle"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Quick Actions</h2>
                <div class="dashboard-quick-action-list">
                    <button type="button" class="dashboard-quick-action blue" data-open-consent>
                        <span class="dashboard-quick-action-icon" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                        <span><strong>Consult a Doctor</strong><small>Get medical advice online</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                    <a href="{{ route('patient.prescriptions') }}" class="dashboard-quick-action purple">
                        <span class="dashboard-quick-action-icon" aria-hidden="true"><i class="bi bi-clipboard-fill"></i></span>
                        <span><strong>View Prescriptions</strong><small>Check your e-prescriptions</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('records.index') }}" class="dashboard-quick-action green">
                        <span class="dashboard-quick-action-icon" aria-hidden="true"><i class="bi bi-file-earmark-text-fill"></i></span>
                        <span><strong>Download Reports</strong><small>Lab results and medical records</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('patient.notifications') }}" class="dashboard-quick-action orange">
                        <span class="dashboard-quick-action-icon" aria-hidden="true"><i class="bi bi-headset"></i></span>
                        <span><strong>Need Help?</strong><small>Contact QMMC Support</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </div>
            </aside>
        </div>

        <footer class="patient-dashboard-footer">
            <div>
                <strong>QMMC Patient Portal</strong>
                <i aria-hidden="true">•</i>
                <a href="{{ route('telemed.home') }}">Services</a>
            </div>
            <div>
                <em>Dekalidad na Serbisyo, Alagang QMMC.</em>
                <i aria-hidden="true">•</i>
                <i class="bi bi-heart-fill" aria-hidden="true"></i>
            </div>
        </footer>

        @include('partials.patient-dashboard-modals')
    </div>
@endsection






