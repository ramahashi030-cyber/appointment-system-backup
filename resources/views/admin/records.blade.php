@extends('layouts.admin')

@section('title', 'Medical Records')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content admin-doctor-content">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="recordsDirectoryTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-file-earmark-medical-fill"></i>
                    <span><i class="bi bi-paperclip"></i></span>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="recordsDirectoryTitle">Medical Records</h1>
                    <p class="admin-telemedicine-welcome">Records Management</p>
                    <p class="admin-telemedicine-description">View, upload, and manage patient medical records, lab results, prescriptions, and consultation notes.</p>
                    <div class="admin-telemedicine-trust" aria-label="Records features">
                        <span><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Records</span>
                        <b aria-hidden="true">•</b>
                        <span>Lab Results</span>
                        <b aria-hidden="true">•</b>
                        <span>Prescriptions</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="admin-doctor-stat-grid">
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi bi-file-earmark-medical-fill" aria-hidden="true"></i></span>
                <span><small>Total records</small><strong>{{ number_format($recordStats['total'] ?? 0) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon green"><i class="bi bi-clipboard2-check-fill" aria-hidden="true"></i></span>
                <span><small>Lab results</small><strong>{{ number_format($recordStats['lab'] ?? 0) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon orange"><i class="bi bi-prescription2" aria-hidden="true"></i></span>
                <span><small>Prescriptions</small><strong>{{ number_format($recordStats['prescriptions'] ?? 0) }}</strong></span>
            </article>

        </div>

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="recordsRosterTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-file-earmark-medical-fill" aria-hidden="true"></i>
                    <h2 id="recordsRosterTitle">Medical records roster</h2>
                </div>
                <span class="admin-muted-text" data-record-result-count>{{ $records->total() ?? 0 }} record{{ ($records->total() ?? 0) === 1 ? '' : 's' }}</span>
            </header>

            <form class="admin-doctor-filters" method="GET" action="{{ route('admin.records') }}" data-record-filters>
                <div class="admin-doctor-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="recordSearch">Search records</label>
                    <input id="recordSearch" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search patient, type, or description...">
                </div>
                <select class="form-select" name="type" aria-label="Filter by record type">
                    <option value="">All types</option>
                    <option value="diagnosis" @selected(($filters['type'] ?? '') === 'diagnosis')>Diagnosis</option>
                    <option value="lab_result" @selected(($filters['type'] ?? '') === 'lab_result')>Lab Result</option>
                    <option value="prescription" @selected(($filters['type'] ?? '') === 'prescription')>Prescription</option>
                    <option value="treatment" @selected(($filters['type'] ?? '') === 'treatment')>Treatment</option>
                    <option value="consultation" @selected(($filters['type'] ?? '') === 'consultation')>Consultation</option>
                    <option value="document" @selected(($filters['type'] ?? '') === 'document')>Document</option>
                </select>
                <select class="form-select" name="patient" aria-label="Filter by patient">
                    <option value="">All patients</option>
                    @foreach ($patients ?? [] as $patient)
                        <option value="{{ $patient->id }}" @selected(($filters['patient'] ?? '') == $patient->id)>{{ $patient->full_name ?: 'Unnamed patient' }}</option>
                    @endforeach
                </select>
                @if (($filters['search'] ?? '') !== '' || ($filters['type'] ?? '') !== '' || ($filters['patient'] ?? '') !== '')
                    <a class="admin-clear-filter" href="{{ route('admin.records') }}">Clear</a>
                @endif
            </form>

            <div class="admin-doctor-table-wrap" data-record-table-wrap>
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">Medical records</caption>
                    <thead>
                        <tr>
                            <th scope="col">Patient</th>
                            <th scope="col">Type</th>
                            <th scope="col">Description</th>
                            <th scope="col">Date</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records ?? [] as $record)
                            @php
                                $recordType = (string) $record->record_type;
                                $recordTypeLabel = $recordType !== '' ? ucfirst(str_replace('_', ' ', $recordType)) : 'N/A';
                                $recordTypeClass = strtolower(str_replace(' ', '_', $recordType));
                            @endphp
                            <tr>
                                <td>
                                    <div class="admin-doctor-person">
                                        <span class="admin-avatar">{{ strtoupper(substr((string) ($record->patient?->first_name ?: 'P'), 0, 1).substr((string) ($record->patient?->last_name ?: 'R'), 0, 1)) }}</span>
                                        <span>
                                            <strong>{{ $record->patient?->full_name ?: 'Unknown patient' }}</strong>
                                            <small>{{ $record->patient?->email ?: 'No email' }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-status-pill {{ $recordTypeClass }}">
                                        {{ $recordTypeLabel }}
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ Str::limit($record->description ?? 'No description', 60) }}</span>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $record->created_at?->format('M d, Y') ?? 'No date' }}</span>
                                </td>
                                <td>
                                    <div class="admin-doctor-actions">
                                        <a href="{{ route('admin.records', ['view' => $record->id]) }}" aria-label="View record">View</a>
                                        <a href="{{ route('admin.records', ['edit' => $record->id]) }}" aria-label="Edit record">Edit</a>
                                        <form method="POST" action="{{ route('admin.records.destroy', $record) }}" onsubmit="return confirm('Delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-file-earmark-x" aria-hidden="true"></i>
                                        <strong>No records found</strong>
                                        <span>Adjust the filters or add a new record.</span>
                                        <a href="{{ route('admin.records', ['create' => 1]) }}" data-bs-toggle="modal" data-bs-target="#addRecordModal">Add record</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-doctor-pagination" data-record-pagination @if (! ($records->hasPages() ?? false)) hidden @endif>
                @if ($records->hasPages() ?? false)
                    @if ($records->onFirstPage())
                        <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&laquo;</span>
                    @else
                        <a class="admin-doctor-page-button" href="{{ $records->previousPageUrl() }}" aria-label="Previous page">&laquo;</a>
                    @endif
                    <span class="admin-doctor-page-current" aria-current="page">{{ $records->currentPage() }}</span>
                    @if ($records->hasMorePages())
                        <a class="admin-doctor-page-button" href="{{ $records->nextPageUrl() }}" aria-label="Next page">&raquo;</a>
                    @else
                        <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&raquo;</span>
                    @endif
                @endif
            </div>
        </section>
    </div>

    <!-- Add Record Modal -->
    <div class="modal fade admin-doctor-modal" id="addRecordModal" tabindex="-1" aria-labelledby="addRecordModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-file-earmark-plus-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="addRecordModalTitle">Add medical record</h2>
                            <p class="admin-telemedicine-description">Create a new medical record for a patient.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <form id="addRecordForm" class="admin-doctor-form" method="POST" action="{{ route('admin.records.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="admin-panel">
                            <div class="admin-panel-title">
                                <i class="bi bi-file-earmark-medical" aria-hidden="true"></i>
                                <h3>Record information</h3>
                            </div>
                            <div class="admin-doctor-form">
                                <div class="form-field">
                                    <label for="recordPatient">Patient <span>*</span></label>
                                    <select class="form-select" id="recordPatient" name="patient_id" required>
                                        <option value="">Select patient</option>
                                        @foreach ($patients ?? [] as $patient)
                                            <option value="{{ $patient->id }}">{{ $patient->full_name ?: 'Unnamed patient' }} ({{ $patient->email ?: 'No email' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="recordType">Record type <span>*</span></label>
                                    <select class="form-select" id="recordType" name="record_type" required>
                                        <option value="">Select type</option>
                                        <option value="diagnosis">Diagnosis</option>
                                        <option value="lab_result">Lab Result</option>
                                        <option value="prescription">Prescription</option>
                                        <option value="treatment">Treatment</option>
                                        <option value="consultation">Consultation</option>
                                        <option value="document">Document</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="recordDescription">Description <span>*</span></label>
                                    <textarea class="form-control" id="recordDescription" name="description" rows="3" placeholder="Enter record details..." required></textarea>
                                </div>
                                <div class="form-field">
                                    <label for="recordFile">Attachment</label>
                                    <input type="file" class="form-control" id="recordFile" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    <small style="color: #8ca2bd; font-size: 13px;">PDF, images, or documents (optional)</small>
                                </div>
                            </div>
                        </div>
                        <div class="admin-doctor-form-actions">
                            <button type="submit" class="admin-primary-button">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                <span>Save record</span>
                            </button>
                            <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Record Modal -->
    @if (($recordModal ?? null) === 'view')
        <div class="modal fade admin-doctor-modal modal-wide" id="viewRecordModal" tabindex="-1" aria-labelledby="viewRecordModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <header class="modal-header admin-doctor-modal-header">
                        <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                        <div class="admin-telemedicine-content">
                            <div class="admin-telemedicine-mark" aria-hidden="true">
                                <i class="bi bi-file-earmark-medical-fill"></i>
                            </div>
                            <div class="admin-telemedicine-copy">
                                <h2 class="modal-title" id="viewRecordModalTitle">Record details</h2>
                                <p class="admin-telemedicine-description">View medical record information.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </header>
                    <div class="modal-body">
                        @if ($recordDetail ?? null)
                            @php
                                $detailType = (string) $recordDetail->record_type;
                                $detailTypeLabel = $detailType !== '' ? ucfirst(str_replace('_', ' ', $detailType)) : 'N/A';
                                $detailTypeClass = strtolower(str_replace(' ', '_', $detailType));
                            @endphp
                            <div class="admin-doctor-profile-hero">
                                <span class="admin-avatar">{{ strtoupper(substr((string) ($recordDetail->patient?->first_name ?: 'P'), 0, 1).substr((string) ($recordDetail->patient?->last_name ?: 'R'), 0, 1)) }}</span>
                                <div>
                                    <h2>{{ $recordDetail->patient?->full_name ?: 'Unknown patient' }}</h2>
                                    <p>{{ $recordDetail->patient?->email ?: '' }} &bull; {{ $recordDetail->patient?->contact_number ?: 'No phone' }}</p>
                                </div>
                                <span class="admin-status-pill {{ $detailTypeClass }}">
                                    {{ $detailTypeLabel }}
                                </span>
                            </div>
                            <div class="admin-panel">
                                <div class="admin-panel-title">
                                    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                                    <h3>Record details</h3>
                                </div>
                                <div class="admin-doctor-form">
                                    <div class="form-field">
                                        <label>Record type</label>
                                        <p>{{ $detailTypeLabel }}</p>
                                    </div>
                                    <div class="form-field">
                                        <label>Description</label>
                                        <p>{{ $recordDetail->description ?? 'No description' }}</p>
                                    </div>
                                    <div class="form-field">
                                        <label>Date created</label>
                                        <p>{{ $recordDetail->created_at?->format('F d, Y') ?? 'No date' }}</p>
                                    </div>
                                    <div class="form-field">
                                        <label>Attachment</label>
                                        <p>
                                            @if ($recordDetail->file_path)
                                                <a href="{{ asset('storage/' . $recordDetail->file_path) }}" target="_blank" style="color: #0877ed;">
                                                    <i class="bi bi-paperclip" aria-hidden="true"></i> View attachment
                                                </a>
                                            @else
                                                No attachment
                                            @endif
                                        </p>
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
                { element: document.getElementById('addRecordModal'), parameter: 'create' },
                { element: document.getElementById('viewRecordModal'), parameter: 'view' },
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
            const form = document.querySelector('[data-record-filters]');
            if (!form) {
                return;
            }

            const searchInput = form.querySelector('input[name="search"]');
            const tableWrap = document.querySelector('[data-record-table-wrap]');
            const resultCount = document.querySelector('[data-record-result-count]');
            const pagination = document.querySelector('[data-record-pagination]');
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
                        throw new Error('Unable to load records.');
                    }

                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const nextTable = page.querySelector('[data-record-table-wrap]');
                    const nextCount = page.querySelector('[data-record-result-count]');
                    const nextPagination = page.querySelector('[data-record-pagination]');

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
                const clickedPagination = target.closest('[data-record-pagination]');
                const recordModalTrigger = target.closest('[data-bs-target="#addRecordModal"]');
                const recordModalOpen = modalStates.some(({ element }) => element.classList.contains('show'));

                if (form.contains(target) || clickedPagination || recordModalOpen || recordModalTrigger) {
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
        });
    </script>
@endpush