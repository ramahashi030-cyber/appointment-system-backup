@php
    $triagerLinks = [
        ['route' => 'triager.dashboard', 'label' => 'Triage Dashboard', 'icon' => 'bi-clipboard2-pulse-fill'],
    ];
@endphp

<aside class="admin-sidebar d-none d-lg-flex" aria-label="Triager navigation">
    <div class="admin-sidebar-waves" aria-hidden="true"></div>

    <a class="admin-brand" href="{{ route('triager.dashboard') }}" aria-label="QMMC triager dashboard">
        <span class="admin-brand-mark" aria-hidden="true"><i class="bi bi-clipboard2-pulse-fill"></i></span>
        <span class="admin-brand-copy"><strong>QMMC</strong><small>TRIAGER</small></span>
    </a>

    <nav class="admin-sidebar-nav">
        @foreach ($triagerLinks as $link)
            @php
                $isActive = request()->routeIs($link['route']);
            @endphp
            <a class="admin-sidebar-link {{ $isActive ? 'active' : '' }}"
               href="{{ route($link['route']) }}"
               @if ($isActive) aria-current="page" @endif>
                <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="admin-sidebar-footer">
        <i class="bi bi-heart-pulse-fill" aria-hidden="true"></i>
        <span>Your Health, <em>Our Priority</em></span>
    </div>
</aside>

<div class="offcanvas offcanvas-start admin-mobile-menu" tabindex="-1" id="triagerMobileMenu" aria-labelledby="triagerMobileMenuLabel">
    <div class="offcanvas-header admin-mobile-menu-header">
        <a class="admin-brand" href="{{ route('triager.dashboard') }}" id="triagerMobileMenuLabel">
            <span class="admin-brand-mark" aria-hidden="true"><i class="bi bi-clipboard2-pulse-fill"></i></span>
            <span class="admin-brand-copy"><strong>QMMC</strong><small>TRIAGER</small></span>
        </a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
    </div>

    <div class="offcanvas-body admin-mobile-menu-body">
        <nav class="admin-sidebar-nav">
            @foreach ($triagerLinks as $link)
                @php
                    $isActive = request()->routeIs($link['route']);
                @endphp
                <a class="admin-sidebar-link {{ $isActive ? 'active' : '' }}"
                   href="{{ route($link['route']) }}"
                   @if ($isActive) aria-current="page" @endif>
                    <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <form class="admin-mobile-logout" action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                Logout
            </button>
        </form>
    </div>
</div>
