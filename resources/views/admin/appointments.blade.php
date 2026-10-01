@extends('layouts.admin')

@section('title', $config['title'])

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content admin-doctor-content">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="appointmentDirectoryTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi {{ $config['banner_icon'] }}"></i>
                    <span><i class="bi bi-clock-fill"></i></span>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="appointmentDirectoryTitle">{{ $config['title'] }}</h1>
                    <p class="admin-telemedicine-welcome">Appointment Management</p>
                    <p class="admin-telemedicine-description">{{ $config['description'] }} Review, approve, reschedule, and manage patient appointments.</p>
                    <div class="admin-telemedicine-trust" aria-label="Appointment features">
                        <span><i class="bi bi-calendar2-check" aria-hidden="true"></i> Scheduling</span>
                        <b aria-hidden="true">&bull;</b>
                        <span>Status Tracking</span>
                        <b aria-hidden="true">&bull;</b>
                        <span>Patient Care</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="admin-doctor-stat-grid admin-appointment-stat-grid">
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi bi-calendar2-week-fill" aria-hidden="true"></i></span>
                <span><small>Total appointments</small><strong data-appointment-stat="total">{{ number_format($appointmentStats['total'] ?? 0) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon orange"><i class="bi bi-calendar2-check-fill" aria-hidden="true"></i></span>
                <span><small>Booked</small><strong data-appointment-stat="booked">{{ number_format($appointmentStats['booked'] ?? 0) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon green"><i class="bi bi-check-circle-fill" aria-hidden="true"></i></span>
                <span><small>Approved</small><strong data-appointment-stat="approved">{{ number_format($appointmentStats['approved'] ?? 0) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon red"><i class="bi bi-x-circle-fill" aria-hidden="true"></i></span>
                <span><small>Cancelled</small><strong data-appointment-stat="cancelled">{{ number_format($appointmentStats['cancelled'] ?? 0) }}</strong></span>
            </article>
            <button class="admin-doctor-add-button admin-doctor-stat-action" type="button" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>New appointment</span>
            </button>
        </div>

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="appointmentRosterTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                    <h2 id="appointmentRosterTitle">Appointment roster</h2>
                </div>
                <span class="admin-muted-text" data-appointment-result-count>{{ $appointments->total() ?? 0 }} appointment{{ ($appointments->total() ?? 0) === 1 ? '' : 's' }}</span>
            </header>

            <form class="admin-doctor-filters" method="GET" action="{{ route("admin.appointments.{$serviceType}") }}" data-appointment-filters>
                <div class="admin-doctor-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="appointmentSearch">Search appointments</label>
                    <input id="appointmentSearch" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search patient, doctor, or service...">
                </div>
                <select class="form-select" name="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <option value="Approved" @selected(($filters['status'] ?? '') === 'Approved')>Approved</option>
                    <option value="Cancelled" @selected(($filters['status'] ?? '') === 'Cancelled')>Cancelled</option>
                    <option value="Booked" @selected(($filters['status'] ?? '') === 'Booked')>Booked</option>
                </select>
                <select class="form-select" name="doctor" aria-label="Filter by doctor">
                    <option value="">All doctors</option>
                    @foreach ($doctors ?? [] as $doc)
                        <option value="{{ $doc->id }}" @selected(($filters['doctor'] ?? '') == $doc->id)>{{ $doc->full_name }}</option>
                    @endforeach
                </select>
                <input type="date" class="form-control" name="date" value="{{ $filters['date'] ?? '' }}" aria-label="Filter by date" style="width: auto;">
                @if (($filters['search'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['doctor'] ?? '') !== '' || ($filters['date'] ?? '') !== '')
                    <a class="admin-clear-filter" href="{{ route("admin.appointments.{$serviceType}") }}">Clear</a>
                @endif
            </form>

            <div class="admin-doctor-table-wrap" data-appointment-table-wrap>
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">{{ $config['title'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">Patient</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Schedule</th>
                            <th scope="col">Type</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments ?? [] as $appointment)
                            <tr>
                                <td>
                                    <div class="admin-doctor-person">
                                        <span class="admin-avatar">{{ strtoupper(substr((string) ($appointment->patient?->first_name ?: 'P'), 0, 1).substr((string) ($appointment->patient?->last_name ?: 'A'), 0, 1)) }}</span>
                                        <span>
                                            <strong>{{ $appointment->patient->full_name ?? 'Unknown patient' }}</strong>
                                            <small>{{ $appointment->patient->email ?? 'No email' }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $appointment->staff->full_name ?? 'Unassigned' }}</span>
                                    <small class="admin-doctor-secondary-text">{{ $appointment->staff->specialization ?? '' }}</small>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $appointment->date?->format('M d, Y') ?? 'No date' }}</span>
                                    <small class="admin-doctor-secondary-text">{{ $appointment->time_slot ?? 'No time' }}</small>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $appointment->mode ?? 'N/A' }}</span>
                                    <small class="admin-doctor-secondary-text">{{ ($appointment->mode === 'TELE' ? $appointment->serviceTele?->service_name : $appointment->service?->service_name) ?? '' }}</small>
                                </td>
                                <td>
                                    <span class="admin-status-pill {{ strtolower(str_replace(' ', '-', $appointment->status)) }}">
                                        {{ $appointment->status === 'Completed' ? 'Approved' : $appointment->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-doctor-actions">
                                        <a href="{{ route("admin.appointments.{$serviceType}", ['view' => $appointment->id]) }}" aria-label="View appointment">View</a>
                                        @if ($appointment->status === 'Pending')
                                            <button type="button" class="appointment-action-btn" data-action="approve" data-id="{{ $appointment->id }}">Approve</button>
                                            <button type="button" class="appointment-action-btn" data-action="reject" data-id="{{ $appointment->id }}">Reject</button>
                                        @elseif ($appointment->status === 'Approved')
                                            <button type="button" class="appointment-action-btn" data-action="confirm" data-id="{{ $appointment->id }}">Confirm</button>
                                            <button type="button" class="appointment-action-btn" data-action="cancel" data-id="{{ $appointment->id }}">Cancel</button>
                                        @elseif ($appointment->status === 'Confirmed')
                                            <button type="button" class="appointment-action-btn" data-action="start" data-id="{{ $appointment->id }}">Start</button>
                                            <button type="button" class="appointment-action-btn" data-action="cancel" data-id="{{ $appointment->id }}">Cancel</button>
                                        @elseif ($appointment->status === 'In Progress')
                                            <button type="button" class="appointment-action-btn" data-action="complete" data-id="{{ $appointment->id }}">Complete</button>
                                        @endif
                                        @if (in_array($appointment->status, ['Pending', 'Booked', 'Approved', 'Confirmed'], true))
                                            <button type="button" class="appointment-edit-btn" data-id="{{ $appointment->id }}">Edit</button>
                                        @endif
                                        @if (in_array($appointment->status, ['Pending', 'Rejected', 'Cancelled'], true))
                                            <button type="button" class="appointment-delete-btn" data-id="{{ $appointment->id }}">Delete</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                                        <strong>No appointments found</strong>
                                        <span>Adjust the filters or create a new appointment.</span>
                                        <a href="{{ route("admin.appointments.{$serviceType}", ['create' => 1]) }}" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">New appointment</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-doctor-pagination" data-appointment-pagination @if (! ($appointments->hasPages() ?? false)) hidden @endif>
                @if ($appointments->hasPages() ?? false)
                    @if ($appointments->onFirstPage())
                        <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&laquo;</span>
                    @else
                        <a class="admin-doctor-page-button" href="{{ $appointments->previousPageUrl() }}" aria-label="Previous page">&laquo;</a>
                    @endif
                    <span class="admin-doctor-page-current" aria-current="page">{{ $appointments->currentPage() }}</span>
                    @if ($appointments->hasMorePages())
                        <a class="admin-doctor-page-button" href="{{ $appointments->nextPageUrl() }}" aria-label="Next page">&raquo;</a>
                    @else
                        <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&raquo;</span>
                    @endif
                @endif
            </div>
        </section>
    </div>

    <!-- Add / Edit Appointment Modal -->
    <div class="modal fade admin-doctor-modal" id="addAppointmentModal" tabindex="-1" aria-labelledby="addAppointmentModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-calendar2-plus-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="addAppointmentModalTitle">New appointment</h2>
                            <p class="admin-telemedicine-description">Schedule a new {{ $mode === 'TELE' ? 'telemedicine' : 'face-to-face' }} appointment.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <form id="addAppointmentForm" class="admin-doctor-form" method="POST" action="{{ route("admin.appointments.{$serviceType}.store") }}">
                        @csrf
                        {{-- Mode is fixed by the page. The server also forces it, so this value is not trusted. --}}
                        <input type="hidden" id="appointmentMode" name="mode" value="{{ $mode }}">
                        <div class="admin-panel">
                            <div class="admin-panel-title">
                                <i class="bi bi-person-fill" aria-hidden="true"></i>
                                <h3>Patient & Schedule</h3>
                            </div>
                            <div class="form-field">
                                <label for="appointmentPatient">Patient <span>*</span></label>
                                <select class="form-select" id="appointmentPatient" name="patient_id" required>
                                    <option value="">Select patient</option>
                                @foreach ($patients ?? [] as $patient)
                                    <option value="{{ $patient->id }}">{{ trim(implode(' ', array_filter([$patient->first_name, $patient->middlename, $patient->last_name]))) ?: 'Unnamed patient' }}</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="appointmentDoctor">Doctor <span>*</span></label>
                                <select class="form-select" id="appointmentDoctor" name="staff_id" required>
                                    <option value="">Select doctor</option>
                                    @foreach ($doctors ?? [] as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->full_name }} &mdash; {{ $doc->specialization ?? 'General' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="appointmentDate">Date <span>*</span></label>
                                <input type="date" class="form-control" id="appointmentDate" name="date" required>
                            </div>
                            <div class="form-field">
                                <label for="appointmentTime">Time slot <span>*</span></label>
                                <input type="time" class="form-control" id="appointmentTime" name="time_slot" required>
                            </div>
                            <div class="form-field">
                                <label for="appointmentService">Service <span>*</span></label>
                                <select class="form-select" id="appointmentService" name="service_id" required>
                                    <option value="">Select service</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="appointmentReason">Reason for visit</label>
                                <textarea class="form-control" id="appointmentReason" name="consultation_reason" rows="3" placeholder="Brief description..."></textarea>
                            </div>
                        </div>
                        <div class="admin-doctor-form-actions">
                            <button type="submit" class="admin-primary-button">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                <span>Save appointment</span>
                            </button>
                            <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Appointment Modal -->
    @if (($providerModal ?? null) === 'view')
        <div class="modal fade admin-doctor-modal modal-wide" id="viewAppointmentModal" tabindex="-1" aria-labelledby="viewAppointmentModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <header class="modal-header admin-doctor-modal-header">
                        <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                        <div class="admin-telemedicine-content">
                            <div class="admin-telemedicine-mark" aria-hidden="true">
                                <i class="bi bi-calendar2-check-fill"></i>
                            </div>
                            <div class="admin-telemedicine-copy">
                                <h2 class="modal-title" id="viewAppointmentModalTitle">Appointment details</h2>
                                <p class="admin-telemedicine-description">Review appointment information and history.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </header>
                    <div class="modal-body">
                        @if ($appointmentDetail ?? null)
                            <div class="admin-doctor-profile-hero">
                                <span class="admin-avatar">{{ strtoupper(substr((string) ($appointmentDetail->patient?->first_name ?: 'P'), 0, 1).substr((string) ($appointmentDetail->patient?->last_name ?: 'A'), 0, 1)) }}</span>
                                <div>
                                    <h2>{{ $appointmentDetail->patient->full_name ?? 'Unknown patient' }}</h2>
                                    <p>{{ $appointmentDetail->patient->email ?? '' }} &bull; {{ $appointmentDetail->patient->contact_number ?? 'No phone' }}</p>
                                </div>
                                <span class="admin-status-pill {{ strtolower(str_replace(' ', '-', $appointmentDetail->status)) }}">
                                    {{ $appointmentDetail->status === 'Completed' ? 'Approved' : $appointmentDetail->status }}
                                </span>
                            </div>
                            <div class="admin-panel">
                                <div class="admin-panel-title">
                                    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                                    <h3>Appointment details</h3>
                                </div>
                                <div class="admin-doctor-form">
                                    <div class="form-field">
                                        <label>Doctor</label>
                                        <p>{{ $appointmentDetail->staff->full_name ?? 'Unassigned' }}</p>
                                    </div>
                                    <div class="form-field">
                                        <label>Date & Time</label>
                                        <p>{{ $appointmentDetail->date?->format('F d, Y') ?? 'No date' }} at {{ $appointmentDetail->time_slot ?? 'No time' }}</p>
                                    </div>
                                    <div class="form-field">
                                        <label>Mode</label>
                                        <p>{{ $appointmentDetail->mode ?? 'N/A' }}</p>
                                    </div>
                                    <div class="form-field">
                                        <label>Service</label>
                                        <p>{{ ($appointmentDetail->mode === 'TELE' ? $appointmentDetail->serviceTele?->service_name : $appointmentDetail->service?->service_name) ?? 'N/A' }}</p>
                                    </div>
                                    <div class="form-field">
                                        <label>Reason</label>
                                        <p>{{ $appointmentDetail->consultation_reason ?? 'No reason provided' }}</p>
                                    </div>
                                    <div class="form-field">
                                        <label>Symptoms</label>
                                        <p>{{ is_array($appointmentDetail->symptoms) ? implode(', ', $appointmentDetail->symptoms) : ($appointmentDetail->symptoms ?? 'None recorded') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $(function() {
            // Modal state management
            const modalStates = [
                { element: document.getElementById('addAppointmentModal'), parameter: 'create' },
                { element: document.getElementById('viewAppointmentModal'), parameter: 'view' },
            ].filter(({ element }) => element);
            const currentSearch = new URLSearchParams(window.location.search);
            const initialModalState = modalStates.find(({ parameter }) => currentSearch.has(parameter));

            modalStates.forEach(({ element, parameter }) => {
                const modal = bootstrap.Modal.getOrCreateInstance(element, {
                    backdrop: 'static',
                    keyboard: false,
                });

                element.addEventListener('show.bs.modal', () => {
                    const url = new URL(window.location.href);
                    modalStates.forEach(({ parameter: modalParameter }) => {
                        if (modalParameter !== parameter) {
                            url.searchParams.delete(modalParameter);
                        }
                    });
                    if (!url.searchParams.has(parameter)) {
                        url.searchParams.set(parameter, '1');
                    }
                    if (url.href !== window.location.href) {
                        window.history.pushState({}, '', url);
                    }
                });

                element.addEventListener('hidden.bs.modal', () => {
                    const url = new URL(window.location.href);
                    if (url.searchParams.has(parameter)) {
                        url.searchParams.delete(parameter);
                        window.history.replaceState({}, '', url);
                    }
                });

                if (initialModalState?.element === element) {
                    modal.show();
                }
            });

            // Filter form AJAX
            const form = document.querySelector('[data-appointment-filters]');
            if (!form) {
                return;
            }

            const searchInput = form.querySelector('input[name="search"]');
            const tableWrap = document.querySelector('[data-appointment-table-wrap]');
            const resultCount = document.querySelector('[data-appointment-result-count]');
            const pagination = document.querySelector('[data-appointment-pagination]');
            let debounceTimer;
            let requestController;

            const updateFilters = async (url = null) => {
                const query = new URLSearchParams(new FormData(form)).toString();
                const targetUrl = url ?? `${form.action}${query ? `?${query}` : ''}`;

                requestController?.abort();
                requestController = new AbortController();

                try {
                    const response = await fetch(targetUrl, {
                        headers: { Accept: 'text/html' },
                        credentials: 'same-origin',
                        signal: requestController.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load appointments.');
                    }

                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const nextTable = page.querySelector('[data-appointment-table-wrap]');
                    const nextCount = page.querySelector('[data-appointment-result-count]');
                    const nextPagination = page.querySelector('[data-appointment-pagination]');

                    page.querySelectorAll('[data-appointment-stat]').forEach((next) => {
                        const current = document.querySelector(`[data-appointment-stat="${next.dataset.appointmentStat}"]`);
                        if (current) {
                            current.textContent = next.textContent;
                        }
                    });

                    if (tableWrap && nextTable) {
                        tableWrap.innerHTML = nextTable.innerHTML;
                    }
                    if (resultCount && nextCount) {
                        resultCount.textContent = nextCount.textContent;
                    }
                    if (pagination) {
                        pagination.innerHTML = nextPagination?.innerHTML ?? '';
                        pagination.hidden = nextPagination?.hidden ?? true;
                    }

                    window.history.replaceState({}, '', targetUrl);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        form.submit();
                    }
                } finally {
                    requestController = null;
                }
            };

            const scheduleFilterUpdate = () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(() => updateFilters(), 300);
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                updateFilters();
            });

            form.querySelectorAll('input, select').forEach((control) => {
                control.addEventListener('input', scheduleFilterUpdate);
                control.addEventListener('change', () => updateFilters());
            });

            document.addEventListener('click', (event) => {
                const target = event.target;
                const clickedPagination = target.closest('[data-appointment-pagination]');
                const appointmentModalTrigger = target.closest('[data-bs-target="#addAppointmentModal"]');
                const appointmentButton = target.closest('.appointment-action-btn, .appointment-edit-btn, .appointment-delete-btn');
                const appointmentModalOpen = modalStates.some(({ element }) => element.classList.contains('show'));

                if (form.contains(target) || clickedPagination || appointmentModalOpen || appointmentModalTrigger || appointmentButton) {
                    return;
                }

                if (searchInput && searchInput.value !== '') {
                    searchInput.value = '';
                    updateFilters();
                }
            });

            pagination?.addEventListener('click', (event) => {
                const link = event.target.closest('a');
                if (!link) {
                    return;
                }
                event.preventDefault();
                updateFilters(link.href);
            });

            // ---- Appointment CRUD via jQuery AJAX ----
            const appointmentRoutes = {
                show: @json(route('admin.appointments.show', ['id' => '__ID__'])),
                update: @json(route('admin.appointments.update', ['id' => '__ID__'])),
                destroy: @json(route('admin.appointments.destroy', ['id' => '__ID__'])),
                action: @json(route('admin.appointments.action', ['id' => '__ID__', 'action' => '__ACTION__'])),
            };
            const csrfToken = '{{ csrf_token() }}';
            const modalEl = document.getElementById('addAppointmentModal');
            const $modalTitle = $('#addAppointmentModalTitle');
            const $apptForm = $('#addAppointmentForm');
            const $submitBtn = $apptForm.find('button[type="submit"]');
            const createUrl = $apptForm.attr('action');
            const actionLabels = {
                approve: 'Approve', reject: 'Reject', confirm: 'Confirm',
                cancel: 'Cancel', start: 'Start', complete: 'Complete',
            };

            const notify = (type, message) => {
                if (window.toastr && typeof window.toastr[type] === 'function') {
                    window.toastr[type](message);
                } else {
                    alert(message);
                }
            };
            const errorMessage = (xhr) => xhr.responseJSON?.message ?? 'An error occurred. Please try again.';
            const urlFor = (template, id, action = '') => template.replace('__ID__', id).replace('__ACTION__', action);

            const clearErrors = () => {
                $apptForm.find('.appointment-field-error').remove();
                $apptForm.find('.is-invalid').removeClass('is-invalid');
            };
            const showErrors = (errors) => {
                $.each(errors, (field, messages) => {
                    const $input = $apptForm.find(`[name="${field}"]`).addClass('is-invalid');
                    $('<div class="appointment-field-error text-danger small mt-1"></div>')
                        .text(messages[0])
                        .insertAfter($input);
                });
            };

            const servicesByMode = {
                FACE: @json(($services ?? collect())->map(fn ($s) => ['id' => $s->id, 'name' => $s->service_name])->values()),
                TELE: @json(($servicesTele ?? collect())->map(fn ($s) => ['id' => $s->id, 'name' => $s->service_name])->values()),
            };
            // Hidden input fixed to this page's type (FACE or TELE).
            const $mode = $('#appointmentMode');
            const $service = $('#appointmentService');
            const populateServices = (mode, selected = '') => {
                $service.empty().append('<option value="">Select service</option>');
                (servicesByMode[mode] ?? []).forEach((s) => $service.append($('<option>').val(s.id).text(s.name)));
                $service.val(String(selected));
            };
            populateServices($mode.val());

            const setFormMode = (id = null) => {
                $apptForm[0].reset();
                clearErrors();
                populateServices($mode.val());
                $apptForm.data('appointmentId', id);
                $modalTitle.text(id ? 'Edit appointment' : 'New appointment');
                $submitBtn.find('span').text(id ? 'Update appointment' : 'Save appointment');
            };

            $(modalEl).on('hidden.bs.modal', () => setFormMode());

            // Create + Update
            $apptForm.off('submit.appointmentCrud').on('submit.appointmentCrud', function (event) {
                event.preventDefault();
                if ($submitBtn.prop('disabled')) {
                    return;
                }

                const id = $apptForm.data('appointmentId');
                const label = $submitBtn.find('span').text();

                clearErrors();
                $submitBtn.prop('disabled', true).find('span').text('Saving...');

                $.ajax({
                    url: id ? urlFor(appointmentRoutes.update, id) : createUrl,
                    method: 'POST',
                    data: $apptForm.serialize() + (id ? '&_method=PUT' : ''),
                    dataType: 'json',
                }).done(function (response) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    notify('success', response.message);
                    updateFilters();
                }).fail(function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors(xhr.responseJSON.errors);
                    } else {
                        notify('error', errorMessage(xhr));
                    }
                }).always(function () {
                    $submitBtn.prop('disabled', false).find('span').text(label);
                });
            });

            // Read one appointment into the edit form
            $(document).off('click.appointmentEdit').on('click.appointmentEdit', '.appointment-edit-btn', function () {
                const btn = $(this);
                if (btn.prop('disabled')) {
                    return;
                }
                btn.prop('disabled', true);

                $.getJSON(urlFor(appointmentRoutes.show, btn.data('id')))
                    .done(function (a) {
                        setFormMode(a.id);
                        const time = /^\d{2}:\d{2}/.test(a.time_slot ?? '') ? a.time_slot.slice(0, 5) : '';
                        $apptForm.find('[name="patient_id"]').val(a.patient_id);
                        $apptForm.find('[name="staff_id"]').val(a.staff_id);
                        $apptForm.find('[name="date"]').val(a.date);
                        $apptForm.find('[name="time_slot"]').val(time);
                        $mode.val(a.mode);
                        populateServices(a.mode, a.service_id);
                        $apptForm.find('[name="consultation_reason"]').val(a.consultation_reason ?? '');
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    })
                    .fail(function (xhr) {
                        notify('error', errorMessage(xhr));
                    })
                    .always(function () {
                        btn.prop('disabled', false);
                    });
            });

            // Delete
            $(document).off('click.appointmentDelete').on('click.appointmentDelete', '.appointment-delete-btn', function () {
                const btn = $(this);
                if (btn.prop('disabled') || !confirm('Delete this appointment permanently? This cannot be undone.')) {
                    return;
                }
                btn.prop('disabled', true).text('Deleting...');

                $.ajax({
                    url: urlFor(appointmentRoutes.destroy, btn.data('id')),
                    method: 'POST',
                    data: { _method: 'DELETE', _token: csrfToken },
                    dataType: 'json',
                }).done(function (response) {
                    notify('success', response.message);
                    updateFilters();
                }).fail(function (xhr) {
                    notify('error', errorMessage(xhr));
                    btn.prop('disabled', false).text('Delete');
                });
            });

            // Status actions (approve, reject, confirm, cancel, start, complete)
            $(document).off('click.appointmentActions').on('click.appointmentActions', '.appointment-action-btn', function () {
                const btn = $(this);
                const action = btn.data('action');
                const id = btn.data('id');

                if (btn.prop('disabled') || !confirm(`Are you sure you want to ${(actionLabels[action] ?? action).toLowerCase()} this appointment?`)) {
                    return;
                }
                btn.prop('disabled', true).text('Processing...');

                $.ajax({
                    url: urlFor(appointmentRoutes.action, id, action),
                    method: 'POST',
                    data: { _token: csrfToken },
                    dataType: 'json',
                }).done(function (response) {
                    notify('success', response.message);
                    updateFilters();
                }).fail(function (xhr) {
                    notify('error', errorMessage(xhr));
                    btn.prop('disabled', false).text(actionLabels[action] ?? action);
                });
            });
        });
    </script>
@endpush