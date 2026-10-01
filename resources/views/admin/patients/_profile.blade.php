@php
    $patientName = trim(implode(' ', array_filter([
        $patient->first_name,
        $patient->middlename,
        $patient->last_name,
    ])));
    $initials = strtoupper(substr((string) ($patient->first_name ?: 'P'), 0, 1).substr((string) ($patient->last_name ?: 'T'), 0, 1));
    $latestAppointment = $patient->appointments()->orderByDesc('date')->orderByDesc('time_slot')->first();
@endphp

<section class="admin-panel admin-doctor-profile-panel">
    <div class="admin-doctor-profile-hero">
        <span class="admin-doctor-profile-avatar">{{ $initials }}</span>
        <div>
            <h2>{{ $patientName ?: 'Unnamed patient' }}</h2>
            <p>{{ $patient->username ?: 'No username' }} · {{ $patient->hospital_number ?: 'No hospital number' }}</p>
            <span class="admin-status-pill {{ strtolower((string) $patient->status) }}">{{ $patient->status ?: '—' }}</span>
        </div>
        <div class="admin-doctor-profile-hero-actions">
            <form method="POST" action="{{ route('admin.patients.status', $patient) }}">
                @csrf
                @method('PATCH')
                <button class="admin-secondary-button" type="submit">
                    <i class="bi bi-{{ $patient->status === 'Active' ? 'pause' : 'play' }}-circle" aria-hidden="true"></i>
                    {{ $patient->status === 'Active' ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.patients.reset-password', $patient) }}">
                @csrf
                <button class="admin-secondary-button" type="submit">
                    <i class="bi bi-key" aria-hidden="true"></i>
                    Reset password
                </button>
            </form>
        </div>
    </div>
    <div class="admin-doctor-profile-details">
        <div><small>Username</small><strong>{{ $patient->username ?: '—' }}</strong></div>
        <div><small>Email</small><strong>{{ $patient->email ?: '—' }}</strong></div>
        <div><small>Contact number</small><strong>{{ $patient->contact_number ?: '—' }}</strong></div>
        <div><small>Date of birth</small><strong>{{ $patient->dob?->format('M j, Y') ?: '—' }}</strong></div>
        <div><small>Gender</small><strong>{{ $patient->gender ?: '—' }}</strong></div>
        <div><small>Hospital number</small><strong>{{ $patient->hospital_number ?: '—' }}</strong></div>
        <div><small>Address</small><strong>{{ $patient->address ?: '—' }}</strong></div>
        <div><small>Registered</small><strong>{{ $patient->created_at?->format('M j, Y') ?: '—' }}</strong></div>
    </div>
</section>

<section class="admin-panel" aria-labelledby="patientMedicalInfoTitle">
    <header class="admin-panel-header">
        <div class="admin-panel-title">
            <i class="bi bi-file-earmark-medical" aria-hidden="true"></i>
            <h2 id="patientMedicalInfoTitle">Medical information</h2>
        </div>
        <a class="admin-panel-link" href="{{ route('admin.patients.records', $patient) }}">View records</a>
    </header>
    <div class="admin-doctor-profile-details">
        <div>
            <small>Latest complaint</small>
            <strong>{{ $latestAppointment?->complaint ?: $latestAppointment?->consultation_reason ?: '—' }}</strong>
        </div>
        <div>
            <small>Latest symptoms</small>
            <strong>{{ ! empty($latestAppointment?->symptoms) ? implode(', ', (array) $latestAppointment->symptoms) : '—' }}</strong>
        </div>
        <div>
            <small>Medical records on file</small>
            <strong>{{ $patient->medicalRecords()->count() }}</strong>
        </div>
    </div>
</section>

<div class="admin-doctor-activity-grid">
    <section class="admin-panel" aria-labelledby="patientAppointmentsTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-calendar2-week" aria-hidden="true"></i>
                <h2 id="patientAppointmentsTitle">Appointment history</h2>
            </div>
            <a class="admin-panel-link" href="{{ route('admin.patients.appointments', $patient) }}">View all</a>
        </header>
        <div class="admin-doctor-table-wrap">
            <table class="admin-doctor-table compact">
                <thead><tr><th>Service</th><th>Date</th><th>Mode</th><th>Status</th></tr></thead>
                <tbody>@include('admin.patients._appointment-table', ['appointments' => $appointments])</tbody>
            </table>
        </div>
        @if ($appointments->hasPages())
            <div class="admin-doctor-pagination">{{ $appointments->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>

    <section class="admin-panel admin-doctor-assignment-history" aria-labelledby="patientHistoryTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <h2 id="patientHistoryTitle">Consultation history</h2>
            </div>
            <a class="admin-panel-link" href="{{ route('admin.patients.history', $patient) }}">View all</a>
        </header>
        <div class="admin-doctor-table-wrap">
            <table class="admin-doctor-table compact">
                <thead><tr><th>Service</th><th>Date</th><th>Provider</th><th>Status</th></tr></thead>
                <tbody>@include('admin.patients._history-table', ['appointments' => $history])</tbody>
            </table>
        </div>
        @if ($history->hasPages())
            <div class="admin-doctor-pagination">{{ $history->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>
</div>

@if ($medicalRecords->isNotEmpty())
    <section class="admin-panel" aria-labelledby="patientRecordsSummaryTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-journal-medical" aria-hidden="true"></i>
                <h2 id="patientRecordsSummaryTitle">Recent medical records</h2>
            </div>
            <a class="admin-panel-link" href="{{ route('admin.patients.records', $patient) }}">View all</a>
        </header>
        <div class="admin-doctor-table-wrap">
            <table class="admin-doctor-table compact">
                <thead><tr><th>Type</th><th>Description</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach ($medicalRecords as $record)
                        <tr>
                            <td>{{ $record->record_type ?: '—' }}</td>
                            <td>{{ $record->description ?: '—' }}</td>
                            <td>{{ $record->created_at?->format('M j, Y') ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
