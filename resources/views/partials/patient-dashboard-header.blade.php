@php
    $dashboardPatientName = \Illuminate\Support\Str::headline($patientName !== '' ? $patientName : 'Guest');
    $avatarPath = (string) ($patient?->profile_pic ?? '');
    $avatarUrl = $avatarPath === ''
        ? null
        : (\Illuminate\Support\Str::startsWith($avatarPath, ['http://', 'https://']) ? $avatarPath : asset($avatarPath));
@endphp

<header class="patient-dashboard-header">
    <div class="patient-dashboard-header-inner">
        <a class="patient-header-brand d-xl-none" href="{{ route('telemed.home') }}" aria-label="QMMC Patient home">
            <span class="patient-brand-symbol" aria-hidden="true"><i class="bi bi-heart-fill"></i></span>
            <span class="patient-brand-copy"><strong>QMMC</strong><small>PATIENT</small></span>
        </a>

        <button class="patient-menu-toggle d-xl-none" type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#patientMobileMenu"
                aria-controls="patientMobileMenu"
                aria-label="Open navigation menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <form class="patient-dashboard-search" role="search" data-dashboard-search-form>
            <i class="bi bi-search" aria-hidden="true"></i>
            <label class="visually-hidden" for="dashboardSearch">Search services and appointments</label>
            <input id="dashboardSearch" type="search" placeholder="Search services, appointments..."
                   autocomplete="off" data-dashboard-search>
        </form>

        <div class="patient-header-actions">
            <button type="button"
                    class="patient-header-icon patient-header-icon-button"
                    data-open-notifications
                    aria-label="Notifications{{ $unreadNotificationCount > 0 ? ', '.$unreadNotificationCount.' unread' : '' }}">
                <i class="bi bi-bell-fill" aria-hidden="true"></i>
                @if ($unreadNotificationCount > 0)
                    <span class="patient-header-badge" aria-live="polite">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
                @endif
            </button>

            @if ($patient !== null)
                <div class="dropdown">
                    <button class="patient-account-button" type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Open patient account menu">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $patientName }}" class="patient-header-avatar">
                        @else
                            <span class="patient-header-avatar patient-header-avatar-fallback" aria-hidden="true">
                                <i class="bi bi-person-fill"></i>
                            </span>
                        @endif
                        <span class="patient-account-name">{{ $dashboardPatientName }}</span>
                        <i class="bi bi-chevron-down patient-account-chevron" aria-hidden="true"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end patient-account-menu">
                        <div class="patient-account-menu-header">
                            <span>{{ $dashboardPatientName }}</span>
                            <small>{{ $patient->hospital_number ?: 'Patient account' }}</small>
                        </div>
                        <a href="{{ route('records.index') }}" class="dropdown-item"
                           data-patient-modal data-title="Medical Records">
                            <i class="bi bi-folder2-open" aria-hidden="true"></i> Medical Records
                        </a>
                        <a href="{{ route('patient.prescriptions') }}" class="dropdown-item"
                           data-patient-modal data-title="Prescriptions">
                            <i class="bi bi-capsule-pill" aria-hidden="true"></i> Prescriptions
                        </a>
                        <a href="{{ route('patient.procedures') }}" class="dropdown-item"
                           data-patient-modal data-title="Procedures">
                            <i class="bi bi-activity" aria-hidden="true"></i> Procedures
                        </a>
                        <a href="{{ route('patient.profile') }}" class="dropdown-item"
                           data-patient-modal data-title="My Profile">
                            <i class="bi bi-person-circle" aria-hidden="true"></i> Profile
                        </a>
                    </div>
                </div>

                <a class="patient-logout-button" href="{{ route('logout') }}">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Logout</span>
                </a>
            @else
                <a class="patient-login-button" href="{{ route('auth.login') }}">Sign in</a>
            @endif
        </div>
    </div>
</header>