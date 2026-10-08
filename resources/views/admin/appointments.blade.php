@extends('layouts.admin')

@section('title', $config['title'])

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    {{-- Table behavior: desktop keeps natural width, tablets get horizontal scroll (mobile.css),
       phones become stacked cards (layout script + mobile.css). --}}
    <style>
        @media (min-width: 768px) {
            .admin-doctor-table-wrap {
                overflow: visible !important;
                max-height: none !important;
            }

            .admin-doctor-table {
                width: 100%;
                min-width: 0 !important;
                table-layout: auto;
            }

            .admin-doctor-table th,
            .admin-doctor-table td {
                white-space: normal;
                overflow-wrap: anywhere;
            }
        }

        {{-- Mobile display cap: on phones (<=767.98px) this Reports table becomes a
             stack of cards, so a full page of rows can take over the screen. Only
             the first 3 data rows are shown there. Rows 4+ remain in the DOM and
             are still submitted by their form, so no report data is deleted and
             pagination continues to work; desktop and tablet (>=768px) are
             untouched and still render every record. Scoped to this page's own
             data-appointment-table-wrap so no other admin table is affected, and
             prefixed with `.admin-main` to outrank mobile.css's
             `.admin-table-stack tbody tr { display: flex }` row rule. --}}
        @media (max-width: 767.98px) {
            .admin-main [data-appointment-table-wrap] .admin-doctor-table > tbody > tr:nth-child(n+4) {
                display: none !important;
            }

            {{-- Filter dropdowns: doctor.css pins each .form-select to a 38px box and
                 app.css adds .7rem (11.2px) top/bottom padding, so once the two 1px
                 borders are taken out only a 13.6px content box is left. The
                 inherited 1.5 line-height needs 24px for the 16px mobile font, so the
                 line box no longer fits: the browser clamps it to the top of that
                 box and cuts the glyphs off at the box bottom, which is why only the
                 upper part of every label was showing. 0.85 x 16px = 13.6px makes the
                 line box fit the very same box, so the text is centred again and the
                 whole label renders. Nothing else changes - height, padding, font
                 size, colour, arrow, spacing and box size all stay exactly as they
                 were - and desktop and tablet never enter this query, so the Face to
                 Face and Telemedicine pages (which share this template) get the one
                 identical fix. --}}
            .admin-doctor-filters > .form-select {
                line-height: 0.85;
            }

            {{-- Mobile-only: the "Appointment features" trust row is hidden on phones;
                 desktop and tablet stay outside this query and render it unchanged. --}}
            .admin-doctor-content .admin-telemedicine-trust[aria-label="Appointment features"] {
                display: none !important;
            }
        }
    </style>
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
                {{-- Date range: two calendars. From can never be later than To (and the
                     other way round); the script below keeps min/max in step, and the
                     server puts a reversed range back in order as a safety net. --}}
                <label class="admin-date-filter" for="appointmentDateFrom">
                    <span>From</span>
                    <input type="date" class="form-control" id="appointmentDateFrom" name="date_from" value="{{ $filters['date_from'] ?? '' }}" @if (($filters['date_to'] ?? '') !== '') max="{{ $filters['date_to'] }}" @endif aria-label="Filter from date">
                </label>
                <label class="admin-date-filter" for="appointmentDateTo">
                    <span>To</span>
                    <input type="date" class="form-control" id="appointmentDateTo" name="date_to" value="{{ $filters['date_to'] ?? '' }}" @if (($filters['date_from'] ?? '') !== '') min="{{ $filters['date_from'] }}" @endif aria-label="Filter to date">
                </label>
                @if (($filters['search'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['doctor'] ?? '') !== '' || ($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '')
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
        <div class="modal fade admin-doctor-modal" id="viewAppointmentModal" tabindex="-1" aria-labelledby="viewAppointmentModalTitle" aria-hidden="true">
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

@push('styles')
    <style>
        /* =============================================================
           Standard Admin modal system — source of truth: the Add Patient
           modal (#createPatientModal, sized by pages/patients.css). These
           rules only restate that modal's measurements for the other
           modals on this page. Markup, behaviour and content are untouched.
           ============================================================= */

        /* One width for every Admin modal: the Add Patient dialog
           (width: calc(100% - 30px); max-width: 640px). Phones keep the
           shared calc(100% - 16px) rule from admin-css/responsive.css. */
        .admin-doctor-modal .modal-dialog {
            max-width: 640px;
        }

        /* Content spacing follows the reference (12px row gap, 4px label gap). */
        .admin-doctor-modal .admin-doctor-form {
            gap: 12px;
        }

        .admin-doctor-modal .admin-doctor-form-grid {
            gap: 12px;
        }

        .admin-doctor-modal .admin-doctor-form .form-field {
            gap: 4px;
        }

        /* Field sizing follows the reference (12px labels, 13px controls). */
        .admin-doctor-modal .admin-doctor-form .form-field > label,
        .admin-doctor-modal .admin-doctor-form .form-field > label span {
            font-size: 12px;
        }

        .admin-doctor-modal .admin-doctor-form .form-control,
        .admin-doctor-modal .admin-doctor-form .form-select {
            min-height: 0;
            padding: 0.375rem 0.5rem;
            font-size: 13px;
        }

        /* Action area mirrors the reference footer: centered, 8px apart,
           with no separator rule above the buttons. The row keeps its
           existing top spacing (margin from pages/holidays.css + padding
           from doctor.css): these rows sit inside forms, so they do not get
           the modal-body padding that separates the reference footer. */
        .admin-doctor-modal .admin-doctor-form-actions {
            justify-content: center;
            gap: 8px;
            border-top: none;
        }

        .admin-doctor-modal .modal-footer {
            justify-content: center;
            border-top: none;
            padding-top: 0;
            gap: 8px;
        }

        /* Button type matches the reference: 13px inside every modal. */
        .admin-doctor-modal .admin-primary-button,
        .admin-doctor-modal .admin-secondary-button,
        .admin-doctor-modal .admin-danger-button {
            font-size: 13px;
        }

        /* ID-scoped page rules (appointments.css) outrank the class rules
           above, so the standard typography is restated for these two
           modals. The blue panels and the field rhythm inside them (row
           padding + hairline separators) are this page's container layout
           and stay exactly as they were. */
        #addAppointmentModal .form-field > label,
        #viewAppointmentModal .form-field > label,
        #addAppointmentModal .form-field > label span {
            font-size: 12px;
        }

        #addAppointmentModal .form-control,
        #addAppointmentModal .form-select,
        #viewAppointmentModal .form-control,
        #viewAppointmentModal .form-select {
            min-height: 0;
            padding: 0.375rem 0.5rem;
            font-size: 13px;
        }

        #viewAppointmentModal .form-field p {
            font-size: 13px;
        }

        /* Phones: action buttons stay inline like the reference footer
           instead of stretching into full-width rows. */
        @media (max-width: 767.98px) {
            .admin-doctor-modal .admin-doctor-form-actions {
                flex-wrap: wrap;
            }

            .admin-doctor-modal .admin-doctor-form-actions > * {
                flex: 0 0 auto;
            }
        }

        /* Date range filter (From / To calendars in the roster filter bar).
           Each calendar is a small label + date input pair that matches the
           search box and dropdowns beside it. Scoped to .admin-date-filter so
           no other filter bar is affected. */
        .admin-doctor-filters .admin-date-filter {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0;
            color: #6a83a4;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .admin-doctor-filters .admin-date-filter .form-control {
            width: auto;
            min-width: 140px;
            border-color: #d5e4f5;
            color: #315786;
            font-size: 11px;
        }

        .admin-doctor-filters .admin-date-filter .form-control:focus {
            border-color: #55a9ff;
            box-shadow: 0 0 0 3px rgba(85, 169, 255, .14);
        }

        /* Phones: the filter bar is a single column (mobile.css), so each
           calendar takes the full row with its label above the field. */
        @media (max-width: 767.98px) {
            .admin-doctor-filters .admin-date-filter {
                flex-direction: column;
                align-items: stretch;
                gap: 4px;
            }

            .admin-doctor-filters .admin-date-filter .form-control {
                width: 100%;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function() {
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

            // Keep the two calendars consistent: From cannot be picked after To,
            // and To cannot be picked before From.
            const dateFrom = form.querySelector('input[name="date_from"]');
            const dateTo = form.querySelector('input[name="date_to"]');
            const syncDateLimits = () => {
                if (dateTo) {
                    if (dateFrom?.value) {
                        dateTo.min = dateFrom.value;
                    } else {
                        dateTo.removeAttribute('min');
                    }
                }

                if (dateFrom) {
                    if (dateTo?.value) {
                        dateFrom.max = dateTo.value;
                    } else {
                        dateFrom.removeAttribute('max');
                    }
                }
            };
            syncDateLimits();
            dateFrom?.addEventListener('change', syncDateLimits);
            dateTo?.addEventListener('change', syncDateLimits);

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