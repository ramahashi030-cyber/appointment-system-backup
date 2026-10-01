<aside class="admin-sidebar d-none d-lg-flex" aria-label="Admin navigation">
    <div class="admin-sidebar-waves" aria-hidden="true"></div>

    <a class="admin-brand" href="{{ route('admin.dashboard') }}" aria-label="QMMC admin dashboard">
        <span class="admin-brand-mark" aria-hidden="true"><i class="bi bi-shield-fill"></i></span>
        <span class="admin-brand-copy"><strong>QMMC</strong><small>ADMIN PANEL</small></span>
    </a>

    @include('partials.admin-sidebar-nav', ['instance' => 'desktop'])

    <div class="admin-sidebar-footer">
        <i class="bi bi-heart-pulse-fill" aria-hidden="true"></i>
        <span>Your Health, <em>Our Priority</em></span>
    </div>
</aside>

<div class="offcanvas offcanvas-start admin-mobile-menu" tabindex="-1" id="adminMobileMenu" aria-labelledby="adminMobileMenuLabel">
    <div class="offcanvas-header admin-mobile-menu-header">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}" id="adminMobileMenuLabel">
            <span class="admin-brand-mark" aria-hidden="true"><i class="bi bi-shield-fill"></i></span>
            <span class="admin-brand-copy"><strong>QMMC</strong><small>ADMIN PANEL</small></span>
        </a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
    </div>

    <div class="offcanvas-body admin-mobile-menu-body">
        @include('partials.admin-sidebar-nav', ['instance' => 'mobile'])

        <form class="admin-mobile-logout" action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                Logout
            </button>
        </form>
    </div>
</div>
