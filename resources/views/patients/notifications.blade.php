{{--
    Patient: notifications — upcoming appointments (the point of this nav
    item) plus the stored notices from the legacy `notifications` table.

    Expected variables:
      $patientName  string
      $upcoming     array  appointments from today onward (see PatientPortalController)
      $notices      \Illuminate\Support\Collection  patient's notification rows
--}}
@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="mb-4">
        <h1 class="page-title h3 mb-0">
            <i class="bi bi-bell me-2"></i>Notifications
        </h1>
        <p class="page-subtitle mb-0">Your upcoming appointments and notices</p>
    </div>

    {{-- UPCOMING APPOINTMENTS --}}
    <h2 class="h5 fw-bold mb-3">
        <i class="bi bi-calendar-event me-1 text-primary"></i>Upcoming appointments
    </h2>

    @if (empty($upcoming))
        <div class="card soft-card mb-4">
            <div class="empty-state">
                <i class="bi bi-calendar-x d-block mb-2"></i>
                <p class="mb-2">You have no upcoming appointments.</p>
                <a href="/telemed/book" class="btn btn-primary btn-pill px-4">
                    <i class="bi bi-calendar2-plus me-1"></i>Book a visit
                </a>
            </div>
        </div>
    @else
        <div class="mb-4">
            @foreach ($upcoming as $appt)
                <div class="card notice-card">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-center border rounded px-3 py-2">
                                <div class="h4 fw-bold mb-0">{{ $appt['day'] }}</div>
                                <div class="text-uppercase small">{{ $appt['month'] }}</div>
                            </div>
                            <div>
                                <h3 class="h6 fw-bold mb-1">
                                    <i class="bi bi-calendar-check text-primary me-1"></i>{{ $appt['service_name'] }}
                                </h3>
                                <p class="text-muted small mb-1">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $appt['date'] }}
                                    &middot;
                                    <i class="bi bi-clock me-1"></i>{{ $appt['time_slot'] }}
                                    &middot;
                                    {{ $appt['mode'] === 'TELE' ? 'Telemedicine' : 'Face to face' }}
                                </p>
                                <span class="badge rounded-pill {{ $appt['status'] === 'Booked' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $appt['status'] }}
                                </span>
                            </div>
                        </div>

                        @if ($appt['mode'] === 'TELE' && $appt['meeting_link'])
                            <a href="{{ $appt['meeting_link'] }}" target="_blank" rel="noopener"
                               class="btn btn-success btn-pill">
                                <i class="bi bi-camera-video me-1"></i>Join Now
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- STORED NOTICES --}}
    <h2 class="h5 fw-bold mb-3">
        <i class="bi bi-envelope me-1 text-primary"></i>Notices
    </h2>

    @if ($notices->isEmpty())
        <div class="card soft-card">
            <div class="empty-state">
                <i class="bi bi-bell-slash d-block mb-2"></i>
                <p class="mb-0">No notices yet.</p>
            </div>
        </div>
    @else
        @foreach ($notices as $notice)
            <div class="card notice-card">
                <div class="card-body d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <p class="mb-1">{{ $notice->message }}</p>
                        <span class="text-muted small">
                            <i class="bi bi-clock me-1"></i>{{ $notice->created_at?->format('M d, Y h:i A') }}
                        </span>
                    </div>
                    @unless ($notice->is_read)
                        <span class="badge rounded-pill badge-unread">New</span>
                    @endunless
                </div>
            </div>
        @endforeach
    @endif
@endsection
