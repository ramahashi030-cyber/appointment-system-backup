@php
    $activeModalAppointment = $activeAppointment ?? null;
    $activeModalDate = $activeModalAppointment['date'] ?? '—';
    $activeModalTime = $activeModalAppointment['time_slot'] ?? '—';
    $activeModalService = $activeModalAppointment['service_name'] ?? 'Telemedicine consultation';
    $autoOpenActiveModal = ($autoOpenActiveModal ?? false) && ! empty($activeModalAppointment['id']);
@endphp

<div
    class="modal fade dashboard-modal dashboard-active-appointment-modal"
    id="activeAppointmentModal"
    tabindex="-1"
    aria-labelledby="activeAppointmentModalTitle"
    aria-hidden="true"
    data-auto-open="{{ $autoOpenActiveModal ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dashboard-modal-content">
            <header class="active-appointment-header">
                <span aria-hidden="true"><i class="bi bi-calendar2-x-fill"></i></span>
                <button
                    type="button"
                    class="dashboard-modal-close"
                    data-bs-dismiss="modal"
                    aria-label="Close active appointment notice"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            <div class="modal-body active-appointment-body">
                <span class="active-appointment-badge">Booking unavailable</span>
                <h2 id="activeAppointmentModalTitle">You already have an active appointment</h2>
                <p data-active-appointment-message>
                    You already have an active appointment on
                    <strong data-active-appointment-date>{{ $activeModalDate }}</strong>
                    at <strong data-active-appointment-time>{{ $activeModalTime }}</strong>.
                    Cancel or complete it before booking another visit.
                </p>

                <div class="active-appointment-summary">
                    <span>
                        <i class="bi bi-heart-pulse" aria-hidden="true"></i>
                        <strong data-active-appointment-service>{{ $activeModalService }}</strong>
                    </span>
                    <span>
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <strong data-active-appointment-status>{{ $activeModalAppointment['status'] ?? 'Booked' }}</strong>
                    </span>
                </div>

                <p class="active-appointment-note">
                    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                    You can cancel it below, or ask the clinic staff to complete it before making another booking.
                </p>
            </div>

            <footer class="active-appointment-footer">
                <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Close</button>
                <a class="dashboard-modal-button primary" href="{{ route('telemed.mine') }}">
                    <i class="bi bi-calendar3" aria-hidden="true"></i> View appointment
                </a>
                <button
                    type="button"
                    class="dashboard-modal-button danger"
                    data-open-appointment-cancel
                    data-cancel-id="{{ $activeModalAppointment['id'] ?? '' }}"
                    @if (empty($activeModalAppointment['id'])) hidden @endif
                    data-active-appointment-cancel
                >
                    <i class="bi bi-x-circle" aria-hidden="true"></i> Cancel appointment
                </button>
            </footer>
        </div>
    </div>
</div>
