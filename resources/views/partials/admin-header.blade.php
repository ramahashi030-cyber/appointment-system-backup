@php
    $admin = auth('admin')->user();
    $adminName = $admin !== null
        ? trim($admin->firstname.' '.$admin->lastname)
        : 'Admin User';
@endphp

<header class="admin-header">
    <div class="admin-header-inner">
        <button class="admin-menu-toggle d-lg-none" type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#adminMobileMenu"
                aria-controls="adminMobileMenu"
                aria-label="Open navigation menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div class="admin-header-actions">
            <a class="admin-header-icon" href="{{ route('admin.appointments') }}" aria-label="View appointments">
                <i class="bi bi-bell-fill" aria-hidden="true"></i>
            </a>

            <div class="admin-user">
                <span class="admin-user-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                <span class="admin-user-copy">
                    <strong>{{ $adminName }}</strong>
                    <small>Administrator</small>
                </span>
            </div>

            <form class="admin-logout" action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</header>
