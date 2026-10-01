@extends('doctor.layout')

@section('title', 'Doctor Dashboard')

@section('content')
    <div class="doctor-dashboard">
        <section class="doctor-welcome-card">
            <div class="doctor-welcome-copy">
                <span class="doctor-welcome-icon" aria-hidden="true"><i class="bi bi-heart-pulse-fill"></i></span>
                <div>
                    <h1>{{ $greeting }}, {{ $doctor['name'] }}</h1>
                    <p>Manage your telemedicine schedule</p>
                    <small>Every patient detail and Jitsi room is ready when you are.</small>
                </div>
            </div>
            <img class="doctor-welcome-image" src="{{ asset('images/qmmc-telemedicine-banner.jpg') }}" alt="Qalinga Memorial Medical Center">
        </section>

        <div class="doctor-dashboard-layout">
            <div class="doctor-dashboard-primary">
                <section class="doctor-panel" id="doctor-schedule">
                    <header class="doctor-panel-header">
                        <div class="doctor-panel-title">
                            <i class="bi bi-calendar2-week-fill" aria-hidden="true"></i>
                            <div>
                                <h2>My Schedule</h2>
                                <p>View and manage your telemedicine appointments</p>
                            </div>
                        </div>

                        <div class="doctor-schedule-controls">
                            <div class="doctor-range-tabs" aria-label="Schedule range">
                                @foreach (['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $rangeKey => $rangeLabel)
                                    <a
                                        href="{{ route('doctor.dashboard', ['range' => $rangeKey, 'date' => $selectedDate->toDateString()]) }}"
                                        class="doctor-range-tab {{ $range === $rangeKey ? 'active' : '' }}"
                                    >{{ $rangeLabel }}</a>
                                @endforeach
                            </div>
                            <div class="doctor-date-nav">
                                <a href="{{ route('doctor.dashboard', ['range' => $range, 'date' => $previousDate->toDateString()]) }}" aria-label="Previous {{ $range }}">
                                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('doctor.dashboard', ['range' => $range, 'date' => $selectedDate->toDateString()]) }}" class="doctor-date-label">
                                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ $dateLabel }}
                                </a>
                                <a href="{{ route('doctor.dashboard', ['range' => $range, 'date' => $nextDate->toDateString()]) }}" aria-label="Next {{ $range }}">
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </header>

                    @if ($range === 'month')
                        <div class="doctor-schedule-scroll">
                            <div class="doctor-month-grid">
                                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                                    <div class="doctor-month-weekday">{{ $weekday }}</div>
                                @endforeach

                                @foreach ($calendarDays as $day)
                                    @php
                                        $dayAppointments = collect($rangeAppointments)
                                            ->where('date', $day['date'])
                                            ->values();
                                    @endphp
                                    <div class="doctor-month-day {{ ! $day['in_current_month'] ? 'outside' : '' }} {{ $day['is_today'] ? 'today' : '' }}">
                                        <strong>{{ $day['day_number'] }}</strong>
                                        @foreach ($dayAppointments->take(3) as $appointment)
                                            <button
                                                type="button"
                                                class="doctor-month-appointment"
                                                data-doctor-appointment-view
                                                data-patient="{{ $appointment['patient_name'] }}"
                                                data-service="{{ $appointment['service_name'] }}"
                                                data-date="{{ $appointment['date_display'] }}"
                                                data-time="{{ $appointment['time_display'] }}"
                                                data-status="{{ $appointment['status_label'] }}"
                                                data-reason="{{ $appointment['consultation_reason_label'] ?? 'Not recorded' }}"
                                                data-symptoms="{{ ! empty($appointment['symptom_labels']) ? implode(', ', $appointment['symptom_labels']) : 'No symptoms recorded.' }}"
                                                data-details="{{ $appointment['complaint_details'] ?: 'No complaint details recorded.' }}"
                                                data-meeting-link="{{ $appointment['meeting_link'] ?? '' }}"
                                            >{{ $appointment['time_display'] }} · {{ $appointment['patient_name'] }}</button>
                                        @endforeach
                                        @if ($dayAppointments->count() > 3)
                                            <a href="{{ route('doctor.appointments', ['date' => $day['date']]) }}" class="doctor-month-more">+{{ $dayAppointments->count() - 3 }} more</a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="doctor-schedule-scroll">
                            <div class="doctor-schedule-grid" style="--doctor-day-count: {{ count($calendarDays) }}">
                                <div class="doctor-schedule-corner"></div>
                                @foreach ($calendarDays as $day)
                                    <div class="doctor-schedule-day {{ $day['is_today'] ? 'today' : '' }}">
                                        <strong>{{ $day['day_name'] }}</strong>
                                        <span>{{ $day['date_short'] }}</span>
                                    </div>
                                @endforeach

                                @foreach ($timeSlots as $timeSlot)
                                    <div class="doctor-schedule-time">{{ $timeSlot['display'] }}</div>
                                    @foreach ($calendarDays as $day)
                                        @php
                                            $cellKey = $day['date'].'|'.$timeSlot['value'];
                                            $cellAppointments = $scheduleByCell[$cellKey] ?? [];
                                        @endphp
                                        <div class="doctor-schedule-cell {{ $day['is_today'] ? 'today-column' : '' }}">
                                            @forelse ($cellAppointments as $appointment)
                                                <button
                                                    type="button"
                                                    class="doctor-schedule-appointment {{ $appointment['status_class'] }}"
                                                    data-doctor-appointment-view
                                                    data-patient="{{ $appointment['patient_name'] }}"
                                                    data-service="{{ $appointment['service_name'] }}"
                                                    data-date="{{ $appointment['date_display'] }}"
                                                    data-time="{{ $appointment['time_display'] }}"
                                                    data-status="{{ $appointment['status_label'] }}"
                                                    data-reason="{{ $appointment['consultation_reason_label'] ?? 'Not recorded' }}"
                                                    data-symptoms="{{ ! empty($appointment['symptom_labels']) ? implode(', ', $appointment['symptom_labels']) : 'No symptoms recorded.' }}"
                                                    data-details="{{ $appointment['complaint_details'] ?: 'No complaint details recorded.' }}"
                                                    data-meeting-link="{{ $appointment['meeting_link'] ?? '' }}"
                                                >
                                                    <strong>{{ $appointment['patient_name'] }}</strong>
                                                    <span>{{ $appointment['time_display'] }}</span>
                                                </button>
                                            @empty
                                                <span class="doctor-schedule-cell available">Available</span>
                                            @endforelse
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                <div class="doctor-dashboard-lower">
                    <section class="doctor-panel">
                        <header class="doctor-panel-header">
                            <div class="doctor-panel-title">
                                <i class="bi bi-calendar2-check-fill" aria-hidden="true"></i>
                                <div>
                                    <h2>Today's Appointments</h2>
                                    <p>{{ $todayAppointments->count() }} telemedicine visits</p>
                                </div>
                            </div>
                            <a class="doctor-panel-link" href="{{ route('doctor.appointments', ['date' => $today->toDateString()]) }}">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        </header>
                        @include('doctor.partials.appointment-table', [
                            'appointments' => $todayAppointments,
                            'showDate' => false,
                            'emptyMessage' => 'No telemedicine appointments today.',
                        ])
                    </section>

                    <section class="doctor-panel">
                        <header class="doctor-panel-header">
                            <div class="doctor-panel-title">
                                <i class="bi bi-clock-history" aria-hidden="true"></i>
                                <div>
                                    <h2>Availability</h2>
                                    <p>Your schedule for today</p>
                                </div>
                            </div>
                        </header>
                        <div class="doctor-availability-list" style="padding: 15px 16px 18px">
                            @foreach ($availability as $availabilityRow)
                                <div class="doctor-availability-row {{ $availabilityRow['class'] }}">
                                    <i></i>
                                    <strong>{{ $availabilityRow['label'] }}</strong>
                                    <span>{{ $availabilityRow['count'] }} slot{{ $availabilityRow['count'] === 1 ? '' : 's' }}</span>
                                    <span>({{ $availabilityRow['hours'] }})</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>

            <aside class="doctor-dashboard-sidebar">
                <section class="doctor-panel doctor-overview-card">
                    <div class="doctor-overview-heading">
                        <h2><i class="bi bi-calendar2-week-fill" aria-hidden="true"></i>Today's Overview</h2>
                        <a href="{{ route('doctor.appointments') }}" class="doctor-overview-link">View Details <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                    </div>

                    <div class="doctor-overview-grid">
                        <div class="doctor-overview-stat">
                            <i class="bi bi-calendar2-check-fill" aria-hidden="true"></i>
                            <strong>{{ $overview['total'] }}</strong>
                            <span>Appointments</span>
                        </div>
                        <div class="doctor-overview-stat completed">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <strong>{{ $overview['completed'] }}</strong>
                            <span>Completed</span>
                        </div>
                        <div class="doctor-overview-stat upcoming">
                            <i class="bi bi-clock-fill" aria-hidden="true"></i>
                            <strong>{{ $overview['upcoming'] }}</strong>
                            <span>Upcoming</span>
                        </div>
                        <div class="doctor-overview-stat cancelled">
                            <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                            <strong>{{ $overview['cancelled'] }}</strong>
                            <span>Cancelled</span>
                        </div>
                    </div>

                    <div class="doctor-progress">
                        <div class="doctor-progress-copy"><span>Daily completion</span><strong>{{ $overview['progress'] }}% Completed</strong></div>
                        <div class="doctor-progress-track"><div class="doctor-progress-bar" style="width: {{ $overview['progress'] }}%"></div></div>
                    </div>
                </section>

                <section class="doctor-panel doctor-next-card">
                    <div class="doctor-next-heading">
                        <h2><i class="bi bi-person-fill" aria-hidden="true"></i>Next Patient</h2>
                    </div>
                    <p class="doctor-next-subtitle">Your next active appointment</p>

                    @if ($nextAppointment)
                        <div class="doctor-next-patient">
                            <span class="doctor-next-avatar">{{ $nextAppointment['patient_initials'] }}</span>
                            <span class="doctor-next-copy">
                                <strong>{{ $nextAppointment['patient_name'] }}</strong>
                                <span>{{ $nextAppointment['service_name'] }}</span>
                                <small><i class="bi bi-clock" aria-hidden="true"></i>{{ $nextAppointment['date_display'] }} · {{ $nextAppointment['time_display'] }}</small>
                            </span>
                        </div>
                        @if ($nextAppointment['can_join'])
                            <a href="{{ $nextAppointment['meeting_link'] }}" target="_blank" rel="noopener" class="doctor-start-button">
                                <i class="bi bi-camera-video-fill" aria-hidden="true"></i>Start Consultation
                            </a>
                        @else
                            <span class="doctor-action-button disabled w-100 mt-3">Jitsi link unavailable</span>
                        @endif
                    @else
                        <div class="doctor-next-patient">
                            <span class="doctor-next-avatar"><i class="bi bi-check2" aria-hidden="true"></i></span>
                            <span class="doctor-next-copy"><strong>Schedule is clear</strong><span>No active telemedicine appointments ahead.</span></span>
                        </div>
                    @endif
                </section>

                <section class="doctor-panel doctor-quick-card">
                    <div class="doctor-quick-heading" style="padding: 16px 16px 0">
                        <h2><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i>Quick Actions</h2>
                    </div>
                    <div class="doctor-quick-grid" style="padding: 0 16px 16px">
                        <a href="{{ route('doctor.dashboard', ['range' => 'week']) }}#doctor-schedule" class="doctor-quick-link">
                            <span class="doctor-quick-icon"><i class="bi bi-calendar2-plus-fill" aria-hidden="true"></i></span>
                            <span class="doctor-quick-copy"><strong>My Schedule</strong><small>Review day, week, or month</small></span>
                        </a>
                        <a href="{{ route('doctor.patients') }}" class="doctor-quick-link">
                            <span class="doctor-quick-icon"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                            <span class="doctor-quick-copy"><strong>View Patients</strong><small>Browse telemedicine patients</small></span>
                        </a>
                        <a href="{{ route('doctor.appointments') }}" class="doctor-quick-link">
                            <span class="doctor-quick-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
                            <span class="doctor-quick-copy"><strong>All Appointments</strong><small>Search every scheduled visit</small></span>
                        </a>
                        @if ($nextAppointment && $nextAppointment['can_join'])
                            <a href="{{ $nextAppointment['meeting_link'] }}" target="_blank" rel="noopener" class="doctor-quick-link">
                                <span class="doctor-quick-icon"><i class="bi bi-camera-video-fill" aria-hidden="true"></i></span>
                                <span class="doctor-quick-copy"><strong>Telemedicine Room</strong><small>Start the next consultation</small></span>
                            </a>
                        @else
                            <a href="{{ route('doctor.notifications') }}" class="doctor-quick-link">
                                <span class="doctor-quick-icon"><i class="bi bi-bell-fill" aria-hidden="true"></i></span>
                                <span class="doctor-quick-copy"><strong>Notifications</strong><small>Review active appointments</small></span>
                            </a>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
