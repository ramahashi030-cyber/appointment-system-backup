{{--
    Shared patient navigation bar.

    The application has one authenticated role: patients. The menu is built
    from the patient session and is shared by every patient-facing page.
--}}
@php
    /**
     * What stays ON the bar for a patient: Home, Appointment, Notification.
     * Booking and the health records live on the Home hub and avatar menu.
     *
     * @return array<string, array{url: string, label: string, icon: string}>
     */
    $patientLinks = [
        'telemed.index'         => ['url' => '/telemed',                 'label' => 'Home',         'icon' => 'bi-house-door'],
        'telemed.mine'          => ['url' => '/telemed/my-appointments', 'label' => 'Appointment',  'icon' => 'bi-calendar2-check'],
        'patient.notifications' => ['url' => '/patients/notifications',  'label' => 'Notification', 'icon' => 'bi-bell'],
    ];

    /**
     * The account dropdown behind the profile picture — the patient's own
     * health pages, so the bar itself only needs three links.
     *
     * On desktop these stay behind the avatar (caret-free) dropdown. On
     * mobile they are flattened straight into the offcanvas list instead of
     * being nested behind a second dropdown — see the offcanvas markup below.
     *
     * @return array<string, array{url: string, label: string, icon: string}>
     */
    $accountLinks = [
        'records.index'         => ['url' => '/records',                'label' => 'Medical Record', 'icon' => 'bi-folder2-open'],
        'patient.prescriptions' => ['url' => '/patients/prescriptions', 'label' => 'Prescription',   'icon' => 'bi-capsule-pill'],
        'patient.procedures'    => ['url' => '/patients/procedures',    'label' => 'Procedure',      'icon' => 'bi-activity'],
        'patient.profile'       => ['url' => '/patients/profile',       'label' => 'Profile',        'icon' => 'bi-person-circle'],
    ];

    $links = $patientLinks;
    $currentUrl = request()->path();

    $isActive = function (string $url) use ($currentUrl): bool {
        $path = trim($url, '/');

        return $path === 'telemed'
            ? $currentUrl === $path
            : ($currentUrl === $path || str_starts_with($currentUrl, $path.'/'));
    };

    foreach ($links as $key => $link) {
        $links[$key]['active'] = $isActive($link['url']);
    }

    foreach ($accountLinks as $key => $link) {
        $accountLinks[$key]['active'] = $isActive($link['url']);
    }

    // Highlight the avatar itself while one of its pages is open.
    $accountOpen = collect($accountLinks)->contains('active', true);

    $patient    = \App\Support\Telemed::currentPatient();
    $avatarPath = (string) ($patient?->profile_pic ?? '');
    $avatarUrl  = $avatarPath === ''
        ? null
        : (\Illuminate\Support\Str::startsWith($avatarPath, ['http://', 'https://']) ? $avatarPath : asset($avatarPath));
@endphp

<nav class="navbar navbar-expand-lg app-navbar no-print">
    <div class="container-fluid px-3 px-md-4">

        <a class="navbar-brand d-flex align-items-center gap-2" href="/telemed">
            <img class="auth-brand-logo" src="{{ asset('images/logo.png') }}" alt="Qalinga logo" style="height: 50px; width: auto;">
            <span>QMMC PATIENT</span>
        </a>

        <div class="d-flex align-items-center gap-2 order-lg-3">
            {{-- Mobile: hamburger toggles the offcanvas menu --}}
            <button class="navbar-toggler border-0"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#mobileMenu"
                    aria-controls="mobileMenu"
                    aria-label="Toggle navigation">
                <i class="bi bi-list fs-3"></i>
            </button>
        </div>

        {{-- Desktop menu --}}
        <div class="collapse navbar-collapse order-lg-2" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">  
                @foreach ($links as $key => $link)
                    <li class="nav-item">
                        <a class="nav-link {{ !empty($link['active']) ? 'active' : '' }}"
                           href="{{ $link['url'] }}">
                            <i class="bi {{ $link['icon'] }} me-1 d-none d-sm-inline-block"></i>{{ $link['label'] }}
                        </a>
                    </li>
                @endforeach

                {{-- Patient account: profile picture only (no caret) as a
                     dropdown toggle holding Medical Record / Prescription /
                     Procedure / Profile. Bootstrap's dropdown behavior comes
                     from data-bs-toggle="dropdown" alone, so dropping the
                     "dropdown-toggle" class removes the arrow but keeps the
                     menu working. --}}
                @if ($patient !== null)
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link p-0 px-lg-2 {{ $accountOpen ? 'active' : '' }}"
                           href="#"
                           id="accountMenuToggle"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false"
                           aria-label="Account menu">
                            <span class="d-flex align-items-center gap-2">
                                @if ($avatarUrl)
                                    <img class="nav-avatar" src="{{ $avatarUrl }}" alt="Profile picture">
                                @else
                                    <span class="nav-avatar nav-avatar-fallback"><i class="bi bi-person"></i></span>
                                @endif
                            </span>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountMenuToggle">
                            @foreach ($accountLinks as $link)
                                <li>
                                    <a class="dropdown-item {{ !empty($link['active']) ? 'active' : '' }}"
                                       href="{{ $link['url'] }}">
                                        <i class="bi {{ $link['icon'] }} me-2"></i>{{ $link['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif

                <li class="nav-item ms-lg-2">
                    <a href="/logout" class="btn btn-outline-danger btn-pill btn-sm px-3">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>

    </div>
</nav>

{{-- Mobile menu (offcanvas) --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold text-primary" id="mobileMenuLabel">
            <i class="bi bi-camera-video-fill me-2"></i>Telemedicine
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <ul class="nav nav-pills flex-column gap-1">
            @foreach ($links as $link)
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center {{ !empty($link['active']) ? 'active' : '' }}"
                       href="{{ $link['url'] }}">
                        <i class="bi {{ $link['icon'] }} me-3 fs-5"></i>{{ $link['label'] }}
                    </a>
                </li>
            @endforeach

            {{-- Account links, flattened: on mobile there's no room (or
                 reason) to bury these behind a second dropdown inside the
                 offcanvas, so every option is listed directly and visible
                 as soon as the hamburger menu opens. --}}
            @if ($patient !== null)
                <li class="mt-2 mb-1 px-2">
                    <small class="text-uppercase text-muted fw-semibold">Account</small>
                </li>
                @foreach ($accountLinks as $link)
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center {{ !empty($link['active']) ? 'active' : '' }}"
                           href="{{ $link['url'] }}">
                            <i class="bi {{ $link['icon'] }} me-3 fs-5"></i>{{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>

        <hr>

        <a href="/logout" class="btn btn-outline-danger w-100">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a>
    </div>
</div>