@extends('layouts.admin')

@section('title', 'Patient visit history')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    @php
        $patientName = trim(implode(' ', array_filter([
            $patient->first_name,
            $patient->middlename,
            $patient->last_name,
        ])));
    @endphp

    <div class="admin-dashboard-content admin-doctor-content">
        <nav class="admin-breadcrumb-nav" aria-label="Patient visits navigation">
            <a class="admin-breadcrumb-link" href="{{ route('admin.patients', ['view' => $patient->id]) }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to patient profile
            </a>
        </nav>

        <section class="admin-panel" aria-labelledby="patientVisitsTitle">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                    <h1 id="patientVisitsTitle" class="admin-panel-page-title">{{ $pageTitle ?? 'Patient visit history' }}</h1>
                </div>
                <span class="admin-muted-text">{{ $visits->total() }} visit{{ $visits->total() === 1 ? '' : 's' }}</span>
            </header>

            <p class="admin-panel-intro">
                Completed visits for {{ $patientName }}.
            </p>

            <div class="admin-doctor-table-wrap">
                <table class="admin-doctor-table">
                    <thead>
                        <tr>
                            <th scope="col">Service</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Date</th>
                            <th scope="col">Provider</th>
                            <th scope="col">Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visits as $visit)
                            @php
                                $serviceName = strtoupper((string) $visit->mode) === 'TELE'
                                    ? ($visit->serviceTele?->service_name ?: 'General consultation')
                                    : ($visit->service?->service_name ?: 'General consultation');
                                $providerName = $visit->staff
                                    ? trim((string) $visit->staff->FirstName.' '.(string) $visit->staff->LastName)
                                    : 'Unassigned';
                            @endphp
                            <tr>
                                <td>{{ $serviceName }}</td>
                                <td>{{ $visit->complaint ?: $visit->consultation_reason ?: '—' }}</td>
                                <td>
                                    <strong>{{ $visit->date?->format('M j, Y') ?: '—' }}</strong>
                                    <small>{{ $visit->time_slot ?: '—' }}</small>
                                </td>
                                <td>{{ $providerName }}</td>
                                <td>{{ $visit->mode ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                                        <strong>No visits to show</strong>
                                        <span>Completed visits for this patient will appear here.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($visits->hasPages())
                <div class="admin-doctor-pagination">{{ $visits->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>
    </div>
@endsection