@extends('layouts.admin')

@section('title', 'Triager Dashboard')

@section('sidebar')
    @include('partials.triager-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content admin-doctor-content triager-dashboard-content">
        {{-- BANNER --}}
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="triagerTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                    <span><i class="bi bi-check2-circle"></i></span>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="triagerTitle">Triager Dashboard</h1>
                    <p class="admin-telemedicine-welcome">Appointment Request Processing</p>
                    <p class="admin-telemedicine-description">Review, schedule, and process incoming face-to-face and telemedicine appointment requests.</p>
                    <div class="admin-telemedicine-trust triager-trust-pills" aria-label="Triager features">
                        <span class="triager-trust-pill"><i class="bi bi-hospital" aria-hidden="true"></i> Face to Face</span>
                        <span class="triager-trust-pill"><i class="bi bi-camera-video" aria-hidden="true"></i> Telemedicine</span>
                        <span class="triager-trust-pill"><i class="bi bi-calendar2-week" aria-hidden="true"></i> Scheduling</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- STATS --}}
        <div class="admin-doctor-stat-grid">
            <article class="admin-doctor-stat-card triager-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi bi-clipboard2-pulse-fill" aria-hidden="true"></i></span>
                <span><small>Face-to-Face requests</small><strong>{{ number_format(count($faceRequests)) }}</strong></span>
                <i class="bi bi-chevron-right triager-stat-chevron" aria-hidden="true"></i>
            </article>
            <article class="admin-doctor-stat-card triager-stat-card">
                <span class="admin-doctor-stat-icon cyan"><i class="bi bi-camera-video-fill" aria-hidden="true"></i></span>
                <span><small>Telemedicine requests</small><strong>{{ number_format(count($teleRequests)) }}</strong></span>
                <i class="bi bi-chevron-right triager-stat-chevron" aria-hidden="true"></i>
            </article>
            <article class="admin-doctor-stat-card triager-stat-card">
                <span class="admin-doctor-stat-icon orange"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <span><small>Total pending</small><strong>{{ number_format(count($faceRequests) + count($teleRequests)) }}</strong></span>
                <i class="bi bi-chevron-right triager-stat-chevron" aria-hidden="true"></i>
            </article>
            <article class="admin-doctor-stat-card triager-stat-card">
                <span class="admin-doctor-stat-icon green"><i class="bi bi-check-circle-fill" aria-hidden="true"></i></span>
                <span><small>Processed today</small><strong>{{ number_format(count($processedToday)) }}</strong></span>
                <i class="bi bi-chevron-right triager-stat-chevron" aria-hidden="true"></i>
            </article>
        </div>

        <div class="triager-layout">
            <div class="triager-main">
                {{-- FACE TO FACE REQUESTS --}}
                <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="faceRequestsTitle">
                    <header class="admin-panel-header admin-doctor-roster-header">
                        <div class="admin-panel-title">
                            <i class="bi bi-hospital" aria-hidden="true"></i>
                            <h2 id="faceRequestsTitle">Request Appointments (Face to Face)</h2>
                        </div>
                        <span class="admin-muted-text triager-panel-count">{{ count($faceRequests) }} request{{ count($faceRequests) === 1 ? '' : 's' }}</span>
                    </header>

                    <div class="triager-card-grid">
                        @forelse ($faceRequests as $req)
                            @include('partials.triager-request-card', ['request' => $req, 'mode' => 'FACE'])
                        @empty
                            <div class="admin-doctor-empty">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <strong>No pending requests</strong>
                                <span>No pending face-to-face requests.</span>
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- TELEMEDICINE REQUESTS --}}
                <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="teleRequestsTitle">
                    <header class="admin-panel-header admin-doctor-roster-header">
                        <div class="admin-panel-title">
                            <i class="bi bi-camera-video" aria-hidden="true"></i>
                            <h2 id="teleRequestsTitle">Request Appointments (Telemedicine)</h2>
                        </div>
                        <span class="admin-muted-text triager-panel-count">{{ count($teleRequests) }} request{{ count($teleRequests) === 1 ? '' : 's' }}</span>
                    </header>

                    <div class="triager-card-grid">
                        @forelse ($teleRequests as $req)
                            @include('partials.triager-request-card', ['request' => $req, 'mode' => 'TELE'])
                        @empty
                            <div class="admin-doctor-empty">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <strong>No pending requests</strong>
                                <span>No pending telemedicine requests.</span>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="triager-sidebar" aria-labelledby="processedTodayTitle">
                <section class="admin-panel admin-doctor-roster-panel">
                    <header class="admin-panel-header admin-doctor-roster-header">
                        <div class="admin-panel-title">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            <h2 id="processedTodayTitle">Processed Today</h2>
                        </div>
                        <a href="{{ route('triager.processed.print') }}" target="_blank" class="admin-doctor-add-button triager-print-btn" aria-label="Print processed requests">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            <span>Print</span>
                        </a>
                    </header>

                    @if (blank($processedToday))
                        <div class="admin-doctor-empty">
                            <i class="bi bi-clipboard-x" aria-hidden="true"></i>
                            <strong>Nothing processed yet</strong>
                            <span>No processed requests today.</span>
                        </div>
                    @else
                        <div class="triager-processed-list">
                            @foreach ($processedToday as $processed)
                                <div class="triager-processed-item">
                                    <div class="admin-doctor-person">
                                        <span class="admin-avatar">{{ $processed['initials'] }}</span>
                                        <span>
                                            <strong>{{ $processed['patient_name'] }}</strong>
                                            <small>{{ $processed['requested_at']?->format('h:i A') ?? '—' }}</small>
                                        </span>
                                    </div>
                                    <span class="triager-processed-badge {{ strtolower(str_replace(' ', '-', $processed['triager_action'] ?? 'pending')) }}">
                                        {{ $processed['triager_action'] ?? 'Processed' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </aside>
        </div>
    </div>

    @include('partials.triager-schedule-modals')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Start Processing buttons
            document.querySelectorAll('[data-start-processing]').forEach((button) => {
                button.addEventListener('click', () => {
                    const requestId = button.dataset.startProcessing;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('triager.requests.start', ['appointment' => '__ID__']) }}'.replace('__ID__', requestId);
                    form.innerHTML = '@csrf';
                    document.body.appendChild(form);
                    form.submit();
                });
            });

            // Save Update buttons
            document.querySelectorAll('[data-save-request]').forEach((button) => {
                button.addEventListener('click', () => {
                    const requestId = button.dataset.saveRequest;
                    const card = button.closest('[data-request-card]');
                    if (!card) return;

                    const actionSelect = card.querySelector('[data-triager-action]');
                    const remarksInput = card.querySelector('[data-triager-remarks]');

                    if (!actionSelect || !actionSelect.value) {
                        alert('Please select an action before saving.');
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('triager.requests.update', ['appointment' => '__ID__']) }}'.replace('__ID__', requestId);

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    form.appendChild(csrfInput);

                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'triager_action';
                    actionInput.value = actionSelect.value;
                    form.appendChild(actionInput);

                    const remarksHidden = document.createElement('input');
                    remarksHidden.type = 'hidden';
                    remarksHidden.name = 'triager_remarks';
                    remarksHidden.value = remarksInput ? remarksInput.value : '';
                    form.appendChild(remarksHidden);

                    document.body.appendChild(form);
                    form.submit();
                });
            });

            // Schedule modals — Create Appointment Module (calendar + time slots).
            // The telemed and face-to-face modals share identical markup; each is
            // initialised against its own modal id, trigger attribute and endpoints.
            const initScheduleModal = ({ modalId, requestAttr, calendarUrl, timeslotsUrl, submitUrl }) => {
                const modalRoot = document.getElementById(modalId);
                if (!modalRoot) return;

                const form = modalRoot.querySelector('form');
                const serviceField = modalRoot.querySelector('[data-schedule-field="service"]');
                const dateField = modalRoot.querySelector('[data-schedule-field="date"]');
                const timeField = modalRoot.querySelector('[data-schedule-field="time"]');
                const ageField = modalRoot.querySelector('[data-schedule-field="age"]');
                const genderField = modalRoot.querySelector('[data-schedule-field="gender"]');
                const complaintField = modalRoot.querySelector('[data-schedule-field="complaint"]');
                const errorBox = modalRoot.querySelector('[data-schedule-error]');
                const patientLabel = modalRoot.querySelector('[data-schedule-patient-label]');
                const scheduleSection = modalRoot.querySelector('[data-schedule-section]');
                const calendarGrid = modalRoot.querySelector('[data-schedule-calendar]');
                const monthLabel = modalRoot.querySelector('[data-schedule-month-label]');
                const prevMonth = modalRoot.querySelector('[data-schedule-prev]');
                const nextMonth = modalRoot.querySelector('[data-schedule-next]');
                const slotsGrid = modalRoot.querySelector('[data-schedule-slots]');
                const selectedDateLabel = modalRoot.querySelector('[data-schedule-selected-date]');
                let requestId = null;

                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const maxMonth = new Date(today.getFullYear(), today.getMonth() + 12, 1);
                const state = {
                    viewDate: new Date(today.getFullYear(), today.getMonth(), 1),
                    selectedDate: '',
                    selectedTime: '',
                    days: new Map(),
                    calendarRequest: 0,
                    slotsRequest: 0,
                };

                const padDigit = (value) => String(value).padStart(2, '0');
                const toLocalDateKey = (date) => `${date.getFullYear()}-${padDigit(date.getMonth() + 1)}-${padDigit(date.getDate())}`;
                const toMonthKey = (date) => `${date.getFullYear()}-${padDigit(date.getMonth() + 1)}`;

                const showError = (message) => {
                    if (!errorBox) return;
                    errorBox.textContent = message;
                    errorBox.hidden = false;
                };

                const clearError = () => {
                    if (!errorBox) return;
                    errorBox.textContent = '';
                    errorBox.hidden = true;
                };

                const renderSlotsMessage = (message) => {
                    if (!slotsGrid) return;
                    slotsGrid.innerHTML = `<p class="triager-book-slots-empty">${message}</p>`;
                };

                const setSelectedDateLabel = () => {
                    if (!selectedDateLabel) return;

                    if (!state.selectedDate) {
                        selectedDateLabel.textContent = 'Select an available date';
                        return;
                    }

                    const date = new Date(`${state.selectedDate}T00:00:00`);
                    selectedDateLabel.textContent = date.toLocaleDateString('en-US', {
                        weekday: 'long',
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric',
                    });
                };

                const renderCalendar = () => {
                    if (!calendarGrid) return;

                    calendarGrid.replaceChildren();
                    const year = state.viewDate.getFullYear();
                    const month = state.viewDate.getMonth();
                    const firstWeekday = new Date(year, month, 1).getDay();
                    const gridStart = new Date(year, month, 1 - firstWeekday);
                    const todayKey = toLocalDateKey(today);

                    for (let index = 0; index < 42; index += 1) {
                        const date = new Date(gridStart);
                        date.setDate(gridStart.getDate() + index);

                        const dateKey = toLocalDateKey(date);
                        const button = document.createElement('button');
                        const info = state.days.get(dateKey);
                        const outsideMonth = date.getMonth() !== month;

                        button.type = 'button';
                        button.className = 'triager-book-day';
                        button.textContent = String(date.getDate());

                        if (outsideMonth) {
                            button.classList.add('outside');
                            button.disabled = true;
                        } else if (info) {
                            button.title = info.reason;

                            if (dateKey === todayKey) button.classList.add('today');

                            if (info.holiday) {
                                button.classList.add('holiday');
                                const label = document.createElement('small');
                                label.textContent = 'Holiday';
                                button.append(label);
                            } else if (info.available) {
                                button.classList.add('available');
                                const dot = document.createElement('span');
                                dot.className = 'triager-book-day-dot';
                                button.append(dot);
                            } else {
                                button.classList.add('unavailable');
                            }

                            if (dateKey === state.selectedDate) button.classList.add('selected');

                            button.dataset.date = dateKey;
                            button.disabled = !info.available;
                        } else {
                            button.classList.add('unavailable');
                            button.disabled = true;
                        }

                        calendarGrid.append(button);
                    }

                    if (monthLabel) {
                        monthLabel.textContent = state.viewDate.toLocaleDateString('en-US', {
                            month: 'long',
                            year: 'numeric',
                        });
                    }
                    if (prevMonth) {
                        prevMonth.disabled = state.viewDate <= new Date(today.getFullYear(), today.getMonth(), 1);
                    }
                    if (nextMonth) {
                        nextMonth.disabled = state.viewDate >= maxMonth;
                    }
                };

                const loadCalendar = async () => {
                    if (!calendarGrid || !serviceField || !serviceField.value) return;

                    const requestSeq = ++state.calendarRequest;
                    clearError();
                    calendarGrid.innerHTML = '<p class="triager-book-slots-empty"><span class="spinner-border spinner-border-sm"></span> Loading availability...</p>';

                    try {
                        const response = await fetch(`${calendarUrl}?service_id=${encodeURIComponent(serviceField.value)}&month=${toMonthKey(state.viewDate)}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const payload = await response.json();

                        if (!response.ok) throw new Error(payload.message || 'Unable to load the calendar.');
                        if (requestSeq !== state.calendarRequest) return;

                        state.days = new Map(payload.days.map((day) => [day.date, day]));
                        renderCalendar();
                    } catch (error) {
                        if (requestSeq !== state.calendarRequest) return;
                        calendarGrid.innerHTML = '<p class="triager-book-slots-empty">Calendar could not be loaded. Please try again.</p>';
                        showError(error.message);
                    }
                };

                const loadTimeSlots = async (date) => {
                    if (!slotsGrid || !serviceField || !serviceField.value || !date) return;

                    const requestSeq = ++state.slotsRequest;
                    renderSlotsMessage('<span class="spinner-border spinner-border-sm"></span> Checking time slots...');

                    try {
                        const response = await fetch(`${timeslotsUrl}?service_id=${encodeURIComponent(serviceField.value)}&date=${encodeURIComponent(date)}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const slots = await response.json();

                        if (!response.ok) throw new Error(slots.error || 'Unable to load time slots.');
                        if (requestSeq !== state.slotsRequest) return;

                        slotsGrid.replaceChildren();

                        if (!Array.isArray(slots) || slots.length === 0) {
                            renderSlotsMessage('No time slots are configured for this date.');
                            return;
                        }

                        slots.forEach((slot) => {
                            const button = document.createElement('button');
                            const remaining = Number(slot.remaining);
                            const unavailable = Boolean(slot.blocked) || remaining <= 0;

                            button.type = 'button';
                            button.className = 'triager-book-slot';
                            button.textContent = slot.time_slot;
                            button.title = slot.blocked
                                ? (slot.reason || 'Unavailable')
                                : (remaining > 0 ? `${remaining} slot${remaining === 1 ? '' : 's'} left` : 'No slots remaining');

                            if (slot.blocked) {
                                button.classList.add('blocked');
                                const icon = document.createElement('i');
                                icon.className = 'bi bi-slash-circle';
                                button.prepend(icon);
                            } else if (unavailable) {
                                button.classList.add('full');
                            }

                            if (unavailable) {
                                button.disabled = true;
                            } else if (state.selectedTime === slot.time_slot) {
                                button.classList.add('selected');
                            }

                            button.addEventListener('click', () => {
                                state.selectedTime = slot.time_slot;
                                if (timeField) timeField.value = slot.time_slot;
                                clearError();
                                slotsGrid.querySelectorAll('.triager-book-slot').forEach((option) => {
                                    option.classList.toggle('selected', option === button);
                                });
                            });

                            slotsGrid.append(button);
                        });
                    } catch (error) {
                        if (requestSeq !== state.slotsRequest) return;
                        renderSlotsMessage('Time slots could not be loaded. Please try again.');
                        showError(error.message);
                    }
                };

                const resetSchedule = () => {
                    state.viewDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    state.selectedDate = '';
                    state.selectedTime = '';
                    state.days = new Map();

                    if (serviceField) serviceField.value = '';
                    if (dateField) dateField.value = '';
                    if (timeField) timeField.value = '';
                    if (scheduleSection) scheduleSection.hidden = true;
                    if (calendarGrid) calendarGrid.replaceChildren();
                    if (monthLabel) monthLabel.textContent = '—';
                    setSelectedDateLabel();
                    renderSlotsMessage('Select an available date to view time slots.');
                    clearError();
                };

                document.querySelectorAll(`[${requestAttr}]`).forEach((button) => {
                    button.addEventListener('click', () => {
                        requestId = button.getAttribute(requestAttr);
                        const patientName = button.dataset.patientName || '';
                        resetSchedule();

                        if (patientLabel) {
                            patientLabel.textContent = 'Scheduling consultation for: ' + patientName;
                        }
                        if (ageField) ageField.value = button.dataset.patientAge || '—';
                        if (complaintField) complaintField.value = button.dataset.patientComplaint || '—';
                        if (genderField) {
                            const gender = button.dataset.patientGender || '';
                            const option = document.createElement('option');
                            option.value = gender;
                            option.textContent = gender || '—';
                            genderField.replaceChildren(option);
                        }

                        bootstrap.Modal.getOrCreateInstance(modalRoot).show();
                    });
                });

                if (serviceField) {
                    serviceField.addEventListener('change', () => {
                        state.selectedDate = '';
                        state.selectedTime = '';
                        state.days = new Map();
                        if (dateField) dateField.value = '';
                        if (timeField) timeField.value = '';
                        setSelectedDateLabel();
                        renderSlotsMessage('Select an available date to view time slots.');
                        clearError();

                        if (scheduleSection) scheduleSection.hidden = !serviceField.value;

                        if (serviceField.value) {
                            loadCalendar();
                        }
                    });
                }

                if (calendarGrid) {
                    calendarGrid.addEventListener('click', (event) => {
                        const button = event.target.closest('[data-date]');
                        if (!button || button.disabled) return;

                        state.selectedDate = button.dataset.date;
                        state.selectedTime = '';
                        if (dateField) dateField.value = state.selectedDate;
                        if (timeField) timeField.value = '';
                        setSelectedDateLabel();
                        clearError();
                        renderCalendar();
                        loadTimeSlots(state.selectedDate);
                    });
                }

                const moveCalendar = (offset) => {
                    state.viewDate = new Date(
                        state.viewDate.getFullYear(),
                        state.viewDate.getMonth() + offset,
                        1,
                    );
                    state.selectedDate = '';
                    state.selectedTime = '';
                    if (dateField) dateField.value = '';
                    if (timeField) timeField.value = '';
                    setSelectedDateLabel();
                    renderSlotsMessage('Select an available date to view time slots.');
                    loadCalendar();
                };

                prevMonth?.addEventListener('click', () => moveCalendar(-1));
                nextMonth?.addEventListener('click', () => moveCalendar(1));

                if (form) {
                    form.addEventListener('submit', (event) => {
                        event.preventDefault();
                        if (!requestId) return;

                        if (!serviceField || !serviceField.value) {
                            showError('Please select a service.');
                            return;
                        }
                        if (!dateField || !dateField.value) {
                            showError('Please select a date from the calendar.');
                            return;
                        }
                        if (!timeField || !timeField.value) {
                            showError('Please select a time slot.');
                            return;
                        }

                        form.action = submitUrl.replace('__ID__', requestId);
                        form.submit();
                    });
                }
            };

            initScheduleModal({
                modalId: 'telemedScheduleModal',
                requestAttr: 'data-schedule-telemed',
                calendarUrl: '{{ route('triager.calendar.telemed', [], false) }}',
                timeslotsUrl: '{{ route('triager.timeslots.telemed', [], false) }}',
                submitUrl: '{{ route('triager.requests.schedule.telemed', ['appointment' => '__ID__']) }}',
            });
            initScheduleModal({
                modalId: 'faceScheduleModal',
                requestAttr: 'data-schedule-face',
                calendarUrl: '{{ route('triager.calendar.face', [], false) }}',
                timeslotsUrl: '{{ route('triager.timeslots.face', [], false) }}',
                submitUrl: '{{ route('triager.requests.schedule.face', ['appointment' => '__ID__']) }}',
            });
        });
    </script>
@endpush