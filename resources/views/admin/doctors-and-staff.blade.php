@extends('layouts.admin')

@section('title', 'Doctors')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
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
                                            <button type="submit">{{ $doctor->is_active ? 'Deactivate' : 'Activate' }}</button>
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
        <div class="modal fade admin-doctor-modal modal-wide" id="viewProviderModal" tabindex="-1" aria-labelledby="viewProviderModalTitle" aria-hidden="true">
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
@endsection

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
