@extends('layouts.admin')

@section('title', $pageTitle ?? 'Patient appointments')

@section('sidebar')
    @include('partials.admin-sidebar')
@end

@section('header')
    @include('partials.admin-header')
@end

@section('content')
    @php
        $patientName = trim(implode(' ', array_filter([
            $patient->first_name,
            $patient->middlename,
            $patient->last_name,
        ])));
    @endphp

    <div class="admin-dashboard-content admin-doctor-content">
        <nav class="admin-breadcrumb-nav" aria-label="Patient history navigation">
            <a class="admin-breadcrumb-link" href="{{ route('admin.patients', ['view' => $patient->id]) }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to patient profile
            </a>
        </nav>

        <section class="admin-panel" aria-labelledby="patientHistoryPageTitle">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-{{ $history ? 'clock-history' : 'calendar2-week' }}" aria-hidden="true"></i>
                    <h1 id="patientHistoryPageTitle" class="admin-panel-page-title">{{ $pageTitle ?? 'Patient appointments' }}</h1>
                </div>
                <span class="admin-muted-text">{{ $appointments->total() }} record{{ $appointments->total() === 1 ? '' : 's' }}</span>
            </header>

            <p class="admin-panel-intro">
                {{ $history
                    ? 'Completed consultations for '.$patientName.'.'
                    : 'Full appointment history for '.$patientName.', including upcoming, completed, and cancelled visits.' }}
            </p>

            <div class="admin-doctor-table-wrap">
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">{{ $pageTitle ?? 'Patient appointments' }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">Service</th>
                            <th scope="col">Date</th>
                            <th scope="col">Mode</th>
                            <th scope="col">Provider</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                            @php
                                $serviceName = strtoupper((string) $appointment->mode) === 'TELE'
                                    ? ($appointment->serviceTele?->service_name ?: 'General consultation')
                                    : ($appointment->service?->service_name ?: 'General consultation');
                                $providerName = $appointment->staff
                                    ? trim((string) $appointment->staff->FirstName.' '.(string) $appointment->staff->LastName)
                                    : 'Unassigned';
                                $status = strtolower((string) ($appointment->status ?: 'booked'));
                            @endphp
                            <tr>
                                <td>
                                    <div class="admin-doctor-appointment-patient">
                                        <strong>{{ $serviceName }}</strong>
                                        <small>{{ $appointment->consultation_reason ?: 'No consultation reason' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $appointment->date?->format('M j, Y') ?: '—' }}</strong>
                                    <small>{{ $appointment->time_slot ?: '—' }}</small>
                                </td>
                                <td>{{ $appointment->mode ?: '—' }}</td>
                                <td>{{ $providerName }}</td>
                                <td>
                                    <span class="admin-status-pill {{ $status }}">{{ ucfirst($status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                                        <strong>No appointments to show</strong>
                                        <span>{{ $history ? 'Completed consultations for this patient will appear here.' : 'Appointments booked by this patient will appear here.' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($appointments->hasPages())
                <div class="admin-doctor-pagination">{{ $appointments->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>
    </div>