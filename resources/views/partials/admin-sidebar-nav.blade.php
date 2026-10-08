@once
    <style>
        /* Equal font size for every sidebar item: links, group toggles, and sub-links */
        .admin-sidebar-nav .admin-sidebar-link,
        .admin-sidebar-nav .admin-sidebar-group-toggle,
        .admin-sidebar-nav .admin-sidebar-sublink {
            font-family: inherit !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            line-height: 1.3 !important;
        }
    </style>
@endonce

@php
    $adminLinks = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-fill'],
        ['route' => 'admin.patients', 'label' => 'Patients', 'icon' => 'bi-people-fill'],
        ['route' => 'admin.doctors', 'label' => 'Doctors', 'icon' => 'bi-person-badge-fill'],
        [
            'label' => 'Services',
            'icon' => 'bi-briefcase-fill',
            'children' => [
                ['route' => 'admin.services.face-to-face', 'label' => 'Face to Face', 'icon' => 'bi-people-fill'],
                ['route' => 'admin.services.telemedicine', 'label' => 'Telemedicine', 'icon' => 'bi-camera-video-fill'],
            ],
        ],
        [
            'label' => 'Holidays',
            'icon' => 'bi-calendar-x-fill',
            'children' => [
                ['route' => 'admin.holidays.face-to-face', 'label' => 'Face to Face', 'icon' => 'bi-people-fill'],
                ['route' => 'admin.holidays.telemedicine', 'label' => 'Telemedicine', 'icon' => 'bi-camera-video-fill'],
            ],
        ],
        [
            'label' => 'Unavailable timeslots',
            'icon' => 'bi-clock-fill',
            'children' => [
                ['route' => 'admin.timeslots.face-to-face', 'label' => 'Face to Face', 'icon' => 'bi-people-fill'],
                ['route' => 'admin.timeslots.telemedicine', 'label' => 'Telemedicine', 'icon' => 'bi-camera-video-fill'],
            ],
        ],
        ['route' => 'admin.triagers', 'label' => 'Triagers', 'icon' => 'bi-clipboard2-pulse-fill'],
        ['route' => 'admin.kiosk', 'label' => 'Kiosk', 'icon' => 'bi-qr-code-scan'],
        ['route' => 'admin.kiosk-history', 'label' => 'Kiosk History', 'icon' => 'bi-qr-code-text'],
        [
            'label' => 'Reports',
            'icon' => 'bi-calendar2-week-fill',
            'children' => [
                ['route' => 'admin.appointments.face-to-face', 'label' => 'Face to Face', 'icon' => 'bi-people-fill'],
                ['route' => 'admin.appointments.telemedicine', 'label' => 'Telemedicine', 'icon' => 'bi-camera-video-fill'],
            ],
        ],
        ['route' => 'admin.audit-logs', 'label' => 'Audit Logs', 'icon' => 'bi-journal-text'],
        ['route' => 'admin.sms', 'label' => 'SMS Module', 'icon' => 'bi-phone-fill'],
    ];
@endphp

{{--
    Renders one sidebar navigation list. Both the fixed sidebar and the mobile
    offcanvas include this so the two can never drift apart.

    A link is active when its own route matches, and a group is active when any
    of its children is, so a service sub-page still highlights its parent.

    `$instance` MUST be unique per include (e.g. 'desktop' and 'mobile'). It
    keeps the collapse element ids unique, since this partial is rendered twice
    per page and Bootstrap collapse targets elements by id. If both includes
    share an id, clicking one group toggles the matching group in both lists.

    Each group is also wired to the <nav> via data-bs-parent, so opening one
    group closes the others (accordion behavior).

    Mobile links carry data-bs-dismiss="offcanvas". Admin navigation swaps the
    .admin-main content over AJAX rather than reloading the document, and the
    offcanvas lives in .admin-shell, outside .admin-main, so it is never
    re-created and would otherwise stay open across every navigation. Only the
    mobile instance gets the attribute: the desktop sidebar is a plain <aside>
    with no offcanvas to dismiss, so its behaviour is unchanged.
--}}
@php
    $instance = $instance ?? 'primary';
    $navId = 'admin-nav-'.$instance;

    $isActiveRoute = function (?string $route): bool {
        if ($route === null) {
            return false;
        }

        if (request()->routeIs($route)) {
            return true;
        }

        // Doctor detail pages keep the Doctors entry highlighted.
        return $route === 'admin.doctors' && request()->routeIs('admin.doctors.*');
    };

    $hasActiveChild = fn (array $link): bool => collect($link['children'] ?? [])
        ->contains(fn (array $child): bool => $isActiveRoute($child['route']));
@endphp

<nav class="admin-sidebar-nav" id="{{ $navId }}" aria-label="Admin sections">
    @foreach ($adminLinks as $link)
        @if (empty($link['children']))
            @php $isActive = $isActiveRoute($link['route']); @endphp

            <a class="admin-sidebar-link {{ $isActive ? 'active' : '' }}"
               href="{{ route($link['route']) }}"
               @if ($instance === 'mobile') data-bs-dismiss="offcanvas" @endif
               @if ($isActive) aria-current="page" @endif>
                <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                <span>{{ $link['label'] }}</span>
            </a>
        @else
            @php
                $groupActive = $hasActiveChild($link);
                // Index-based id: unique per group even when two labels match
                // (there are two "Reports" entries), and unique per include.
                $groupId = "admin-nav-group-{$instance}-{$loop->index}";
            @endphp

            <div class="admin-sidebar-group {{ $groupActive ? 'active' : '' }}">
                <button type="button"
                        class="admin-sidebar-link admin-sidebar-group-toggle {{ $groupActive ? '' : 'collapsed' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $groupId }}"
                        aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                        aria-controls="{{ $groupId }}"
                        @if ($groupActive) aria-current="page" @endif>
                    <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $link['label'] }}</span>
                    <i class="bi bi-chevron-down admin-sidebar-group-chevron" aria-hidden="true"></i>
                </button>

                <div class="collapse {{ $groupActive ? 'show' : '' }}"
                     id="{{ $groupId }}"
                     data-bs-parent="#{{ $navId }}">
                    <div class="admin-sidebar-subnav">
                        @foreach ($link['children'] as $child)
                            @php $childActive = $isActiveRoute($child['route']); @endphp

                            <a class="admin-sidebar-sublink {{ $childActive ? 'active' : '' }}"
                               href="{{ route($child['route']) }}"
                               @if ($instance === 'mobile') data-bs-dismiss="offcanvas" @endif
                               @if ($childActive) aria-current="page" @endif>
                                <i class="bi {{ $child['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</nav>