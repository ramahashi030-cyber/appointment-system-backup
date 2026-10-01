@extends('layouts.admin')

@section('title', 'Patients')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
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
                <span><small>Total patients</small><strong data-patient-stat="total">{{ number_format($patientStats['total']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon green"><i class="bi bi-person-check-fill" aria-hidden="true"></i></span>
                <span><small>Active</small><strong data-patient-stat="active">{{ number_format($patientStats['active']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon orange"><i class="bi bi-person-exclamation" aria-hidden="true"></i></span>
                <span><small>Pending</small><strong data-patient-stat="pending">{{ number_format($patientStats['pending']) }}</strong></span>
            </article>
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
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPatientModal">
                        <i class="bi bi-person-plus-fill" aria-hidden="true"></i> Add patient
                    </button>
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
                @if ($filters['search'] !== '' || $filters['status'] !== '' || $filters['gender'] !== '')
                    <a class="admin-clear-filter" href="{{ route('admin.patients') }}">Clear</a>
                @endif
            </form>

            {{-- Paginated at 20 rows, so the table is never given its own scrollbar; see pages/patients.css. --}}
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
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $patient->contact_number ?: '—' }}</span>
                                    <small class="admin-doctor-secondary-text">{{ $patient->email ?: 'No email' }}</small>
                                </td>
                                <td>
                                    @if ($patient->hospital_number)
                                        <span class="admin-patient-code">{{ $patient->hospital_number }}</span>
                                    @else
                                        <span class="admin-patient-empty-value">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($registeredAt)
                                        <time datetime="{{ $registeredAt->toDateString() }}">{{ $registeredAt->format('M j, Y') }}</time>
                                    @else
                                        <span class="admin-patient-empty-value">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="admin-status-pill {{ strtolower((string) $patient->status) }}">{{ $patient->status ?: 'Unknown' }}</span>
                                </td>
                                <td>
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
                {{ $patients->links('pagination::bootstrap-5') }}
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
                                <p class="admin-telemedicine-description">Review {{ trim(implode(' ', array_filter([$patient->first_name, $patient->middlename, $patient->last_name]))) ?: 'this patient' }}'s information, medical summary, and activity.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </header>
                    <div class="modal-body">
                        @include('admin.patients._profile')
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
                                <p class="admin-telemedicine-description">Update {{ trim(implode(' ', array_filter([$patient->first_name, $patient->middlename, $patient->last_name]))) ?: 'this patient' }}'s profile and account settings.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </header>
                    <div class="modal-body">
                        @include('admin.patients._form', [
                            'patient' => $patient,
                            'formAction' => route('admin.patients.update', $patient),
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
                            <input class="form-control" id="cpFirst" name="firstname" value="{{ old('firstname') }}" placeholder="First name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="cpMiddle">Middle name</label>
                            <input class="form-control" id="cpMiddle" name="middlename" value="{{ old('middlename') }}" placeholder="Middle name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="cpLast">Last name</label>
                            <input class="form-control" id="cpLast" name="lastname" value="{{ old('lastname') }}" placeholder="Last name" required>
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
                            <input class="form-control" id="cpContact" name="contactno" value="{{ old('contactno') }}" placeholder="Contact number">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cpAddress">Address</label>
                            <input class="form-control" id="cpAddress" name="address" value="{{ old('address') }}" placeholder="Address">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cpHospital">Hospital no.</label>
                            <input class="form-control" id="cpHospital" name="hospital_number" value="{{ old('hospital_number') }}" placeholder="Hospital no.">
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
                    // Keep the existing inputs (and their focus); only sync the Clear link.
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

            // View / Edit: open the modal in place so filters, search and page are kept.
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