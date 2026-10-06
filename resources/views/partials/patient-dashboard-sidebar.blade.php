@php
    $dashboardLinks = [
        ['action' => 'home', 'label' => 'Home', 'icon' => 'bi-house-door-fill'],
        ['action' => 'visits', 'label' => 'Appointment', 'icon' => 'bi-calendar2-check-fill', 'target' => 'myVisitsModal'],
        ['action' => 'history', 'label' => 'History', 'icon' => 'bi-clock-history', 'target' => 'historyModal'],
        ['action' => 'notifications', 'label' => 'Notification', 'icon' => 'bi-bell-fill', 'target' => 'notificationsModal', 'badge' => $unreadNotificationCount],
        ['action' => 'profile', 'label' => 'Profile', 'icon' => 'bi-person-fill', 'target' => 'profileModal'],
    ];

    $mobileAccountLinks = [
        ['route' => 'records.index', 'label' => 'Medical Records', 'icon' => 'bi-folder2-open'],
        ['route' => 'patient.prescriptions', 'label' => 'Prescriptions', 'icon' => 'bi-capsule-pill'],
        ['route' => 'patient.procedures', 'label' => 'Procedures', 'icon' => 'bi-activity'],
    ];
@endphp

<aside class="patient-sidebar d-none d-xl-flex" aria-label="Patient dashboard navigation">
    <svg class="patient-sidebar-waves" viewBox="0 0 265 885" preserveAspectRatio="none" aria-hidden="true">
        <path fill="#0b56b5" d="M0 356c45-2 77 33 101 73 24 41 51 91 92 101 30 7 48-9 72-27v382H0Z" />
        <path fill="#0870d7" d="M0 640c51-37 104-32 144-6 36 23 72 71 121 62V885H0Z" />
    </svg>

    <a class="patient-brand" href="{{ route('telemed.home') }}" aria-label="QMMC Patient home">
        <span class="patient-brand-symbol" aria-hidden="true"><i class="bi bi-heart-fill"></i></span>
        <span class="patient-brand-copy"><strong>QALINGA</strong><small>PATIENTS</small><small>PORTAL</small></span>
    </a>

    <nav class="patient-sidebar-nav">
        @foreach ($dashboardLinks as $link)
            <button type="button"
                    class="patient-sidebar-link {{ $loop->first ? 'active' : '' }}"
                    data-sidebar-action="{{ $link['action'] }}"
                    @if (isset($link['target'])) data-sidebar-target="{{ $link['target'] }}" @endif
                    @if ($loop->first) aria-current="page" @endif>
                <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                <span>{{ $link['label'] }}</span>
                @if (($link['badge'] ?? 0) > 0)
                    <span class="patient-sidebar-badge">{{ $link['badge'] > 99 ? '99+' : $link['badge'] }}</span>
                @endif
            </button>
        @endforeach
    </nav>

    <div class="patient-health-priority" aria-label="Your health, our priority">
        <i class="bi bi-heart-pulse-fill" aria-hidden="true"></i>
        <span>Your Health<br><em>Our Priority</em></span>
    </div>
</aside>

<div class="offcanvas offcanvas-end patient-mobile-menu" tabindex="-1" id="patientMobileMenu" aria-labelledby="patientMobileMenuLabel">
    <div class="offcanvas-header patient-mobile-menu-header">
        <a class="patient-brand" href="{{ route('telemed.home') }}" id="patientMobileMenuLabel">
            <span class="patient-brand-symbol" aria-hidden="true"><i class="bi bi-heart-fill"></i></span>
            <span class="patient-brand-copy"><strong>QALINGA</strong><small>PATIENTS</small><small>PORTAL</small></span>
        </a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
    </div>

    <div class="offcanvas-body patient-mobile-menu-body">
        <nav class="patient-sidebar-nav">
            @foreach ($dashboardLinks as $link)
                <button type="button"
                        class="patient-sidebar-link {{ $loop->first ? 'active' : '' }}"
                        data-sidebar-action="{{ $link['action'] }}"
                        @if (isset($link['target'])) data-sidebar-target="{{ $link['target'] }}" @endif>
                    <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $link['label'] }}</span>
                    @if (($link['badge'] ?? 0) > 0)
                        <span class="patient-sidebar-badge">{{ $link['badge'] > 99 ? '99+' : $link['badge'] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>

        @if ($patient !== null)
            <p class="patient-mobile-menu-label">Health &amp; account</p>
            <nav class="patient-sidebar-nav patient-mobile-account-nav">
                @foreach ($mobileAccountLinks as $link)
                    <a class="patient-sidebar-link" href="{{ route($link['route']) }}">
                        <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif

        <a class="patient-menu-logout" href="{{ route('logout') }}">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            Logout
        </a>
    </div>
</div>
