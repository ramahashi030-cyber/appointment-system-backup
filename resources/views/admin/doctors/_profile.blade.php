@php
    $availability = $doctor->availability ?? [];
    $scheduleDays = $availability['days'] ?? [];
    $scheduleLabel = count($scheduleDays) > 0
        ? ucfirst(implode(', ', array_map('ucfirst', $scheduleDays)))
        : 'Not set';
    $initials = strtoupper(substr((string) ($doctor->FirstName ?: 'D'), 0, 1).substr((string) ($doctor->LastName ?: 'P'), 0, 1));
@endphp

<section class="admin-panel admin-doctor-profile-panel">
    <div class="admin-doctor-profile-hero">
        <span class="admin-doctor-profile-avatar">{{ $initials }}</span>
        <div>
            <h2>{{ $doctor->full_name ?: 'Unnamed provider' }}</h2>
            <p>{{ $doctor->username ?: 'No username' }} · {{ $doctor->site ?: 'Any site' }}</p>
            <span class="admin-status-pill {{ $doctor->is_active ? 'active' : 'inactive' }}">{{ $doctor->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <form method="POST" action="{{ route('admin.doctors.status', $doctor) }}">
            @csrf
            @method('PATCH')
            <button class="admin-secondary-button" type="submit">
                <i class="bi bi-{{ $doctor->is_active ? 'pause' : 'play' }}-circle" aria-hidden="true"></i>
                {{ $doctor->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </div>
    <div class="admin-doctor-profile-details">
        <div><small>Username</small><strong>{{ $doctor->username ?: '—' }}</strong></div>
        <div><small>Email</small><strong>{{ $doctor->email ?: '—' }}</strong></div>
        <div><small>Contact number</small><strong>{{ $doctor->contactno ?: '—' }}</strong></div>
        <div><small>Employee ID</small><strong>{{ $doctor->employee_id ?: '—' }}</strong></div>
        <div><small>Primary site</small><strong>{{ $doctor->site ?: 'Any site' }}</strong></div>
    </div>
</section>

<div class="admin-doctor-activity-grid">
    <section class="admin-panel" aria-labelledby="doctorAppointmentsTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-calendar2-week" aria-hidden="true"></i>
                <h2 id="doctorAppointmentsTitle">Appointments</h2>
            </div>
            <a class="admin-panel-link" href="{{ route('admin.doctors.appointments', $doctor) }}">View all</a>
        </header>
        <div class="admin-doctor-table-wrap">
            <table class="admin-doctor-table compact">
                <thead><tr><th>Patient</th><th>Service</th><th>Date</th><th>Mode</th><th>Status</th></tr></thead>
                <tbody>@include('admin.doctors._appointment-table', ['appointments' => $appointments])</tbody>
            </table>
        </div>
        @if ($appointments->hasPages())
            <div class="admin-doctor-pagination">{{ $appointments->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>

    <section class="admin-panel admin-doctor-assignment-history" aria-labelledby="doctorHistoryTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <h2 id="doctorHistoryTitle">Consultation history</h2>
            </div>
            <a class="admin-panel-link" href="{{ route('admin.doctors.history', $doctor) }}">View all</a>
        </header>
        <div class="admin-doctor-table-wrap">
            <table class="admin-doctor-table compact">
                <thead><tr><th>Patient</th><th>Service</th><th>Date</th><th>Mode</th><th>Status</th></tr></thead>
                <tbody>@include('admin.doctors._appointment-table', ['appointments' => $history])</tbody>
            </table>
        </div>
        @if ($history->hasPages())
            <div class="admin-doctor-pagination">{{ $history->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>
</div>

<div class="admin-doctor-profile-footer-grid">
<section class="admin-panel admin-doctor-schedule-panel" aria-labelledby="doctorScheduleSummaryTitle">
    <header class="admin-panel-header">
        <div class="admin-panel-title">
            <i class="bi bi-calendar2-week" aria-hidden="true"></i>
            <h2 id="doctorScheduleSummaryTitle">Availability schedule</h2>
        </div>
        <a class="admin-panel-link" href="{{ route('admin.doctors.edit', $doctor) }}">Edit schedule</a>
    </header>
    <div class="admin-doctor-schedule-summary">
        <div><small>Available days</small><strong>{{ $scheduleLabel }}</strong></div>
        <div><small>Shift hours</small><strong>{{ ! empty($availability['start']) ? $availability['start'].'–'.$availability['end'] : 'Not set' }}</strong></div>
    </div>
</section>
</div>
