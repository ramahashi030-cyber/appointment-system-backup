@extends('layouts.admin')

@section('title', 'Doctors')

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

        {{-- Mobile display cap, matching the Patients and Reports pages: below 768px
             this roster becomes a stack of cards and a full page of providers can
             take over the screen, so only the first 3 data rows are made visible
             there. Rows 4+ stay in the DOM (still reachable through the existing
             pagination and its filters), so no doctor or staff record is deleted.
             Desktop and tablet sit outside this query and keep rendering every
             record unchanged. Scoped to this page's own data-doctor-table-wrap so
             no other admin table is affected, and prefixed with .admin-main so it
             outranks mobile.css's `.admin-table-stack tbody tr { display: flex }`
             row rule. --}}
        @media (max-width: 767.98px) {
            .admin-main [data-doctor-table-wrap] .admin-doctor-table > tbody > tr:nth-child(n+4) {
                display: none !important;
            }

            {{-- Mobile-only: the "Directory features" trust row is hidden on phones;
                 desktop and tablet stay outside this query and render it unchanged. --}}
            .admin-doctor-content .admin-telemedicine-trust[aria-label="Directory features"] {
                display: none !important;
            }
        }
    </style>
    <div class="admin-dashboard-content admin-doctor-content">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="doctorDirectoryTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-people-fill"></i>
                    <span><i class="bi bi-person-plus-fill"></i></span>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="doctorDirectoryTitle">Doctors</h1>
                    <p class="admin-telemedicine-welcome">Healthcare Provider Directory</p>
                    <p class="admin-telemedicine-description">Manage provider profiles, schedules, and account access.</p>
                    <div class="admin-telemedicine-trust" aria-label="Directory features">
                        <span><i class="bi bi-person-badge" aria-hidden="true"></i> Providers</span>
                        <b aria-hidden="true">•</b>
                        <span>Scheduling</span>
                        <b aria-hidden="true">•</b>
                        <span>Access Control</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="admin-doctor-stat-grid">
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                <span><small>Total providers</small><strong>{{ number_format($doctorStats['total']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon green"><i class="bi bi-person-check-fill" aria-hidden="true"></i></span>
                <span><small>Active</small><strong>{{ number_format($doctorStats['active']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon orange"><i class="bi bi-calendar2-week-fill" aria-hidden="true"></i></span>
                <span><small>With availability</small><strong>{{ number_format($doctorStats['scheduled']) }}</strong></span>
            </article>
            <button class="admin-doctor-add-button admin-doctor-stat-action" type="button" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add doctor</span>
            </button>
        </div>

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="doctorRosterTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                    <h2 id="doctorRosterTitle">Healthcare provider roster</h2>
                </div>
                <span class="admin-muted-text" data-doctor-result-count>{{ $doctors->total() }} provider{{ $doctors->total() === 1 ? '' : 's' }}</span>
            </header>

            <form class="admin-doctor-filters" method="GET" action="{{ route('admin.doctors') }}" data-doctor-filters>
                <div class="admin-doctor-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="doctorSearch">Search providers</label>
                    <input id="doctorSearch" name="search" value="{{ $filters['search'] }}" placeholder="Search names, username, or email...">
                </div>
                <select class="form-select" name="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <option value="active" @selected($filters['status'] === 'active')>Active</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                </select>
                @if ($filters['search'] !== '' || $filters['status'] !== '')
                    <a class="admin-clear-filter" href="{{ route('admin.doctors') }}">Clear</a>
                @endif
            </form>

            <div class="admin-doctor-table-wrap" data-doctor-table-wrap>
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">Doctors and healthcare providers</caption>
                    <thead>
                        <tr>
                            <th scope="col">Provider</th>
                            <th scope="col">Schedule</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($doctors as $doctor)
                            @php
                                $scheduleDays = $doctor->availability['days'] ?? [];
                                $scheduleLabel = count($scheduleDays) > 0
                                    ? ucfirst(implode(', ', array_map('ucfirst', $scheduleDays)))
                                    : 'Not set';
                            @endphp
                            <tr>
                                <td>
                                    <div class="admin-doctor-person">
                                        <span class="admin-avatar">{{ strtoupper(substr((string) ($doctor->FirstName ?: 'D'), 0, 1).substr((string) ($doctor->LastName ?: 'P'), 0, 1)) }}</span>
                                        <span>
                                            <strong>{{ $doctor->full_name ?: 'Unnamed provider' }}</strong>
                                            <small>{{ $doctor->username ?: 'No username' }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $scheduleLabel }}</span>
                                    @if (! empty($doctor->availability['start']))
                                        <small class="admin-doctor-secondary-text">{{ $doctor->availability['start'] }}–{{ $doctor->availability['end'] }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="admin-status-pill {{ $doctor->is_active ? 'active' : 'inactive' }}">
                                        {{ $doctor->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-doctor-actions">
                                        <a href="{{ route('admin.doctors', ['view' => $doctor->id]) }}" aria-label="View {{ $doctor->full_name }}">View</a>
                                        <a href="{{ route('admin.doctors', ['edit' => $doctor->id]) }}" aria-label="Edit {{ $doctor->full_name }}">Edit</a>
                                        <form method="POST" action="{{ route('admin.doctors.status', $doctor) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button"
                                                    data-doctor-toggle
                                                    data-active="{{ $doctor->is_active ? '1' : '0' }}"
                                                    data-name="{{ $doctor->full_name ?: 'this provider' }}"
                                                    aria-label="{{ $doctor->is_active ? 'Deactivate' : 'Activate' }} {{ $doctor->full_name ?: 'provider' }}">
                                                <i class="bi {{ $doctor->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}" aria-hidden="true"></i>
                                                <span data-doctor-toggle-label>{{ $doctor->is_active ? 'Deactivate' : 'Activate' }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-person-x" aria-hidden="true"></i>
                                        <strong>No providers found</strong>
                                        <span>Add a doctor or adjust the current filters.</span>
                                        <a href="{{ route('admin.doctors', ['create' => 1]) }}" data-bs-toggle="modal" data-bs-target="#addDoctorModal">Add doctor</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-doctor-pagination" data-doctor-pagination @if (! $doctors->hasPages()) hidden @endif>
                @if ($doctors->hasPages())
                    @if ($doctors->onFirstPage())
                        <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&laquo;</span>
                    @else
                        <a class="admin-doctor-page-button" href="{{ $doctors->previousPageUrl() }}" aria-label="Previous page">&laquo;</a>
                    @endif
                    <span class="admin-doctor-page-current" aria-current="page">{{ $doctors->currentPage() }}</span>
                    @if ($doctors->hasMorePages())
                        <a class="admin-doctor-page-button" href="{{ $doctors->nextPageUrl() }}" aria-label="Next page">&raquo;</a>
                    @else
                        <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&raquo;</span>
                    @endif
                @endif
            </div>
        </section>
    </div>

    <div class="modal fade admin-doctor-modal" id="addDoctorModal" tabindex="-1" aria-labelledby="addDoctorModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="addDoctorModalTitle">Add doctor</h2>
                            <p class="admin-telemedicine-description">Create a healthcare provider account and profile.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    @include('admin.doctors._form', [
                        'doctor' => null,
                        'formAction' => route('admin.doctors.store'),
                        'formMethod' => 'POST',
                        'submitLabel' => 'Save',
                    ])
                </div>
            </div>
        </div>
    </div>

    @if ($providerModal === 'view')
        <div class="modal fade admin-doctor-modal" id="viewProviderModal" tabindex="-1" aria-labelledby="viewProviderModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <header class="modal-header admin-doctor-modal-header">
                        <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                        <div class="admin-telemedicine-content">
                            <div class="admin-telemedicine-mark" aria-hidden="true">
                                <i class="bi bi-person-badge-fill"></i>
                            </div>
                            <div class="admin-telemedicine-copy">
                                <h2 class="modal-title" id="viewProviderModalTitle">Provider profile</h2>
                                <p class="admin-telemedicine-description">Review {{ $provider->full_name ?: 'this provider' }}'s account, schedule, and activity.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </header>
                    <div class="modal-body">
                        @include('admin.doctors._profile', ['doctor' => $provider])
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($providerModal === 'edit')
        <div class="modal fade admin-doctor-modal" id="editProviderModal" tabindex="-1" aria-labelledby="editProviderModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <header class="modal-header admin-doctor-modal-header">
                        <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                        <div class="admin-telemedicine-content">
                            <div class="admin-telemedicine-mark" aria-hidden="true">
                                <i class="bi bi-pencil-fill"></i>
                            </div>
                            <div class="admin-telemedicine-copy">
                                <h2 class="modal-title" id="editProviderModalTitle">Edit provider</h2>
                                <p class="admin-telemedicine-description">Update {{ $provider->full_name ?: 'this provider' }}'s profile and account settings.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </header>
                    <div class="modal-body">
                        @include('admin.doctors._form', [
                            'doctor' => $provider,
                            'formAction' => route('admin.doctors.update', $provider),
                            'formMethod' => 'PUT',
                            'submitLabel' => 'Save',
                        ])
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Doctor Deactivation confirmation flashcard.
         Mirrors the Patient Deactivation flashcard on the Patients page
         (#confirmDeactivatePatient): the question (doctor's name) is filled
         from the toggle button's existing data-name, the X and Cancel are the
         only ways it closes, and this flashcard's Deactivate button submits
         the same PATCH status form the old submit button sent. --}}
    {{-- data-bs-backdrop="static" + data-bs-keyboard="false": outside clicks and
         Escape report a "hidePrevented" event (answered below with a brief red
         danger outline) instead of closing. Only X and Cancel dismiss this
         flashcard; Deactivate submits. --}}
    <div class="modal fade" id="confirmDeactivateDoctor" tabindex="-1"
         data-bs-backdrop="static" data-bs-keyboard="false"
         aria-labelledby="confirmDeactivateDoctorTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="admin-doctor-confirm-body">
                    <span class="admin-doctor-confirm-icon" aria-hidden="true">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </span>
                    <div class="admin-doctor-confirm-copy">
                        <h2 class="modal-title" id="confirmDeactivateDoctorTitle">Deactivate Provider?</h2>
                        <p class="admin-doctor-confirm-text">Are you sure you want to deactivate <strong data-confirm-doctor-name></strong>?</p>
                        <p class="admin-doctor-confirm-note">Their access to the system will be disabled; they can be reactivated at any time.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="admin-doctor-confirm-actions">
                    <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="admin-danger-button" data-confirm-doctor-deactivate>
                        <i class="bi bi-person-x" aria-hidden="true"></i>
                        <span>Deactivate</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Doctor Activation confirmation flashcard.
         Mirrors the Doctor Deactivation flashcard: the question (doctor's name) is filled
         from the toggle button's existing data-name, the X and Cancel are the only
         ways it closes, and this flashcard's Activate button submits the same PATCH
         status form the old submit button sent. --}}
    <div class="modal fade" id="confirmActivateDoctor" tabindex="-1"
         data-bs-backdrop="static" data-bs-keyboard="false"
         aria-labelledby="confirmActivateDoctorTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="admin-doctor-confirm-body">
                    <span class="admin-doctor-confirm-icon" aria-hidden="true">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </span>
                    <div class="admin-doctor-confirm-copy">
                        <h2 class="modal-title" id="confirmActivateDoctorTitle">Activate Provider?</h2>
                        <p class="admin-doctor-confirm-text">Are you sure you want to activate <strong data-confirm-doctor-name-activate></strong>?</p>
                        <p class="admin-doctor-confirm-note">Their access to the system will be enabled; they can be deactivated at any time.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="admin-doctor-confirm-actions">
                    <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="admin-primary-button" data-confirm-doctor-activate>
                        <i class="bi bi-person-check" aria-hidden="true"></i>
                        <span>Activate</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
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

        /* =============================================================
           Add Doctor modal — spacing fix + design alignment with the standard.

           Cause of the uneven spacing (not random margins): every section
           body restated its own measurements in admin-css/doctor.css —
           17px grid gap, 18px schedule gap, 14px time-grid gap, 20px
           padding — and wrapped the fields in a nested blue box, so the
           modal never used the reference's 12px row rhythm or its flat
           content area. The rules below restate the standard measurements
           for this one modal; markup, fields, validation and behaviour
           are untouched. Scoped with #addDoctorModal so the edit/view
           modals and the shared doctors._form partial stay as they are.
           ============================================================= */

        /* Sections get the standard light treatment: the reference body is a
           flat surface (no cards or strips), so the panels drop their dark
           surface/border and each section heading becomes a quiet label row
           finished with a hairline divider. */
        #addDoctorModal .admin-panel {
            border: none;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        #addDoctorModal .admin-panel-header {
            min-height: 0;
            padding: 0 16px 8px;
            border-bottom: 1px solid #eaf1f9;
            background: transparent;
            color: #0a326c;
        }

        /* Section bodies: 12px rows/columns and 16px inner padding, with the
           nested blue box dropped so fields sit on the section surface the
           way the reference fields sit on the modal body. The day grid keeps
           its 8px chip gap — chip pickers stay dense (the service day picker
           uses 6px) and seven 70px chips need the room inside 640px. */
        #addDoctorModal .admin-doctor-form-grid,
        #addDoctorModal .admin-doctor-schedule-fields {
            gap: 12px;
            padding: 16px;
            border: none;
            background: transparent;
        }

        #addDoctorModal .admin-doctor-time-grid {
            gap: 12px;
        }

        /* Action button: the Add form's submit also carries the shared
           .admin-doctor-add-button look (44px tall, 11px radius, 20px padding,
           1px border). Inside this modal it is restored to the reference
           footer's primary button (40px tall, 9px radius, 18px padding, no
           border) so the footer reads exactly like the Add Patient modal.
           Behaviour is untouched. */
        #addDoctorModal .admin-doctor-form-actions .admin-primary-button {
            height: 40px;
            min-height: 40px;
            gap: 7px;
            padding: 0 18px;
            border: 0;
            border-radius: 9px;
        }

        /* Password guidance reads as helper text, and the switch label
           follows control sizing — both on the standard type scale. */
        #addDoctorModal .form-text {
            color: #8ca2bd;
            font-size: 12px;
        }

        #addDoctorModal .form-check-label {
            font-size: 13px;
        }

        /* Phones: matches the reference modal's mobile behaviour — section and
           heading padding line up with the shared 14px body padding, the shift
           fields stack full width, and the body/dialog get the same
           viewport-height scroll caps as the Add Patient modal (patients.css)
           so nothing is clipped or cut off. */
        @media (max-width: 767.98px) {
            #addDoctorModal .admin-panel-header {
                padding: 0 14px 8px;
            }

            #addDoctorModal .admin-doctor-form-grid,
            #addDoctorModal .admin-doctor-schedule-fields {
                padding: 14px;
            }

            #addDoctorModal .admin-doctor-time-grid {
                grid-template-columns: 1fr;
            }

            #addDoctorModal .modal-dialog {
                max-height: calc(100vh - 2rem);
            }

            #addDoctorModal .modal-body {
                max-height: calc(100vh - 14rem);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Required-field markers for the Add Doctor modal. The form markup lives in
           the read-only doctors._form partial, so asterisks are appended with CSS
           pseudo-elements, scoped to this page's add modal (edit modal untouched). */
        #addDoctorModal .form-field:has(> input[required]) > label::after,
        #addDoctorModal .form-field:has(> select[required]) > label::after {
            content: ' *';
            color: #dc3545;
        }

        #addDoctorModal .admin-doctor-schedule-fields > .form-field > label::after {
            content: ' *';
            color: #dc3545;
        }

        /* =============================================================
           Doctor Deactivation — row button + confirmation flashcard.

           Mirrors the Patient Deactivation UI (Patients page toggle button
           and #confirmDeactivatePatient flashcard). Scoped to this page's
           own classes / modal id, so no other Doctor/Staff control moves.
           ============================================================= */

        /* Row toggle: the patient's bare Deactivate/Activate link — no box,
           body font, toggle icon at 16px, danger red while active and the
           activation green while inactive. */
        .admin-doctor-content .admin-doctor-actions button[data-doctor-toggle] {
            background: none;
            border: 0;
            padding: 0;
            font: inherit;
            color: #b42318;
            cursor: pointer;
        }

        .admin-doctor-content .admin-doctor-actions button[data-doctor-toggle] i {
            font-size: 16px;
        }

        .admin-doctor-content .admin-doctor-actions button[data-doctor-toggle][data-active="0"] {
            color: #067647;
        }

        .admin-doctor-content .admin-doctor-actions button[data-doctor-toggle]:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        /* Flashcard: compact 420px card in this page's modal language (12px
           radius, admin elevation), with a danger treatment for the
           destructive action — identical to the Patient flashcard. */
        #confirmDeactivateDoctor .modal-dialog,
        #confirmActivateDoctor .modal-dialog {
            max-width: 420px;
        }

        #confirmDeactivateDoctor .modal-content,
        #confirmActivateDoctor .modal-content {
            overflow: hidden;
            border: 1px solid #f2c9c6;
            border-top: 3px solid #c93f36;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 24px 60px rgba(7, 30, 61, .3);
            color: #0a326c;
        }

        #confirmDeactivateDoctor .admin-doctor-confirm-body,
        #confirmActivateDoctor .admin-doctor-confirm-body {
            position: relative;
            display: flex;
            gap: 14px;
            padding: 22px 22px 14px;
        }

        #confirmDeactivateDoctor .admin-doctor-confirm-icon,
        #confirmActivateDoctor .admin-doctor-confirm-icon {
            display: inline-flex;
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(135deg, #e05a52, #c93f36);
            box-shadow: 0 8px 16px rgba(201, 63, 54, .22);
            color: #fff;
            font-size: 20px;
        }

        #confirmDeactivateDoctor .admin-doctor-confirm-copy,
        #confirmActivateDoctor .admin-doctor-confirm-copy {
            min-width: 0;
            padding-right: 20px; /* keeps text clear of the close button */
        }

        #confirmDeactivateDoctor .admin-doctor-confirm-body .modal-title,
        #confirmActivateDoctor .admin-doctor-confirm-body .modal-title {
            margin: 0;
            color: #0a326c;
            font-size: 17px;
            font-weight: 700;
        }

        #confirmDeactivateDoctor .admin-doctor-confirm-text,
        #confirmActivateDoctor .admin-doctor-confirm-text {
            margin: 6px 0 0;
            color: #315786;
            font-size: 13px;
            line-height: 1.5;
        }

        #confirmDeactivateDoctor .admin-doctor-confirm-text strong,
        #confirmActivateDoctor .admin-doctor-confirm-text strong {
            color: #0a326c;
            font-weight: 700;
            overflow-wrap: anywhere; /* long doctor names wrap cleanly */
        }

        #confirmDeactivateDoctor .admin-doctor-confirm-note,
        #confirmActivateDoctor .admin-doctor-confirm-note {
            margin: 6px 0 0;
            color: #8ca2bd;
            font-size: 12px;
            line-height: 1.45;
        }

        #confirmDeactivateDoctor .btn-close,
        #confirmActivateDoctor .btn-close {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: .4rem;
        }

        #confirmDeactivateDoctor .admin-doctor-confirm-actions,
        #confirmActivateDoctor .admin-doctor-confirm-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            padding: 0 22px 22px;
        }

        /* Outside-click / Escape acknowledgement. The flashcard refuses to be
           dismissed from outside, so each invalid attempt answers with one red
           danger ring that fades out (~3/4s) — visible once, never blinking
           continuously. Scoped to this flashcard only. */
        @keyframes admin-doctor-confirm-danger-nudge {
            0% {
                box-shadow: 0 24px 60px rgba(7, 30, 61, .3), 0 0 0 4px rgba(224, 90, 82, .95);
            }

            60% {
                box-shadow: 0 24px 60px rgba(7, 30, 61, .3), 0 0 0 4px rgba(224, 90, 82, .95);
            }

            100% {
                box-shadow: 0 24px 60px rgba(7, 30, 61, .3), 0 0 0 4px rgba(224, 90, 82, 0);
            }
        }

        #confirmDeactivateDoctor .modal-content.is-danger-nudge {
            animation: admin-doctor-confirm-danger-nudge .75s ease-out 1 forwards;
        }

        #confirmActivateDoctor .modal-content.is-danger-nudge {
            animation: admin-doctor-confirm-danger-nudge .75s ease-out 1 forwards;
        }

        /* Bootstrap's own static-backdrop scale pop is replaced by the red
           outline above, so the card itself never moves. */
        #confirmDeactivateDoctor.modal-static .modal-dialog {
            transform: none;
        }

        #confirmActivateDoctor.modal-static .modal-dialog {
            transform: none;
        }
        @media (max-width: 575.98px) {
            #confirmDeactivateDoctor .modal-dialog,
            #confirmActivateDoctor .modal-dialog {
                width: calc(100% - 16px);
                margin: 8px auto;
            }

            #confirmDeactivateDoctor .admin-doctor-confirm-body,
            #confirmActivateDoctor .admin-doctor-confirm-body {
                padding: 18px 16px 12px;
            }

            #confirmDeactivateDoctor .admin-doctor-confirm-actions,
            #confirmActivateDoctor .admin-doctor-confirm-actions {
                padding: 0 16px 18px;
            }
        }

        /* Mobile roster: the toggle keeps the patient's compact chip target
           (colored border, matching the other stacked-table actions) instead
           of the shared pill from admin-css/mobile.css. */
        @media (max-width: 767.98px) {
            .admin-main .admin-doctor-content .admin-doctor-actions button[data-doctor-toggle] {
                flex: 1 1 0;
                justify-content: center;
                min-height: 38px;
                padding: 0 8px;
                border: 1px solid #d5e4f5;
                border-radius: 8px;
                background: #fff;
                font-size: 12px;
            }

            .admin-main .admin-doctor-content .admin-doctor-actions button[data-doctor-toggle] {
                border-color: currentcolor;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const modalStates = [
                { element: document.getElementById('addDoctorModal'), parameter: 'create' },
                { element: document.getElementById('viewProviderModal'), parameter: 'view' },
                { element: document.getElementById('editProviderModal'), parameter: 'edit' },
            ].filter(({ element }) => element);
            const currentSearch = new URLSearchParams(window.location.search);
            const initialModalState = modalStates.find(({ parameter }) => currentSearch.has(parameter));

            document.querySelectorAll('[data-letters-only]').forEach((input) => {
                input.addEventListener('input', () => {
                    input.value = input.value
                        .replace(/[^A-Za-z ]+/g, '')
                        .replace(/^ +| +$/g, '');
                });
            });

            document.querySelectorAll('[data-digits-only]').forEach((input) => {
                input.addEventListener('input', () => {
                    input.value = input.value
                        .replace(/[^0-9 -]/g, '')
                        .replace(/^[^0-9]+|[^0-9]+$/g, '');
                });
            });

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

            const addForm = document.querySelector('#addDoctorModal form');

            function showToast(type, message) {
                document.querySelectorAll('.admin-toast').forEach((toast) => toast.remove());

                const toast = document.createElement('div');
                toast.className = 'admin-toast alert alert-' + (type === 'success' ? 'success' : 'danger')
                    + ' alert-dismissible fade show position-fixed';
                toast.style.cssText = 'top: 1rem; right: 1rem; z-index: 9999; min-width: 300px;';
                toast.setAttribute('role', 'alert');

                const text = document.createElement('span');
                text.textContent = message;
                toast.appendChild(text);

                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'btn-close';
                close.setAttribute('data-bs-dismiss', 'alert');
                close.setAttribute('aria-label', 'Close');
                toast.appendChild(close);

                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 150);
                }, 3000);
            }

            if (addForm) {
                addForm.addEventListener('submit', (event) => {
                    const password = addForm.querySelector('#password');
                    let passwordError = '';

                    if (password && password.value !== '') {
                        if (!/[A-Z]/.test(password.value)) {
                            passwordError = 'Password must contain at least one uppercase letter.';
                        } else if (!/[a-z]/.test(password.value)) {
                            passwordError = 'Password must contain at least one lowercase letter.';
                        } else if (!/[^A-Za-z0-9]/.test(password.value)) {
                            passwordError = 'Password must contain at least one special character (e.g. @, #, $, %).';
                        }
                    }

                    if (passwordError) {
                        event.preventDefault();
                        password.focus();
                        showToast('error', passwordError);

                        return;
                    }

                    const passwordConfirm = addForm.querySelector('#password_confirmation');

                    if (passwordConfirm && password.value !== '' && passwordConfirm.value !== '') {
                        if (password.value !== passwordConfirm.value) {
                            event.preventDefault();
                            passwordConfirm.focus();
                            showToast('error', 'Password confirmation must match the password.');

                            return;
                        }
                    }

                    const selectedDays = addForm.querySelectorAll('input[name="availability_days[]"]:checked').length;

                    if (selectedDays >= 3) {
                        return;
                    }

                    event.preventDefault();

                    const firstDay = addForm.querySelector('input[name="availability_days[]"]');

                    if (firstDay) {
                        firstDay.focus();
                    }

                    showToast('error', 'Doctor must have at least 3 scheduled days before it can be saved.');
                });
            }

            /* Doctor Deactivation — mirrors the Patient Deactivation flashcard
               flow: clicking Deactivate asks inside #confirmDeactivateDoctor
               instead of the browser's confirm(); the X and Cancel are the only
               ways it closes, outside clicks and Escape answer with a brief red
               danger outline, and the flashcard's Deactivate button submits the
               same PATCH status form the old submit button posted. Delegated on
               the table wrap because the rows are re-rendered by the filters. */
            const confirmDeactivateDoctorModal = bootstrap.Modal.getOrCreateInstance(
                document.getElementById('confirmDeactivateDoctor'),
            );
            const confirmActivateDoctorModal = bootstrap.Modal.getOrCreateInstance(
                document.getElementById('confirmActivateDoctor'),
            );
            let pendingDoctorDeactivation = null;

            document.querySelector('[data-doctor-table-wrap]')?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-doctor-toggle]');

                if (!button) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation(); /* keep the outside-click search clear from firing */

                if (button.dataset.active === '1') {
                    pendingDoctorDeactivation = button.closest('form');
                    document.querySelector('[data-confirm-doctor-name]').textContent = button.dataset.name;
                    confirmDeactivateDoctorModal.show();
                    return;
                }

                /* Activation */
                pendingDoctorDeactivation = button.closest('form');
                document.querySelector('[data-confirm-doctor-name-activate]').textContent = button.dataset.name;
                confirmActivateDoctorModal.show();
            });

            document.querySelector('[data-confirm-doctor-deactivate]')?.addEventListener('click', () => {
                const form = pendingDoctorDeactivation;
                pendingDoctorDeactivation = null;

                if (!form) {
                    return;
                }

                confirmDeactivateDoctorModal.hide();
                form.submit();
            });

            document.querySelector('[data-confirm-doctor-activate]')?.addEventListener('click', () => {
                const form = pendingDoctorDeactivation;
                pendingDoctorDeactivation = null;

                if (!form) {
                    return;
                }

                confirmActivateDoctorModal.hide();
                form.submit();
            });

            document.getElementById('confirmDeactivateDoctor').addEventListener('hidden.bs.modal', () => {
                pendingDoctorDeactivation = null;
            });

            document.getElementById('confirmActivateDoctor').addEventListener('hidden.bs.modal', () => {
                pendingDoctorDeactivation = null;
            });

            /* Outside clicks and Escape never dismiss this flashcard: with the
               static backdrop Bootstrap reports every invalid dismiss attempt
               as "hidePrevented" instead of hiding, and we answer it with a
               single brief red danger outline on the card. The card stays open
               throughout — only X and Cancel close it, and nothing is
               submitted from here. */
            let dangerNudgeTimer = null;

            document.getElementById('confirmDeactivateDoctor').addEventListener('hidePrevented.bs.modal', () => {
                const content = document.querySelector('#confirmDeactivateDoctor .modal-content');

                if (!content) {
                    return;
                }

                content.classList.remove('is-danger-nudge');
                void content.offsetWidth; /* restart the animation on rapid repeat clicks */
                content.classList.add('is-danger-nudge');
                window.clearTimeout(dangerNudgeTimer);
                dangerNudgeTimer = window.setTimeout(() => {
                    content.classList.remove('is-danger-nudge');
                    dangerNudgeTimer = null;
                }, 750);
            });

            document.getElementById('confirmActivateDoctor').addEventListener('hidePrevented.bs.modal', () => {
                const content = document.querySelector('#confirmActivateDoctor .modal-content');

                if (!content) {
                    return;
                }

                content.classList.remove('is-danger-nudge');
                void content.offsetWidth; /* restart the animation on rapid repeat clicks */
                content.classList.add('is-danger-nudge');
                window.clearTimeout(dangerNudgeTimer);
                dangerNudgeTimer = window.setTimeout(() => {
                    content.classList.remove('is-danger-nudge');
                    dangerNudgeTimer = null;
                }, 750);
            });

            const form = document.querySelector('[data-doctor-filters]');

            if (!form) {
                return;
            }

            const searchInput = form.querySelector('input[name="search"]');
            const tableWrap = document.querySelector('[data-doctor-table-wrap]');
            const resultCount = document.querySelector('[data-doctor-result-count]');
            const pagination = document.querySelector('[data-doctor-pagination]');
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
                        throw new Error('Unable to load providers.');
                    }

                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const nextTable = page.querySelector('[data-doctor-table-wrap]');
                    const nextCount = page.querySelector('[data-doctor-result-count]');
                    const nextPagination = page.querySelector('[data-doctor-pagination]');

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
                const clickedPagination = target.closest('[data-doctor-pagination]');

                const doctorModalTrigger = target.closest('[data-bs-target="#addDoctorModal"]');
                const doctorModalOpen = modalStates.some(({ element }) => element.classList.contains('show'));

                if (form.contains(target) || clickedPagination || doctorModalOpen || doctorModalTrigger) {
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
        })();
    </script>
@endpush