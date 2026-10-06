@extends('doctor.layout')

@section('title', 'Patients')

@section('content')
    <div class="doctor-patients-page">
    <div class="doctor-page-header">
        <div>
            <h1>Telemedicine Patients</h1>
            <p>Patients with at least one appointment in the doctors portal.</p>
        </div>
        <a href="{{ route('doctor.appointments') }}" class="doctor-action-button">
            <i class="bi bi-calendar2-check" aria-hidden="true"></i>All appointments
        </a>
    </div>

    <section class="doctor-panel">
        <form action="{{ route('doctor.patients') }}" method="GET" class="doctor-filter-bar">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search patient or hospital number..." aria-label="Search patients">
            <button type="submit" class="doctor-action-button primary"><i class="bi bi-search" aria-hidden="true"></i>Search</button>
            @if (request('q'))
                <a href="{{ route('doctor.patients') }}" class="doctor-action-button">Clear</a>
            @endif
        </form>

        <div class="doctor-table-wrap">
            <table class="doctor-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Hospital Number</th>
                        <th>Contact</th>
                        <th>Appointments</th>
                        <th>Last Appointment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patients as $patient)
                        <tr>
                            <td>
                                <div class="doctor-patient-cell">
                                    <span class="doctor-patient-avatar">
                                        @if ($patient['profile_pic'])
                                            <img src="{{ $patient['profile_pic'] }}" alt="{{ $patient['name'] }}">
                                        @else
                                            {{ $patient['initials'] }}
                                        @endif
                                    </span>
                                    <span class="doctor-patient-copy"><strong>{{ $patient['name'] }}</strong><small>{{ $patient['gender'] ?: 'Not provided' }}</small></span>
                                </div>
                            </td>
                            <td>{{ $patient['hospital_number'] ?: 'Not assigned' }}</td>
                            <td>{{ $patient['contact_number'] ?: 'Not provided' }}</td>
                            <td>{{ $patient['appointment_count'] }}</td>
                            <td>{{ $patient['last_appointment_display'] }}</td>
                            <td>
                                <a href="{{ route('doctor.appointments', ['q' => $patient['name']]) }}" class="doctor-action-button">View visits</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="doctor-empty-row">No telemedicine patients found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($patientPagination->hasPages())
            <div class="doctor-pagination">{{ $patientPagination->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>
    </div>
@endsection
