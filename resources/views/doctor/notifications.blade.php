@extends('doctor.layout')

@section('title', 'Notifications')

@section('content')
    <div class="doctor-page-header">
        <div>
            <h1>Appointment Notifications</h1>
            <p>Active and upcoming telemedicine visits that may need your attention.</p>
        </div>
        <a href="{{ route('doctor.dashboard') }}" class="doctor-action-button">
            <i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard
        </a>
    </div>

    <section class="doctor-panel">
        <header class="doctor-panel-header">
            <div class="doctor-panel-title">
                <i class="bi bi-bell-fill" aria-hidden="true"></i>
                <div>
                    <h2>Active Appointment Alerts</h2>
                    <p>{{ count($notifications) }} active or upcoming visit{{ count($notifications) === 1 ? '' : 's' }}</p>
                </div>
            </div>
        </header>

        <div class="doctor-table-wrap">
            <table class="doctor-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notifications as $appointment)
                        <tr>
                            <td>
                                <div class="doctor-patient-cell">
                                    <span class="doctor-patient-avatar">{{ $appointment['patient_initials'] }}</span>
                                    <span class="doctor-patient-copy"><strong>{{ $appointment['patient_name'] }}</strong><small>{{ $appointment['hospital_number'] ?: 'No hospital number' }}</small></span>
                                </div>
                            </td>
                            <td>{{ $appointment['service_name'] }}</td>
                            <td>{{ $appointment['date_display'] }}</td>
                            <td>{{ $appointment['time_display'] }}</td>
                            <td><span class="doctor-status {{ $appointment['status_class'] }}" data-doctor-status>{{ $appointment['status_label'] }}</span></td>
                            <td>
                                @if ($appointment['can_join'])
                                    <a href="{{ $appointment['meeting_link'] }}" target="_blank" rel="noopener" class="doctor-action-button primary"><i class="bi bi-camera-video-fill" aria-hidden="true"></i>Start</a>
                                @else
                                    <span class="doctor-action-button disabled">Unavailable</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="doctor-empty-row">There are no active appointment notifications.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
