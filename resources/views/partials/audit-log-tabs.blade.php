<ul class="nav nav-pills gap-2 mb-3 audit-log-tabs" aria-label="Audit log type">
    <li class="nav-item">
        <a class="nav-link {{ $active === 'staff' ? 'active' : '' }}" href="{{ route('admin.audit-logs') }}" @if ($active === 'staff') aria-current="page" @endif>
            <i class="bi bi-person-badge-fill" aria-hidden="true"></i> Staff activity
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'patients' ? 'active' : '' }}" href="{{ route('admin.audit-logs', ['type' => 'patients']) }}" @if ($active === 'patients') aria-current="page" @endif>
            <i class="bi bi-people-fill" aria-hidden="true"></i> Patient logins
        </a>
    </li>
</ul>