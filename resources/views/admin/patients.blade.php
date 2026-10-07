@extends('layouts.admin')

@section('title', 'Patients')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@push('styles')
    <style>
        /* Patient view modal. Scoped to #viewPatientModal because the profile markup is
           rendered by this file while its base styles live in admin-css/doctor.css. */

        /* Values wrap instead of being clipped. The base rule pins every
           .admin-doctor-profile-details strong to a single line and hides the
           overflow behind an ellipsis, which cut a long address down to a few
           characters. Identifiers (dates, numbers, emails) are short, so nothing
           else changes shape. */
        #viewPatientModal .admin-doctor-profile-details strong {
            overflow: visible;
            text-overflow: clip;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        /* Registered sits directly under Hospital number, which is the sixth cell
           of the three-column identity grid. Pinning Registered to the same column
           in the next row does that while Address keeps its slot beside it. */
        #viewPatientModal .admin-patient-profile-address-cell {
            grid-row: 3;
            grid-column: 1 / 3;
        }

        #viewPatientModal .admin-patient-profile-registered-cell {
            grid-row: 3;
            grid-column: 3;
        }

        /* On phones the grid collapses to one column (admin-css/responsive.css),
           where an explicit row/column would leave an empty third column, so the
           cells fall back to the source order with Address last. */
        @media (max-width: 767px) {
            #viewPatientModal .admin-patient-profile-address-cell,
            #viewPatientModal .admin-patient-profile-registered-cell {
                grid-row: auto;
                grid-column: auto;
            }

            #viewPatientModal .admin-patient-profile-address-cell { order: 2; }
            #viewPatientModal .admin-patient-profile-registered-cell { order: 1; }
        }

        /* History footers: the range summary and the page controls sit on one row,
           summary first, with the controls wrapping underneath on narrow screens. */
        #viewPatientModal .admin-patient-view-pagination {
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 10px;
        }

        #viewPatientModal .admin-patient-view-pagination-summary {
            margin: 0;
            color: #6a83a4;
            font-size: 12px;
        }

        #viewPatientModal .admin-patient-view-pagination-summary span {
            color: #0a326c;
            font-weight: 600;
        }

        /* Both histories show at most five records per page, so the body area is
           reserved for five rows and a page with fewer records (the last one, or
           any page after a filter change) keeps the same physical height instead
           of shrinking the table.

           The reservation is derived from the row box the table already uses, not
           from a guessed constant: .admin-doctor-table td carries 14px of vertical
           padding and a history row stacks two 15px lines inside
           .admin-doctor-appointment-patient, so one row is 28px + 2 * 18px = 64px
           (about the 65px a full page measures). The header row adds 24px of
           padding plus one 13px line, so 40px + 5 * 64px = 360px covers the header
           and a full page of records.

           No filler rows are added. The space below the last record is genuinely
           empty, so it is not counted in the result total, announced by a screen
           reader, focusable or clickable. The background matches the row
           background so the reserved area reads as a continuation of the body. An
           empty dataset is left out: it shows its own empty state instead. */
        #viewPatientModal .admin-patient-view-table-wrap:not(.is-empty) {
            min-height: calc(40px + (5 * 64px));
            background: #fff;
        }

        /* Vertical separation between the two history sections.

           Appointment history and Consultation history are the only children of
           .admin-doctor-activity-grid in this modal, and the grid collapses to a
           single column here, so the two panels stack and the grid's row gap is the
           only thing separating them. That gap arrives from the shared
           .admin-doctor-activity-grid rule, which is written for the two-column
           doctor listings rather than for one panel above the other, so this modal
           states the value it actually wants instead of inheriting it.

           The gap sits on the grid, between the panels, and on no panel at all: no
           margin is added to either section, no padding is added to the modal, and
           no table, row, header, footer, control or result count is touched. Because
           both tables reserve their body area for five rows, a page holding 1, 2, 3,
           4 or 5 records leaves this gap at exactly the same 12px. */
        #viewPatientModal .admin-doctor-activity-grid {
            row-gap: 12px;
        }

        /* Same 12px separation below Medical information as the gap between
           Appointment history and Consultation history. It is set on this one
           panel, so the profile panel and the records panel are unaffected. */
        #viewPatientModal .admin-patient-medical-panel {
            margin-bottom: 12px;
        }

        /* Compact controls (last page, one page back, current page, one page
           forward). The buttons reuse .admin-doctor-page-button /
           .admin-doctor-page-current from admin-css/doctor.css, the same pair the
           other admin listings use; only the row layout lives here. */
        #viewPatientModal .admin-patient-view-pagination-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 7px;
            margin: 0;
        }

        /* The controls are real buttons so they cannot navigate, but the browser
           would give them their own padding, font and cursor on top of the shared
           .admin-doctor-page-button styling. This keeps them looking exactly like
           the anchors they replaced. */
        #viewPatientModal .admin-patient-view-pagination-controls .admin-doctor-page-button {
            padding: 0;
            font-family: inherit;
            line-height: 1;
            cursor: pointer;
        }

        /* A control with nothing left to show is muted instead of merely inert,
           matching .admin-doctor-page-button.is-disabled from admin-css/doctor.css. */
        #viewPatientModal .admin-patient-view-pagination-controls .admin-doctor-page-button:disabled,
        #viewPatientModal .admin-patient-view-pagination-controls .admin-doctor-page-button:disabled:hover {
            border-color: #e4edf7;
            background: #f7faff;
            color: #b1c3d8;
            cursor: default;
        }

        {{-- Mobile display cap: below 768px this roster renders as a stack of cards
             and a full page of patients can push everything else off screen, so
             only the first 3 data rows are made visible there. Rows 4+ stay in the
             DOM (still submitted by their form, still reachable through the
             existing pagination), so nothing is deleted. Desktop and tablet are
             outside this query and keep rendering all records unchanged. Scoped to
             .admin-patients-page so the history/modal tables are unaffected, and
             prefixed with .admin-main so it outranks mobile.css's
             `.admin-table-stack tbody tr { display: flex }` row rule. --}}
        @media (max-width: 767.98px) {
            .admin-main .admin-patients-page .admin-patient-table > tbody > tr:nth-child(n+4) {
                display: none !important;
            }

            {{-- Mobile-only: the "Directory features" trust row is hidden on phones;
                 desktop and tablet stay outside this query and render it unchanged. --}}
            .admin-patients-page .admin-telemedicine-trust[aria-label="Directory features"] {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        // The roster loop below reuses $patient; keep the requested patient for the View/Edit modals.
        $selectedPatient = $patient ?? null;

        // Mirrors the admin profile name rule (partials/admin-header.blade.php):
        // letters only, with single spaces allowed between them ("dela cruz").
        $namePattern = '[A-Za-z]+( +[A-Za-z]+)*';
    @endphp

    <div class="admin-dashboard-content admin-doctor-content admin-patients-page">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="patientDirectoryTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-people-fill"></i>
                    <span><i class="bi bi-person-heart"></i></span>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="patientDirectoryTitle">Patient Management</h1>
                    <p class="admin-telemedicine-welcome">Registered Patient Directory</p>
                    <p class="admin-telemedicine-description">Search, review, and manage patient accounts, records, and access.</p>
                    <div class="admin-telemedicine-trust" aria-label="Directory features">
                        <span><i class="bi bi-search" aria-hidden="true"></i> Search</span>
                        <b aria-hidden="true">•</b>
                        <span>Records</span>
                        <b aria-hidden="true">•</b>
                        <span>Access Control</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="admin-doctor-stat-grid">
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                <span class="admin-doctor-stat-text"><small>Total patients</small><strong data-patient-stat="total">{{ number_format($patientStats['total']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon green"><i class="bi bi-person-check-fill" aria-hidden="true"></i></span>
                <span class="admin-doctor-stat-text"><small>Active</small><strong data-patient-stat="active">{{ number_format($patientStats['active']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon orange"><i class="bi bi-person-exclamation" aria-hidden="true"></i></span>
                <span class="admin-doctor-stat-text"><small>Deactivated</small><strong data-patient-stat="deactivated">{{ number_format($patientStats['deactivated']) }}</strong></span>
            </article>
            {{-- Moved up from the roster header so it sits on the same row as the
                 statistics, following the .admin-doctor-stat-grid +
                 .admin-doctor-stat-action pattern the doctor listing already uses. --}}
            <button class="admin-doctor-add-button admin-doctor-stat-action" type="button" data-bs-toggle="modal" data-bs-target="#createPatientModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add patient</span>
            </button>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <section class="admin-panel admin-doctor-roster-panel admin-patient-roster" aria-labelledby="patientRosterTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-people-fill" aria-hidden="true"></i>
                    <h2 id="patientRosterTitle">Patient roster</h2>
                </div>
                <div class="admin-patient-roster-actions">
                    <span class="admin-muted-text" data-patient-result-count>{{ $patients->total() }} patient{{ $patients->total() === 1 ? '' : 's' }}</span>
                </div>
            </header>

            <form class="admin-doctor-filters admin-patient-filters" method="GET" action="{{ route('admin.patients') }}" data-patient-filters>
                <div class="admin-patient-filter admin-patient-filter-search">
                    <label for="patientSearch">Search</label>
                    <div class="admin-doctor-search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input id="patientSearch" name="search" value="{{ $filters['search'] }}" placeholder="Name, username, email, hospital no." autocomplete="off">
                    </div>
                </div>
                <div class="admin-patient-filter">
                    <label for="patientStatus">Status</label>
                    <select class="form-select" id="patientStatus" name="status">
                        <option value="">All statuses</option>
                        <option value="Active" @selected($filters['status'] === 'Active')>Active</option>
                        <option value="Pending" @selected($filters['status'] === 'Pending')>Pending</option>
                    </select>
                </div>
                <div class="admin-patient-filter">
                    <label for="patientGender">Gender</label>
                    <select class="form-select" id="patientGender" name="gender">
                        <option value="">All genders</option>
                        <option value="Male" @selected($filters['gender'] === 'Male')>Male</option>
                        <option value="Female" @selected($filters['gender'] === 'Female')>Female</option>
                    </select>
                </div>
                <div class="admin-patient-filter-gender-group">
                    @if ($filters['search'] !== '' || $filters['status'] !== '' || $filters['gender'] !== '')
                        <a class="admin-clear-filter" href="{{ route('admin.patients') }}">Clear</a>
                    @endif
                </div>
            </form>

            {{-- Paginated at 20 rows, so the table is never given its own scrollbar; see pages/patients.css.
                 On phones (<= 767px) each row is rendered as a card using the data-label attributes below. --}}
            <div class="admin-doctor-table-wrap" data-patient-table-wrap>
                <table class="admin-doctor-table admin-patient-table">
                    <caption class="visually-hidden">Registered patients</caption>
                    <thead>
                        <tr>
                            <th scope="col">Patient</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Hospital no.</th>
                            <th scope="col">Registered</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="admin-patient-actions-heading"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patients as $patient)
                            @php
                                $patientName = trim(implode(' ', array_filter([
                                    $patient->first_name,
                                    $patient->middlename,
                                    $patient->last_name,
                                ])));
                                $initials = strtoupper(substr((string) ($patient->first_name ?: 'P'), 0, 1).substr((string) ($patient->last_name ?: 'T'), 0, 1));
                                $isActive = $patient->status === 'Active';
                                $registeredAt = $patient->created_at;
                            @endphp
                            <tr>
                                <td>
                                    <div class="admin-doctor-person">
                                        <span class="admin-avatar">{{ $initials }}</span>
                                        <span>
                                            <strong>{{ $patientName ?: 'Unnamed patient' }}</strong>
                                            <small>{{ $patient->username ? '@'.$patient->username : 'No username' }} · {{ $patient->gender ?: 'Unspecified' }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Contact">
                                    <span class="admin-doctor-primary-text">{{ $patient->contact_number ?: '—' }}</span>
                                    <small class="admin-doctor-secondary-text">{{ $patient->email ?: 'No email' }}</small>
                                </td>
                                <td data-label="Hospital no.">
                                    @if ($patient->hospital_number)
                                        <span class="admin-patient-code">{{ $patient->hospital_number }}</span>
                                    @else
                                        <span class="admin-patient-empty-value">—</span>
                                    @endif
                                </td>
                                <td data-label="Registered">
                                    @if ($registeredAt)
                                        <time datetime="{{ $registeredAt->toDateString() }}">{{ $registeredAt->format('M j, Y') }}</time>
                                    @else
                                        <span class="admin-patient-empty-value">—</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    <span class="admin-status-pill {{ strtolower((string) $patient->status) }}">{{ $patient->status ?: 'Unknown' }}</span>
                                </td>
                                <td class="admin-patient-actions-cell">
                                    <div class="admin-doctor-actions">
                                        <a href="{{ route('admin.patients', ['view' => $patient->id]) }}" data-patient-modal="view" aria-label="View {{ $patientName ?: 'patient' }}">
                                            <i class="bi bi-eye" aria-hidden="true"></i><span>View</span>
                                        </a>
                                        <a href="{{ route('admin.patients', ['edit' => $patient->id]) }}" data-patient-modal="edit" aria-label="Edit {{ $patientName ?: 'patient' }}">
                                            <i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span>
                                        </a>
                                        <button type="button" data-patient-toggle
                                                data-url="{{ route('admin.patients.status', $patient) }}"
                                                data-name="{{ $patientName ?: 'this patient' }}"
                                                data-active="{{ $isActive ? '1' : '0' }}"
                                                aria-label="{{ $isActive ? 'Deactivate' : 'Activate' }} {{ $patientName ?: 'patient' }}">
                                            <i class="bi {{ $isActive ? 'bi-toggle-on' : 'bi-toggle-off' }}" aria-hidden="true"></i><span data-patient-toggle-label>{{ $isActive ? 'Deactivate' : 'Activate' }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-person-x" aria-hidden="true"></i>
                                        <strong>No patients found</strong>
                                        <span>Registered patients will appear here, or adjust the current filters.</span>
                                        @if ($filters['search'] !== '' || $filters['status'] !== '' || $filters['gender'] !== '')
                                            <a href="{{ route('admin.patients') }}">Clear all filters</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-doctor-pagination admin-patient-pagination" data-patient-pagination @if (! $patients->hasPages()) hidden @endif>
                {{ $patients->links('pagination::patient-pagination') }}
            </div>
        </section>
    </div>

    @if ($patientModal === 'view')
        <div class="modal fade admin-doctor-modal" id="viewPatientModal" tabindex="-1" aria-labelledby="viewPatientModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <header class="modal-header admin-doctor-modal-header">
                        <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                        <div class="admin-telemedicine-content">
                            <div class="admin-telemedicine-mark" aria-hidden="true">
                                <i class="bi bi-person-heart"></i>
                            </div>
                            <div class="admin-telemedicine-copy">
                                <h2 class="modal-title" id="viewPatientModalTitle">Patient profile</h2>
                                <p class="admin-telemedicine-description">Review {{ trim(implode(' ', array_filter([$selectedPatient->first_name, $selectedPatient->middlename, $selectedPatient->last_name]))) ?: 'this patient' }}'s information, medical summary, and activity.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </header>
                    <div class="modal-body">
                        @php
                            // The profile body used to come from admin.patients._profile, but every
                            // panel here needed markup changes (full address, Registered under the
                            // hospital number, 5-row pagination, no "View all" / "View records" links)
                            // and that partial is outside this task's file scope, so it is rendered
                            // here instead. The rows are still shared: both tables keep using their
                            // own partials.
                            $profileName = trim(implode(' ', array_filter([
                                $selectedPatient->first_name,
                                $selectedPatient->middlename,
                                $selectedPatient->last_name,
                            ])));
                            $profileInitials = strtoupper(substr((string) ($selectedPatient->first_name ?: 'P'), 0, 1).substr((string) ($selectedPatient->last_name ?: 'T'), 0, 1));
                            $profileLatestAppointment = $selectedPatient->appointments()
                                ->orderByDesc('date')
                                ->orderByDesc('time_slot')
                                ->first();

                            // Both histories are loaded in full and paged in the browser.
                            // openPatientModal() injects this modal for any patient without a
                            // page load, so a table can never fetch its own pages: the record
                            // set travels with the modal as JSON (see the <template> in each
                            // panel below) and the script renders five rows at a time into the
                            // existing tbody. Paging therefore only touches rows - never the
                            // panel, the table or the request - so a table cannot disappear
                            // while paging.
                            $patientHistoryPerPage = 5;

                            $viewAppointments = $selectedPatient->appointments()
                                ->with(['service', 'serviceTele', 'staff'])
                                ->orderByDesc('date')
                                ->orderByDesc('time_slot')
                                ->get();

                            $viewHistory = $selectedPatient->appointments()
                                ->where('status', 'Completed')
                                ->with(['service', 'serviceTele', 'staff'])
                                ->orderByDesc('date')
                                ->orderByDesc('time_slot')
                                ->get();

                            // First paint of the footers. The script recomputes both from the
                            // JSON record set on every page change, so nothing is hard coded.
                            $appointmentTotal = $viewAppointments->count();
                            $appointmentFirstItem = $appointmentTotal === 0 ? 0 : 1;
                            $appointmentLastItem = min($patientHistoryPerPage, $appointmentTotal);
                            $consultationTotal = $viewHistory->count();
                            $consultationFirstItem = $consultationTotal === 0 ? 0 : 1;
                            $consultationLastItem = min($patientHistoryPerPage, $consultationTotal);

                            // The record shape mirrors both row partials. The third cell is
                            // resolved here - the mode for the appointment history, the
                            // provider for the consultation history - so a single row builder
                            // renders either table.
                            $patientHistoryService = static fn ($appointment) => strtoupper((string) $appointment->mode) === 'TELE'
                                ? ($appointment->serviceTele?->service_name ?: 'General consultation')
                                : ($appointment->service?->service_name ?: 'General consultation');

                            $patientHistoryPayloads = [
                                'appointments' => [
                                    'records' => $viewAppointments
                                        ->map(static function ($appointment) use ($patientHistoryService) {
                                            $status = strtolower((string) ($appointment->status ?: 'booked'));

                                            return [
                                                'service' => $patientHistoryService($appointment),
                                                'reason' => $appointment->consultation_reason ?: 'No consultation reason',
                                                'date' => $appointment->date?->format('M j, Y') ?: '—',
                                                'time' => $appointment->time_slot ?: '—',
                                                'detail' => $appointment->mode ?: '—',
                                                'status' => $status,
                                                'statusLabel' => ucfirst($status),
                                            ];
                                        })
                                        ->values()
                                        ->all(),
                                    'emptyTitle' => 'No appointments to show',
                                    'emptyMessage' => 'Appointments booked by this patient will appear here.',
                                ],
                                'consultations' => [
                                    'records' => $viewHistory
                                        ->map(static function ($appointment) use ($patientHistoryService) {
                                            $status = strtolower((string) ($appointment->status ?: 'completed'));

                                            return [
                                                'service' => $patientHistoryService($appointment),
                                                'reason' => $appointment->consultation_reason ?: 'No consultation reason',
                                                'date' => $appointment->date?->format('M j, Y') ?: '—',
                                                'time' => $appointment->time_slot ?: '—',
                                                'detail' => $appointment->staff
                                                    ? trim((string) $appointment->staff->FirstName.' '.(string) $appointment->staff->LastName)
                                                    : 'Unassigned',
                                                'status' => $status,
                                                'statusLabel' => ucfirst($status),
                                            ];
                                        })
                                        ->values()
                                        ->all(),
                                    'emptyTitle' => 'No consultations to show',
                                    'emptyMessage' => 'Completed consultations for this patient will appear here.',
                                ],
                            ];
                        @endphp

                        <section class="admin-panel admin-doctor-profile-panel">
                            <div class="admin-doctor-profile-hero">
                                <span class="admin-doctor-profile-avatar">{{ $profileInitials }}</span>
                                <div>
                                    <h2>{{ $profileName ?: 'Unnamed patient' }}</h2>
                                    <p>{{ $selectedPatient->username ?: 'No username' }} · {{ $selectedPatient->hospital_number ?: 'No hospital number' }}</p>
                                    <span class="admin-status-pill {{ strtolower((string) $selectedPatient->status) }}">{{ $selectedPatient->status ?: '—' }}</span>
                                </div>
                                <div class="admin-doctor-profile-hero-actions">
                                    <form method="POST" action="{{ route('admin.patients.status', $selectedPatient) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="admin-secondary-button" type="submit">
                                            <i class="bi bi-{{ $selectedPatient->status === 'Active' ? 'pause' : 'play' }}-circle" aria-hidden="true"></i>
                                            {{ $selectedPatient->status === 'Active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.patients.reset-password', $selectedPatient) }}">
                                        @csrf
                                        <button class="admin-secondary-button" type="submit">
                                            <i class="bi bi-key" aria-hidden="true"></i>
                                            Reset password
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="admin-doctor-profile-details">
                                <div><small>Username</small><strong>{{ $selectedPatient->username ?: '—' }}</strong></div>
                                <div><small>Email</small><strong>{{ $selectedPatient->email ?: '—' }}</strong></div>
                                <div><small>Contact number</small><strong>{{ $selectedPatient->contact_number ?: '—' }}</strong></div>
                                <div><small>Date of birth</small><strong>{{ $selectedPatient->dob?->format('M j, Y') ?: '—' }}</strong></div>
                                <div><small>Gender</small><strong>{{ $selectedPatient->gender ?: '—' }}</strong></div>
                                <div><small>Hospital number</small><strong>{{ $selectedPatient->hospital_number ?: '—' }}</strong></div>
                                {{-- Address shares the row with Registered; both cells are placed by
                                     the .admin-patient-profile-* rules in the @push('styles') block. --}}
                                <div class="admin-patient-profile-address-cell"><small>Address</small><strong>{{ $selectedPatient->address ?: '—' }}</strong></div>
                                <div class="admin-patient-profile-registered-cell"><small>Registered</small><strong>{{ $selectedPatient->created_at?->format('M j, Y') ?: '—' }}</strong></div>
                            </div>
                        </section>

                        <section class="admin-panel admin-patient-medical-panel" aria-labelledby="patientMedicalInfoTitle">
                            <header class="admin-panel-header">
                                <div class="admin-panel-title">
                                    <i class="bi bi-file-earmark-medical" aria-hidden="true"></i>
                                    <h2 id="patientMedicalInfoTitle">Medical information</h2>
                                </div>
                            </header>
                            <div class="admin-doctor-profile-details">
                                <div>
                                    <small>Latest complaint</small>
                                    <strong>{{ $profileLatestAppointment?->complaint ?: $profileLatestAppointment?->consultation_reason ?: '—' }}</strong>
                                </div>
                                <div>
                                    <small>Latest symptoms</small>
                                    <strong>{{ ! empty($profileLatestAppointment?->symptoms) ? implode(', ', (array) $profileLatestAppointment->symptoms) : '—' }}</strong>
                                </div>
                                <div>
                                    <small>Medical records on file</small>
                                    <strong>{{ $selectedPatient->medicalRecords()->count() }}</strong>
                                </div>
                            </div>
                        </section>

                        <div class="admin-doctor-activity-grid">
                            <section class="admin-panel" id="patientAppointmentsPanel" data-patient-history="appointments" aria-labelledby="patientAppointmentsTitle">
                                <header class="admin-panel-header">
                                    <div class="admin-panel-title">
                                        <i class="bi bi-calendar2-week" aria-hidden="true"></i>
                                        <h2 id="patientAppointmentsTitle">Appointment history</h2>
                                    </div>
                                </header>
                                <div class="admin-doctor-table-wrap admin-patient-view-table-wrap{{ $appointmentTotal > 0 ? '' : ' is-empty' }}">
                                    <table class="admin-doctor-table compact">
                                        <thead><tr><th>Service</th><th>Date</th><th>Mode</th><th>Status</th></tr></thead>
                                        {{-- Page one is rendered here so the table is populated on the
                                             first paint; every later page is drawn into this same
                                             tbody by the script. --}}
                                        <tbody data-patient-history-body>@include('admin.patients._appointment-table', ['appointments' => $viewAppointments->forPage(1, $patientHistoryPerPage)])</tbody>
                                    </table>
                                </div>
                                {{-- The record set rides along inside an inert <template>, so the
                                     script reads it without a request and without any chance of
                                     the layout re-running it as a page script. --}}
                                <template data-patient-history-records>@json($patientHistoryPayloads['appointments'])</template>
                                <div class="admin-doctor-pagination admin-patient-view-pagination" data-patient-history-footer>
                                    <p class="admin-patient-view-pagination-summary" @if ($appointmentFirstItem === 0) hidden @endif>
                                        {{ __('Showing') }}
                                        <span data-patient-history-from>{{ $appointmentFirstItem }}</span>
                                        {{ __('to') }}
                                        <span data-patient-history-to>{{ $appointmentLastItem }}</span>
                                        {{ __('of') }}
                                        <span data-patient-history-total>{{ $appointmentTotal }}</span>
                                        {{ __('results') }}
                                    </p>
                                    {{-- Compact controls: last page, one page back, the current page,
                                         one page forward, first page. They are buttons, not links, so
                                         a click can neither navigate (the layout only intercepts
                                         a[href]) nor submit anything. data-patient-history on the
                                         section tells the script which table to page, so only that
                                         one is touched. --}}
                                    <nav class="admin-patient-view-pagination-controls" aria-label="Appointment history pages">
                                        <button type="button" class="admin-doctor-page-button" data-patient-history-go="first" aria-label="First page" @disabled($appointmentTotal <= $patientHistoryPerPage)>&laquo;</button>
                                        <button type="button" class="admin-doctor-page-button" data-patient-history-go="prev" aria-label="Previous page" @disabled($appointmentFirstItem <= 1)>&lsaquo;</button>
                                        <span class="admin-doctor-page-current" tabindex="-1" aria-current="page" data-patient-history-current>1</span>
                                        <button type="button" class="admin-doctor-page-button" data-patient-history-go="next" aria-label="Next page" @disabled($appointmentLastItem >= $appointmentTotal)>&rsaquo;</button>
                                        <button type="button" class="admin-doctor-page-button" data-patient-history-go="last" aria-label="Last page" @disabled($appointmentTotal <= $patientHistoryPerPage)>&raquo;</button>
                                    </nav>
                                </div>
                            </section>

                            <section class="admin-panel admin-doctor-assignment-history" id="patientHistoryPanel" data-patient-history="consultations" aria-labelledby="patientHistoryTitle">
                                <header class="admin-panel-header">
                                    <div class="admin-panel-title">
                                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                                        <h2 id="patientHistoryTitle">Consultation history</h2>
                                    </div>
                                </header>
                                <div class="admin-doctor-table-wrap admin-patient-view-table-wrap{{ $consultationTotal > 0 ? '' : ' is-empty' }}">
                                    <table class="admin-doctor-table compact">
                                        <thead><tr><th>Service</th><th>Date</th><th>Provider</th><th>Status</th></tr></thead>
                                        <tbody data-patient-history-body>@include('admin.patients._history-table', ['appointments' => $viewHistory->forPage(1, $patientHistoryPerPage)])</tbody>
                                    </table>
                                </div>
                                {{-- Its own record set and its own data-patient-history key, so paging
                                     this table cannot move the appointment history above it. --}}
                                <template data-patient-history-records>@json($patientHistoryPayloads['consultations'])</template>
                                <div class="admin-doctor-pagination admin-patient-view-pagination" data-patient-history-footer>
                                    <p class="admin-patient-view-pagination-summary" @if ($consultationFirstItem === 0) hidden @endif>
                                        {{ __('Showing') }}
                                        <span data-patient-history-from>{{ $consultationFirstItem }}</span>
                                        {{ __('to') }}
                                        <span data-patient-history-to>{{ $consultationLastItem }}</span>
                                        {{ __('of') }}
                                        <span data-patient-history-total>{{ $consultationTotal }}</span>
                                        {{ __('results') }}
                                    </p>
                                    {{-- Same compact controls as the appointment history above. --}}
                                    <nav class="admin-patient-view-pagination-controls" aria-label="Consultation history pages">
                                        <button type="button" class="admin-doctor-page-button" data-patient-history-go="first" aria-label="First page" @disabled($consultationTotal <= $patientHistoryPerPage)>&laquo;</button>
                                        <button type="button" class="admin-doctor-page-button" data-patient-history-go="prev" aria-label="Previous page" @disabled($consultationFirstItem <= 1)>&lsaquo;</button>
                                        <span class="admin-doctor-page-current" tabindex="-1" aria-current="page" data-patient-history-current>1</span>
                                        <button type="button" class="admin-doctor-page-button" data-patient-history-go="next" aria-label="Next page" @disabled($consultationLastItem >= $consultationTotal)>&rsaquo;</button>
                                        <button type="button" class="admin-doctor-page-button" data-patient-history-go="last" aria-label="Last page" @disabled($consultationTotal <= $patientHistoryPerPage)>&raquo;</button>
                                    </nav>
                                </div>
                            </section>
                        </div>

                        @if ($medicalRecords->isNotEmpty())
                            <section class="admin-panel" aria-labelledby="patientRecordsSummaryTitle">
                                <header class="admin-panel-header">
                                    <div class="admin-panel-title">
                                        <i class="bi bi-journal-medical" aria-hidden="true"></i>
                                        <h2 id="patientRecordsSummaryTitle">Recent medical records</h2>
                                    </div>
                                    <a class="admin-panel-link" href="{{ route('admin.patients.records', $selectedPatient) }}">View all</a>
                                </header>
                                <div class="admin-doctor-table-wrap">
                                    <table class="admin-doctor-table compact">
                                        <thead><tr><th>Type</th><th>Description</th><th>Date</th></tr></thead>
                                        <tbody>
                                            @foreach ($medicalRecords as $record)
                                                <tr>
                                                    <td>{{ $record->record_type ?: '—' }}</td>
                                                    <td>{{ $record->description ?: '—' }}</td>
                                                    <td>{{ $record->created_at?->format('M j, Y') ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($patientModal === 'edit')
        <div class="modal fade admin-doctor-modal" id="editPatientModal" tabindex="-1" aria-labelledby="editPatientModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <header class="modal-header admin-doctor-modal-header">
                        <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                        <div class="admin-telemedicine-content">
                            <div class="admin-telemedicine-mark" aria-hidden="true">
                                <i class="bi bi-pencil-fill"></i>
                            </div>
                            <div class="admin-telemedicine-copy">
                                <h2 class="modal-title" id="editPatientModalTitle">Edit patient</h2>
                                <p class="admin-telemedicine-description">Update {{ trim(implode(' ', array_filter([$selectedPatient->first_name, $selectedPatient->middlename, $selectedPatient->last_name]))) ?: 'this patient' }}'s profile and account settings.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </header>
                    <div class="modal-body">
                        @include('admin.patients._form', [
                            'patient' => $selectedPatient,
                            'formAction' => route('admin.patients.update', $selectedPatient),
                            'formMethod' => 'PUT',
                            'submitLabel' => 'Save',
                        ])
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade admin-doctor-modal" id="createPatientModal" tabindex="-1" aria-labelledby="createPatientModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <header class="modal-header admin-doctor-modal-header">
                <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                <div class="admin-telemedicine-content">
                    <div class="admin-telemedicine-mark" aria-hidden="true">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <div class="admin-telemedicine-copy">
                        <h2 class="modal-title" id="createPatientModalTitle">Add patient</h2>
                        <p class="admin-telemedicine-description">Create a new patient account.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </header>
            <form method="POST" action="{{ route('admin.patients.store') }}" novalidate>
                @csrf
                <div class="modal-body">
                    @if ($errors->createPatient->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->createPatient->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="cpFirst">First name</label>
                            <input class="form-control" id="cpFirst" name="firstname" value="{{ old('firstname') }}" placeholder="First name" required maxlength="100" pattern="{{ $namePattern }}" data-letters-only>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="cpMiddle">Middle name</label>
                            <input class="form-control" id="cpMiddle" name="middlename" value="{{ old('middlename') }}" placeholder="Middle name" maxlength="100" pattern="{{ $namePattern }}" data-letters-only>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="cpLast">Last name</label>
                            <input class="form-control" id="cpLast" name="lastname" value="{{ old('lastname') }}" placeholder="Last name" required maxlength="100" pattern="{{ $namePattern }}" data-letters-only>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cpUsername">Username</label>
                            <input class="form-control" id="cpUsername" name="username" value="{{ old('username') }}" placeholder="Username" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cpEmail">Email</label>
                            <input class="form-control" type="email" id="cpEmail" name="email" value="{{ old('email') }}" placeholder="Email">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="cpDob">Date of birth</label>
                            <input class="form-control" type="date" id="cpDob" name="dob" value="{{ old('dob') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="cpGender">Gender</label>
                            <select class="form-select" id="cpGender" name="gender" required>
                                <option value="Male" @selected(old('gender') === 'Male')>Male</option>
                                <option value="Female" @selected(old('gender') === 'Female')>Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="cpContact">Contact number</label>
                            <input class="form-control" id="cpContact" name="contactno" value="{{ old('contactno') }}" placeholder="Contact number" required maxlength="11" pattern="[0-9]*" inputmode="numeric" data-digits-only>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cpAddress">Address</label>
                            <input class="form-control" id="cpAddress" name="address" value="{{ old('address') }}" placeholder="Address" maxlength="500" pattern="[A-Za-z0-9 ]*" data-address-only>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cpHospital">Hospital no.</label>
                            <input class="form-control" id="cpHospital" name="hospital_number" value="{{ old('hospital_number') }}" placeholder="Hospital no." maxlength="10" pattern="[0-9]*" inputmode="numeric" data-digits-only>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cpStatus">Status</label>
                            <select class="form-select" id="cpStatus" name="status" required>
                                <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                                <option value="Pending" @selected(old('status') === 'Pending')>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cpPassword">Password</label>
                            <input class="form-control" type="password" id="cpPassword" name="password" placeholder="Password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cpPasswordConfirm">Confirm password</label>
                            <input class="form-control" type="password" id="cpPasswordConfirm" name="password_confirmation" placeholder="Confirm password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const modalStates = [
                { element: document.getElementById('viewPatientModal'), parameter: 'view' },
                { element: document.getElementById('editPatientModal'), parameter: 'edit' },
                { element: document.getElementById('createPatientModal'), parameter: 'create' },
            ].filter(({ element }) => element);
            const currentSearch = new URLSearchParams(window.location.search);
            const initialModalState = modalStates.find(({ parameter }) => currentSearch.has(parameter));
            const modalParameters = ['view', 'edit', 'create'];

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

            @if ($errors->createPatient->any())
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createPatientModal')).show();
            @endif

            const cleanPatientName = (value) => value
                .replace(/[^A-Za-z ]+/g, '')
                .replace(/ {2,}/g, ' ')
                .replace(/^ +| +$/g, '')
                .toUpperCase();

            const cleanPatientDigits = (maxLength) => (value) => value
                .replace(/[^0-9]/g, '')
                .slice(0, maxLength);

            const cleanPatientAddress = (value) => value
                .replace(/[^A-Za-z0-9 ]+/g, '')
                .replace(/ {2,}/g, ' ')
                .replace(/^ +| +$/g, '');

            const patientFieldRules = [
                {
                    selector: 'input[name="firstname"]',
                    label: 'First name',
                    required: true,
                    maxlength: 100,
                    pattern: @json($namePattern),
                    clean: cleanPatientName,
                    message: 'First name may only contain letters and spaces.',
                },
                {
                    selector: 'input[name="middlename"]',
                    label: 'Middle name',
                    required: false,
                    maxlength: 100,
                    pattern: @json($namePattern),
                    clean: cleanPatientName,
                    message: 'Middle name may only contain letters and spaces.',
                },
                {
                    selector: 'input[name="lastname"]',
                    label: 'Last name',
                    required: true,
                    maxlength: 100,
                    pattern: @json($namePattern),
                    clean: cleanPatientName,
                    message: 'Last name may only contain letters and spaces.',
                },
                {
                    selector: 'input[name="contactno"]',
                    label: 'Contact number',
                    required: true,
                    maxlength: 11,
                    pattern: '[0-9]*',
                    inputmode: 'numeric',
                    clean: cleanPatientDigits(11),
                    exactLength: 11,
                    message: 'Contact number must be 11 digits (numbers only).',
                },
                {
                    selector: 'input[name="hospital_number"]',
                    label: 'Hospital number',
                    required: false,
                    maxlength: 10,
                    pattern: '[0-9]*',
                    inputmode: 'numeric',
                    clean: cleanPatientDigits(10),
                    message: 'Hospital number may only contain up to 10 digits (numbers only).',
                },
                {
                    selector: 'input[name="address"], textarea[name="address"]',
                    label: 'Address',
                    required: false,
                    maxlength: 500,
                    pattern: '[A-Za-z0-9 ]*',
                    clean: cleanPatientAddress,
                    message: 'Address may only contain letters, numbers and spaces.',
                },
            ];

            const patientFieldSelector = patientFieldRules.map((rule) => rule.selector).join(', ');

            const isPatientField = (input) => $(input).closest('#createPatientModal, #editPatientModal').length > 0;

            const applyPatientFieldRules = ($scope) => {
                patientFieldRules.forEach((rule) => {
                    $(rule.selector, $scope).each(function () {
                        this.patientFieldRule = rule;
                        this.setAttribute('maxlength', rule.maxlength);
                        this.setAttribute('pattern', rule.pattern);

                        if (rule.required) {
                            this.setAttribute('required', 'required');
                        }

                        if (rule.inputmode) {
                            this.setAttribute('inputmode', rule.inputmode);
                        }
                    });
                });
            };

            const cleanPatientField = (input) => {
                const cleaned = input.patientFieldRule.clean(input.value);

                if (cleaned === input.value) {
                    return;
                }

                const caretStart = input.selectionStart;
                const caretEnd = input.selectionEnd;

                input.value = cleaned;

                if (caretStart !== null && typeof input.setSelectionRange === 'function') {
                    input.setSelectionRange(caretStart, caretEnd);
                }
            };

            const patientFieldMessage = (input) => {
                const rule = input.patientFieldRule;
                const value = input.value;

                if (rule.required && value.trim() === '') {
                    return rule.label + ' is required.';
                }

                if (value !== '' && rule.exactLength && value.length !== rule.exactLength) {
                    return rule.message;
                }

                return rule.clean(value) === value ? '' : rule.message;
            };

            const showPatientFieldError = (input, message) => {
                const $input = $(input);
                const $feedback = $input.siblings('.patient-field-error');

                input.patientInvalid = true;
                $input.addClass('is-invalid');

                if ($feedback.length) {
                    $feedback.text(message);
                } else {
                    $('<div class="patient-field-error invalid-feedback d-block"></div>')
                        .text(message)
                        .insertAfter($input);
                }
            };

            const clearPatientFieldError = (input) => {
                if (!input.patientInvalid) {
                    return;
                }

                delete input.patientInvalid;
                $(input).removeClass('is-invalid').siblings('.patient-field-error').remove();
            };

            $(document).off('.patientFields');
            applyPatientFieldRules($('#createPatientModal, #editPatientModal'));

            $(document).on('input.patientFields', patientFieldSelector, function () {
                if (!isPatientField(this)) {
                    return;
                }

                cleanPatientField(this);

                if (this.patientInvalid && patientFieldMessage(this) === '') {
                    clearPatientFieldError(this);
                }
            });

            $(document).on('submit.patientFields', '#createPatientModal form, #editPatientModal form', function (event) {
                let firstInvalid = null;

                applyPatientFieldRules($(this));

                $(patientFieldSelector, this).each(function () {
                    cleanPatientField(this);

                    const message = patientFieldMessage(this);

                    if (message === '') {
                        clearPatientFieldError(this);

                        return;
                    }

                    showPatientFieldError(this, message);
                    firstInvalid = firstInvalid || this;
                });

                if (!firstInvalid) {
                    return;
                }

                event.preventDefault();
                firstInvalid.focus();
            });

            const PATIENT_HISTORY_PER_PAGE = 5;

            const readPatientHistoryState = ($panel) => {
                const element = $panel[0];

                if (!element) {
                    return null;
                }

                if (element.patientHistory) {
                    return element.patientHistory;
                }

                const template = $panel.find('template[data-patient-history-records]')[0];
                let payload = null;

                if (template) {
                    try {
                        payload = JSON.parse(template.content.textContent);
                    } catch (error) {
                        payload = null;
                    }
                }

                element.patientHistory = {
                    records: Array.isArray(payload?.records) ? payload.records : [],
                    emptyTitle: payload?.emptyTitle || 'Nothing to show',
                    emptyMessage: payload?.emptyMessage || '',
                    currentPage: 1,
                };

                return element.patientHistory;
            };

            const patientHistoryPageCount = (total) => Math.max(1, Math.ceil(total / PATIENT_HISTORY_PER_PAGE));

            const patientHistoryClampPage = (page, pageCount) => {
                const wanted = parseInt(page, 10);
                const safePage = Number.isFinite(wanted) ? wanted : 1;

                return Math.min(Math.max(safePage, 1), pageCount);
            };

            const buildPatientHistoryRow = (record) => $('<tr>')
                .append($('<td>').append(
                    $('<div class="admin-doctor-appointment-patient">').append(
                        $('<strong>').text(record.service ?? '—'),
                        $('<small>').text(record.reason ?? '—'),
                    ),
                ))
                .append($('<td>').append(
                    $('<strong>').text(record.date ?? '—'),
                    $('<small>').text(record.time ?? '—'),
                ))
                .append($('<td>').text(record.detail ?? '—'))
                .append($('<td>').append(
                    $('<span>')
                        .addClass('admin-status-pill ' + (record.status || ''))
                        .text(record.statusLabel ?? record.status ?? '—'),
                ));

            const buildPatientHistoryEmptyRow = (state, columnCount) => $('<tr>')
                .append($('<td>').attr('colspan', columnCount).append(
                    $('<div class="admin-doctor-empty compact">').append(
                        $('<i>').addClass('bi bi-calendar2-x').attr('aria-hidden', 'true'),
                        $('<strong>').text(state.emptyTitle),
                        $('<span>').text(state.emptyMessage),
                    ),
                ));

            const renderPatientHistoryPage = ($panel, state, requestedPage) => {
                const $tbody = $panel.find('[data-patient-history-body]');

                if (!$tbody.length) {
                    return state.currentPage;
                }

                const pageCount = patientHistoryPageCount(state.records.length);
                const currentPage = patientHistoryClampPage(requestedPage, pageCount);

                const startIndex = (currentPage - 1) * PATIENT_HISTORY_PER_PAGE;
                const pageRecords = state.records.slice(startIndex, startIndex + PATIENT_HISTORY_PER_PAGE);
                const columnCount = Math.max(1, $panel.find('table thead th').length);
                const $rows = $(document.createDocumentFragment());

                pageRecords.forEach((record) => $rows.append(buildPatientHistoryRow(record)));

                if (!pageRecords.length) {
                    $rows.append(buildPatientHistoryEmptyRow(state, columnCount));
                }

                $tbody.empty().append($rows);

                state.currentPage = currentPage;

                return currentPage;
            };

            const updatePatientHistoryPagination = ($panel, state) => {
                const $footer = $panel.find('[data-patient-history-footer]');

                if (!$footer.length) {
                    return;
                }

                const total = state.records.length;
                const pageCount = patientHistoryPageCount(total);
                const currentPage = patientHistoryClampPage(state.currentPage, pageCount);
                const firstItem = total === 0 ? 0 : (currentPage - 1) * PATIENT_HISTORY_PER_PAGE + 1;
                const lastItem = total === 0 ? 0 : Math.min(currentPage * PATIENT_HISTORY_PER_PAGE, total);
                const $summary = $footer.find('.admin-patient-view-pagination-summary');

                $summary.prop('hidden', total === 0);
                $summary.find('[data-patient-history-from]').text(firstItem);
                $summary.find('[data-patient-history-to]').text(lastItem);
                $summary.find('[data-patient-history-total]').text(total);
                $footer.find('[data-patient-history-current]').text(currentPage);

                $footer.find('[data-patient-history-go]').each(function () {
                    const direction = $(this).attr('data-patient-history-go');
                    const targetPage = {
                        first: 1,
                        last: pageCount,
                        prev: currentPage - 1,
                        next: currentPage + 1,
                    }[direction];
                    const staysInRange = direction === 'first' || direction === 'last'
                        ? pageCount > 1
                        : targetPage >= 1 && targetPage <= pageCount;

                    $(this).prop('disabled', !staysInRange);
                });
            };

            const goToPatientHistoryPage = ($panel, requestedPage) => {
                const state = readPatientHistoryState($panel);

                if (!state) {
                    return;
                }

                renderPatientHistoryPage($panel, state, requestedPage);
                updatePatientHistoryPagination($panel, state);

                const $current = $panel.find('[data-patient-history-current]')[0];

                if ($current) {
                    $current.focus({ preventScroll: true });
                }
            };

            $(document).off('click.patientHistory')
                .on('click.patientHistory', '[data-patient-history-go]', function (event) {
                    event.preventDefault();

                    const $button = $(this);

                    if ($button.prop('disabled')) {
                        return;
                    }

                    const $panel = $button.closest('[data-patient-history]');
                    const state = readPatientHistoryState($panel);

                    if (!state) {
                        return;
                    }

                    const pageCount = patientHistoryPageCount(state.records.length);
                    const currentPage = patientHistoryClampPage(state.currentPage, pageCount);
                    const targetPage = {
                        first: 1,
                        last: pageCount,
                        prev: currentPage - 1,
                        next: currentPage + 1,
                    }[$button.attr('data-patient-history-go')];

                    if (targetPage < 1 || targetPage > pageCount) {
                        return;
                    }

                    goToPatientHistoryPage($panel, targetPage);
                });

            $('.admin-doctor-activity-grid [data-patient-history]').each(function () {
                const state = readPatientHistoryState($(this));

                if (state) {
                    updatePatientHistoryPagination($(this), state);
                }
            });

            const $form = $('[data-patient-filters]');

            if (!$form.length) {
                return;
            }

            const $tableWrap = $('[data-patient-table-wrap]');
            const $resultCount = $('[data-patient-result-count]');
            const $pagination = $('[data-patient-pagination]');
            let debounceTimer;
            let activeFilterRequest;

            const stripModalParams = (url) => {
                modalParameters.forEach((parameter) => {
                    url.searchParams.delete(parameter);
                });

                return url;
            };

            const buildFilterUrl = (overrideUrl = null) => {
                if (overrideUrl) {
                    return stripModalParams(new URL(overrideUrl, window.location.origin)).toString();
                }

                const url = new URL($form.attr('action'), window.location.origin);
                const params = new URLSearchParams($form.serialize());

                params.delete('page');

                params.forEach((value, key) => {
                    if (value === '') {
                        url.searchParams.delete(key);
                    } else {
                        url.searchParams.set(key, value);
                    }
                });

                return stripModalParams(url).toString();
            };

            const applyFilterResponse = (html, targetUrl) => {
                const $page = $('<div>').html(html);
                const $nextTable = $page.find('[data-patient-table-wrap]');
                const $nextCount = $page.find('[data-patient-result-count]');
                const $nextPagination = $page.find('[data-patient-pagination]');
                const $nextForm = $page.find('[data-patient-filters]');

                if ($nextTable.length) {
                    $tableWrap.html($nextTable.html());
                }

                if ($nextCount.length) {
                    $resultCount.text($nextCount.text());
                }

                if ($nextPagination.length) {
                    $pagination.html($nextPagination.html());
                    $pagination.prop('hidden', $nextPagination.prop('hidden') ?? true);
                }

                if ($nextForm.length) {
                    const $nextClear = $nextForm.find('a.admin-clear-filter');
                    const $currentClear = $form.find('a.admin-clear-filter');

                    if ($nextClear.length && !$currentClear.length) {
                        $form.append($nextClear);
                    } else if (!$nextClear.length && $currentClear.length) {
                        $currentClear.remove();
                    }
                }

                window.history.replaceState({}, '', targetUrl);
            };

            const updateFilters = (overrideUrl = null) => {
                const targetUrl = buildFilterUrl(overrideUrl);

                if (activeFilterRequest) {
                    activeFilterRequest.abort();
                }

                activeFilterRequest = $.ajax({
                    url: targetUrl,
                    method: 'GET',
                    headers: { Accept: 'text/html' },
                })
                    .done((html) => {
                        applyFilterResponse(html, targetUrl);
                    })
                    .fail((_jqXHR, textStatus) => {
                        if (textStatus !== 'abort') {
                            $form[0].submit();
                        }
                    })
                    .always(() => {
                        activeFilterRequest = null;
                    });
            };

            $form.on('submit', (event) => {
                event.preventDefault();
                updateFilters();
            });

            $form.on('input', 'input[name="search"]', () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(() => updateFilters(), 300);
            });

            $form.on('change', 'select[name="status"], select[name="gender"]', () => {
                updateFilters();
            });

            $form.on('click', 'a.admin-clear-filter', (event) => {
                event.preventDefault();
                $form.find('input[name="search"]').val('');
                $form.find('select[name="status"]').val('');
                $form.find('select[name="gender"]').val('');
                updateFilters();
            });

            $pagination.on('click', 'a', (event) => {
                event.preventDefault();
                updateFilters($(event.currentTarget).attr('href'));
            });

            const showAlert = (type, message) => {
                $('[data-patient-alert]').remove();
                const $alert = $('<div class="alert alert-dismissible fade show" role="alert" data-patient-alert></div>')
                    .addClass('alert-' + type)
                    .text(message)
                    .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                $('.admin-doctor-stat-grid').after($alert);
            };

            $tableWrap.on('click', '[data-patient-toggle]', function () {
                const $button = $(this);

                if ($button.prop('disabled')) {
                    return;
                }

                const isActive = $button.attr('data-active') === '1';
                const verb = isActive ? 'Deactivate' : 'Activate';
                const $label = $button.find('[data-patient-toggle-label]');

                if (!window.confirm(verb + ' ' + $button.attr('data-name') + '?')) {
                    return;
                }

                $button.prop('disabled', true);
                $label.text(isActive ? 'Deactivating…' : 'Activating…');

                $.ajax({
                    url: $button.attr('data-url'),
                    type: 'POST',
                    data: { _method: 'PATCH' },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        Accept: 'application/json',
                    },
                })
                    .done((response) => {
                        Object.entries(response.stats || {}).forEach(([key, value]) => {
                            $('[data-patient-stat="' + key + '"]').text(value);
                        });

                        const rowsLeft = $tableWrap.find('tbody tr').not(':has(.admin-doctor-empty)').length;
                        const url = new URL(window.location.href);
                        const page = parseInt(url.searchParams.get('page') || '1', 10);

                        if (rowsLeft <= 1 && page > 1) {
                            url.searchParams.set('page', page - 1);
                        }

                        updateFilters(url.toString());
                        showAlert('success', response.message);
                    })
                    .fail((xhr) => {
                        $button.prop('disabled', false);
                        $label.text(verb);
                        showAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to update the patient status. Please try again.');
                    });
            });

            const $modalHost = $tableWrap.closest('.admin-main').length ? $tableWrap.closest('.admin-main') : $(document.body);
            let modalRequest;

            const openPatientModal = (href, type) => {
                if (modalRequest) {
                    modalRequest.abort();
                }

                modalRequest = $.ajax({ url: href, method: 'GET', headers: { Accept: 'text/html' } })
                    .done((html) => {
                        const $incoming = $('<div>').html(html).find(type === 'view' ? '#viewPatientModal' : '#editPatientModal').first();

                        if (!$incoming.length) {
                            window.location.href = href;
                            return;
                        }

                        $('#viewPatientModal, #editPatientModal').each(function () {
                            bootstrap.Modal.getInstance(this)?.dispose();
                            $(this).remove();
                        });

                        const patientId = new URL(href, window.location.origin).searchParams.get(type);
                        $incoming.appendTo($modalHost);
                        const element = $incoming[0];

                        applyPatientFieldRules($incoming);

                        element.addEventListener('show.bs.modal', () => {
                            const url = new URL(window.location.href);
                            modalParameters.forEach((parameter) => url.searchParams.delete(parameter));
                            url.searchParams.set(type, patientId);
                            window.history.replaceState({}, '', url);
                        });

                        element.addEventListener('hidden.bs.modal', () => {
                            const url = new URL(window.location.href);
                            url.searchParams.delete(type);
                            window.history.replaceState({}, '', url);
                            bootstrap.Modal.getInstance(element)?.dispose();
                            $(element).remove();
                        });

                        bootstrap.Modal.getOrCreateInstance(element, { backdrop: 'static', keyboard: false }).show();
                    })
                    .fail((_jqXHR, textStatus) => {
                        if (textStatus !== 'abort') {
                            window.location.href = href;
                        }
                    })
                    .always(() => {
                        modalRequest = null;
                    });
            };

            $tableWrap.on('click', 'a[data-patient-modal]', function (event) {
                if (event.ctrlKey || event.metaKey || event.shiftKey || event.which === 2) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation(); // keeps the layout's page navigation out of this click
                openPatientModal(this.href, $(this).attr('data-patient-modal'));
            });
        });
    </script>
@endpush