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

            // Telemed schedule modal
            const telemedModal = document.getElementById('telemedScheduleModal');
            const telemedForm = document.getElementById('telemedScheduleForm');
            const telemedService = document.getElementById('telemedService');
            const telemedDate = document.getElementById('telemedDate');
            const telemedTimeSlot = document.getElementById('telemedTimeSlot');
            const telemedError = document.querySelector('[data-telemed-error]');
            const telemedPatientLabel = document.querySelector('[data-telemed-patient-label]');
            let telemedRequestId = null;

            document.querySelectorAll('[data-schedule-telemed]').forEach((button) => {
                button.addEventListener('click', () => {
                    telemedRequestId = button.dataset.scheduleTelemed;
                    const patientName = button.dataset.patientName || '';
                    if (telemedPatientLabel) {
                        telemedPatientLabel.textContent = 'Scheduling telemed consultation for: ' + patientName;
                    }
                    if (telemedError) telemedError.hidden = true;
                    const modal = new bootstrap.Modal(telemedModal);
                    modal.show();
                });
            });

            if (telemedService && telemedDate && telemedTimeSlot) {
                const loadTelemedSlots = async () => {
                    if (!telemedService.value || !telemedDate.value) return;
                    telemedTimeSlot.innerHTML = '<option value="">Loading...</option>';
                    try {
                        const response = await fetch('/triager/timeslots/telemed?service_id=' + telemedService.value + '&date=' + telemedDate.value);
                        const slots = await response.json();
                        telemedTimeSlot.innerHTML = '<option value="">Select a time slot</option>';
                        slots.forEach((slot) => {
                            const option = document.createElement('option');
                            option.value = slot.time_slot;
                            option.textContent = slot.time_slot + ' (' + slot.remaining + ' slots left)';
                            if (slot.blocked) {
                                option.disabled = true;
                                option.textContent += ' — Unavailable';
                            }
                            telemedTimeSlot.appendChild(option);
                        });
                    } catch {
                        telemedTimeSlot.innerHTML = '<option value="">Error loading slots</option>';
                    }
                };
                telemedService.addEventListener('change', loadTelemedSlots);
                telemedDate.addEventListener('change', loadTelemedSlots);
            }

            if (telemedForm) {
                telemedForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    if (!telemedRequestId) return;
                    telemedForm.action = '{{ route('triager.requests.schedule.telemed', ['appointment' => '__ID__']) }}'.replace('__ID__', telemedRequestId);
                    telemedForm.submit();
                });
            }

            // Face-to-face schedule modal
            const faceModal = document.getElementById('faceScheduleModal');
            const faceForm = document.getElementById('faceScheduleForm');
            const faceService = document.getElementById('faceService');
            const faceDate = document.getElementById('faceDate');
            const faceTimeSlot = document.getElementById('faceTimeSlot');
            const faceError = document.querySelector('[data-face-error]');
            const facePatientLabel = document.querySelector('[data-face-patient-label]');
            let faceRequestId = null;

            document.querySelectorAll('[data-schedule-face]').forEach((button) => {
                button.addEventListener('click', () => {
                    faceRequestId = button.dataset.scheduleFace;
                    const patientName = button.dataset.patientName || '';
                    if (facePatientLabel) {
                        facePatientLabel.textContent = 'Scheduling face-to-face consultation for: ' + patientName;
                    }
                    if (faceError) faceError.hidden = true;
                    const modal = new bootstrap.Modal(faceModal);
                    modal.show();
                });
            });

            if (faceService && faceDate && faceTimeSlot) {
                const loadFaceSlots = async () => {
                    if (!faceService.value || !faceDate.value) return;
                    faceTimeSlot.innerHTML = '<option value="">Loading...</option>';
                    try {
                        const response = await fetch('/triager/timeslots/face?service_id=' + faceService.value + '&date=' + faceDate.value);
                        const slots = await response.json();
                        faceTimeSlot.innerHTML = '<option value="">Select a time slot</option>';
                        slots.forEach((slot) => {
                            const option = document.createElement('option');
                            option.value = slot.time_slot;
                            option.textContent = slot.time_slot + ' (' + slot.remaining + ' slots left)';
                            if (slot.blocked) {
                                option.disabled = true;
                                option.textContent += ' — Unavailable';
                            }
                            faceTimeSlot.appendChild(option);
                        });
                    } catch {
                        faceTimeSlot.innerHTML = '<option value="">Error loading slots</option>';
                    }
                };
                faceService.addEventListener('change', loadFaceSlots);
                faceDate.addEventListener('change', loadFaceSlots);
            }

            if (faceForm) {
                faceForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    if (!faceRequestId) return;
                    faceForm.action = '{{ route('triager.requests.schedule.face', ['appointment' => '__ID__']) }}'.replace('__ID__', faceRequestId);
                    faceForm.submit();
                });
            }
        });
    </script>
@endpush