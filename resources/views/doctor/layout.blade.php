<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Doctor Dashboard') — QMMC Doctor Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    @vite([
        'resources/css/doctor-dashboard.css',
        'resources/css/doctor-patients-append.css',
        'resources/css/notifications.css',
        'resources/js/doctor-dashboard.js',
        'resources/js/notifications.js',
    ])

    @stack('head')
</head>
<body class="doctor-dashboard-body">
    @php
        $doctorName = session('doctor_name', 'Doctor');
        $doctorSpecialty = session('doctor_specialty', '');
        $doctorEmail = session('doctor_email', '');
        $doctorProfilePic = session('doctor_profile_pic');
        $doctorInitials = collect(explode(' ', preg_replace('/\bDr\.?\s*/i', '', $doctorName)))
            ->filter()
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');
        $doctorInitials = $doctorInitials ?: 'DR';
        $currentRoute = request()->route()?->getName();
        $notificationCount = (int) ($doctorNotificationCount ?? 0);
    @endphp

    <div class="doctor-shell">
        <aside class="doctor-sidebar" data-doctor-sidebar>
            <a href="{{ route('doctor.dashboard') }}" class="doctor-brand" aria-label="QMMC Doctor dashboard">
                <img src="{{ asset('images/logo.png') }}" alt="Qalinga Medical Center">
                <span class="doctor-brand-copy">
                    <strong>QMMC</strong>
                    <small>DOCTOR</small>
                </span>
            </a>

            <nav class="doctor-sidebar-nav" aria-label="Doctor portal navigation">
                <a href="{{ route('doctor.dashboard') }}" class="doctor-nav-link {{ $currentRoute === 'doctor.dashboard' ? 'active' : '' }}">
                    <i class="bi bi-house-door-fill" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('doctor.appointments') }}" class="doctor-nav-link {{ $currentRoute === 'doctor.appointments' ? 'active' : '' }}">
                    <i class="bi bi-calendar2-check-fill" aria-hidden="true"></i>
                    <span>Appointments</span>
                </a>
                <a href="{{ route('doctor.patients') }}" class="doctor-nav-link {{ $currentRoute === 'doctor.patients' ? 'active' : '' }}">
                    <i class="bi bi-people-fill" aria-hidden="true"></i>
                    <span>Patients</span>
                </a>
                <a href="{{ route('doctor.notifications') }}" class="doctor-nav-link {{ $currentRoute === 'doctor.notifications' ? 'active' : '' }}">
                    <i class="bi bi-bell-fill" aria-hidden="true"></i>
                    <span>Notifications</span>
                    @if ($notificationCount > 0)
                        <span class="doctor-nav-badge">{{ min($notificationCount, 99) }}</span>
                    @endif
                </a>
                <a href="{{ route('doctor.profile') }}" class="doctor-nav-link {{ $currentRoute === 'doctor.profile' ? 'active' : '' }}">
                    <i class="bi bi-person-fill" aria-hidden="true"></i>
                    <span>Profile</span>
                </a>
            </nav>

            <div class="doctor-sidebar-footer">
                <i class="bi bi-heart-pulse-fill" aria-hidden="true"></i>
                <span>Your Care,<br><em>Your Impact</em></span>
            </div>
        </aside>

        <div class="doctor-sidebar-overlay" data-doctor-sidebar-overlay></div>

        <div class="doctor-page">
            <header class="doctor-header">
                <div class="doctor-header-inner">
                    <button type="button" class="doctor-menu-toggle" data-doctor-sidebar-toggle aria-label="Open doctor navigation">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>

                    <form action="{{ route('doctor.appointments') }}" method="GET" class="doctor-global-search" role="search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search appointments, patients..." aria-label="Search appointments and patients">
                    </form>

                    <div class="doctor-header-actions">
                        @include('partials.notification-bell', ['rtVariant' => 'doctor', 'rtModalId' => 'realtimeNotificationsModal'])

                        <div class="dropdown">
                            <button class="doctor-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="doctor-user-avatar">
                                    @if ($doctorProfilePic)
                                        <img src="{{ $doctorProfilePic }}" alt="{{ $doctorName }}">
                                    @else
                                        {{ $doctorInitials }}
                                    @endif
                                </span>
                                <span class="doctor-user-copy">
                                    <strong>{{ $doctorName }}</strong>
                                    <small>{{ $doctorSpecialty }}</small>
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end doctor-user-menu">
                                <div class="dropdown-header">{{ $doctorEmail }}</div>
                                <a class="dropdown-item" href="{{ route('doctor.profile') }}">
                                    <i class="bi bi-person me-2" aria-hidden="true"></i>My profile
                                </a>
                                <a class="dropdown-item" href="{{ route('doctor.dashboard') }}">
                                    <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>Dashboard
                                </a>
                                <hr class="dropdown-divider">
                                <form action="{{ route('doctor.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>

                        <form action="{{ route('doctor.logout') }}" method="POST" class="doctor-logout-form">
                            @csrf
                            <button type="submit" class="doctor-action-button">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>Logout
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="doctor-main">
                @include('partials.flash')
                @yield('content')
            </main>

            <footer class="doctor-footer">
                <div class="doctor-footer-brand">
                    <strong>QMMC Doctor Portal</strong>
                    <span aria-hidden="true">|</span>
                    <a href="{{ route('doctor.dashboard') }}">Telemedicine Services</a>
                </div>
                <div class="doctor-footer-right">
                    <em>Dekalidad na Serbisyo, Alagang QMMC.</em>
                    <i class="bi bi-heart-pulse-fill" aria-hidden="true"></i>
                </div>
            </footer>
        </div>
    </div>

    <div class="modal fade doctor-appointment-modal" id="doctorAppointmentModal" tabindex="-1" aria-labelledby="doctorAppointmentModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="doctorAppointmentModalTitle">Appointment details</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="doctor-detail-summary">
                        <div class="doctor-detail-item"><span>Patient</span><strong data-doctor-modal-patient>—</strong></div>
                        <div class="doctor-detail-item"><span>Service</span><strong data-doctor-modal-service>—</strong></div>
                        <div class="doctor-detail-item"><span>Date</span><strong data-doctor-modal-date>—</strong></div>
                        <div class="doctor-detail-item"><span>Time</span><strong data-doctor-modal-time>—</strong></div>
                        <div class="doctor-detail-item"><span>Status</span><strong data-doctor-modal-status>—</strong></div>
                        <div class="doctor-detail-item"><span>Consultation reason</span><strong data-doctor-modal-reason>—</strong></div>
                    </div>
                    <div class="doctor-detail-text">
                        <h3>Selected symptoms</h3>
                        <p data-doctor-modal-symptoms>No symptoms recorded.</p>
                    </div>
                    <div class="doctor-detail-text">
                        <h3>Complaint details</h3>
                        <p data-doctor-modal-details>No complaint details recorded.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="doctor-action-button" data-bs-dismiss="modal">Close</button>
                    <a href="#" target="_blank" rel="noopener" class="doctor-action-button primary" data-doctor-modal-start>
                        <i class="bi bi-camera-video-fill" aria-hidden="true"></i>Start consultation
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Real-time notification bell detail modal (additive). --}}
    @include('partials.notification-modal', ['rtVariant' => 'doctor', 'rtModalId' => 'realtimeNotificationsModal'])

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
