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
            'label' => 'Timeslots',
            'icon' => 'bi-clock-fill',
            'children' => [
                ['route' => 'admin.timeslots.face-to-face', 'label' => 'Face to Face', 'icon' => 'bi-people-fill'],
                ['route' => 'admin.timeslots.telemedicine', 'label' => 'Telemedicine', 'icon' => 'bi-camera-video-fill'],
            ],
        ],
        ['route' => 'admin.triagers', 'label' => 'Triagers', 'icon' => 'bi-clipboard2-pulse-fill'],
        ['route' => 'admin.appointments', 'label' => 'Appointments', 'icon' => 'bi-calendar2-week-fill'],
        ['route' => 'admin.audit-logs', 'label' => 'Audit Logs', 'icon' => 'bi-journal-text'],
        ['route' => 'admin.records', 'label' => 'Records', 'icon' => 'bi-file-earmark-medical-fill'],
        ['route' => 'admin.reports', 'label' => 'Reports', 'icon' => 'bi-bar-chart-fill'],
        ['route' => 'admin.settings', 'label' => 'Settings', 'icon' => 'bi-gear-fill'],
    ];
@endphp

{{--
    Renders one sidebar navigation list. Both the fixed sidebar and the mobile
    offcanvas include this so the two can never drift apart.

    A link is active when its own route matches, and a group is active when any
    of its children is, so a service sub-page still highlights its parent.

    `$instance` keeps the collapse element ids unique, since this partial is
    rendered twice per page and collapse targets elements by id.
--}}
@php
    $instance = $instance ?? 'primary';

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

<nav class="admin-sidebar-nav" aria-label="Admin sections">
    @foreach ($adminLinks as $link)
        @if (empty($link['children']))
            @php $isActive = $isActiveRoute($link['route']); @endphp

            <a class="admin-sidebar-link {{ $isActive ? 'active' : '' }}"
               href="{{ route($link['route']) }}"
               @if ($isActive) aria-current="page" @endif>
                <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                <span>{{ $link['label'] }}</span>
            </a>
        @else
            @php
                $groupActive = $hasActiveChild($link);
                $groupId = 'admin-nav-group-'.$instance.'-'.str($link['label'])->slug();
            @endphp

            <div class="admin-sidebar-group {{ $groupActive ? 'active' : '' }}">
                <button type="button"
                        class="admin-sidebar-link admin-sidebar-group-toggle"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $groupId }}"
                        aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                        aria-controls="{{ $groupId }}"
                        @if ($groupActive) aria-current="page" @endif>
                    <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $link['label'] }}</span>
                    <i class="bi bi-chevron-down admin-sidebar-group-chevron" aria-hidden="true"></i>
                </button>

                <div class="collapse {{ $groupActive ? 'show' : '' }}" id="{{ $groupId }}">
                    <div class="admin-sidebar-subnav">
                        @foreach ($link['children'] as $child)
                            @php $childActive = $isActiveRoute($child['route']); @endphp

                            <a class="admin-sidebar-sublink {{ $childActive ? 'active' : '' }}"
                               href="{{ route($child['route']) }}"
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