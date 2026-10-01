@extends('doctor.layout')

@section('title', 'Appointments')

@section('content')
    <div class="doctor-page-header">
        <div>
            <h1>Telemedicine Appointments</h1>
            <p>Review every patient visit, intake, status, and Jitsi room.</p>
        </div>
        <a href="{{ route('doctor.dashboard') }}" class="doctor-action-button">
            <i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard
        </a>
    </div>

    <section class="doctor-panel">
        <form action="{{ route('doctor.appointments') }}" method="GET" class="doctor-filter-bar">
            <input
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search patient, service, hospital number..."
                aria-label="Search appointments"
            >
            <input type="date" name="date" value="{{ request('date') }}" aria-label="Filter by date">
            <select name="status" data-doctor-auto-submit aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach (['Booked', 'Pending', 'Confirmed', 'Completed', 'Cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button type="submit" class="doctor-action-button primary">
                <i class="bi bi-funnel-fill" aria-hidden="true"></i>Apply
            </button>
            @if (request()->has('q') || request()->has('date') || request()->has('status'))
                <a href="{{ route('doctor.appointments') }}" class="doctor-action-button">Clear</a>
            @endif
        </form>

        <div data-doctor-local-filter-wrap>
            <div class="doctor-filter-bar border-top-0 border-bottom-0">
                <input
                    type="search"
                    value="{{ request('q') }}"
                    placeholder="Filter visible rows..."
                    aria-label="Filter visible appointment rows"
                    data-doctor-local-filter
                >
                <span class="text-muted ms-auto" style="font-size: 9px">Showing {{ count($appointments) }} appointment{{ count($appointments) === 1 ? '' : 's' }}</span>
            </div>

            @include('doctor.partials.appointment-table', [
                'appointments' => $appointments,
                'showDate' => true,
                'emptyMessage' => 'No appointments match the selected filters.',
            ])

            <div class="doctor-empty-row" data-doctor-filter-empty hidden>No visible rows match your quick filter.</div>
        </div>

        @if ($appointmentPagination->hasPages())
            <div class="doctor-pagination">
                {{ $appointmentPagination->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
@endsection