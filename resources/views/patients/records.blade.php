{{--
    Patient medical records — list + jQuery CRUD.
    Renders as a full page normally, or as bare content inside the dashboard modal.

    Expected variables:
      $patientName   string
      $records       Illuminate\Support\Collection of MedicalRecord
      $homisHistory  array<int, array<string, string|null>>
      $homisStatus   array{available:bool,configured:bool,message:string}
      $canEdit       bool — controls whether the write form is shown
--}}
@extends(request()->ajax() ? 'layouts.modal' : 'layouts.app')

@section('title', 'Medical Records')

@section('content')
    @php $inModal = request()->ajax(); @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        @unless ($inModal)
            <div>
                <h1 class="page-title h3 mb-0">
                    <i class="bi bi-folder2-open me-2"></i>Medical Records
                </h1>
                <p class="page-subtitle mb-0">
                    {{ $patientName !== '' ? $patientName.' — ' : '' }}your laboratory results, prescriptions and other records
                </p>
            </div>
        @else
            <p class="text-muted mb-0">
                {{ $patientName !== '' ? $patientName.' — ' : '' }}your laboratory results, prescriptions and other records
            </p>
        @endunless

        @if ($canEdit)
            <button type="button"
                    class="btn btn-primary btn-pill px-4"
                    data-bs-toggle="modal"
                    data-bs-target="#recordModal"
                    onclick="openAddModal()">
                <i class="bi bi-plus-lg me-1"></i>Add Record
            </button>
        @endif
    </div>

    {{-- jQuery posts answer into this box --}}
    <div id="recordsAlert" class="mb-3"></div>

    @if (! ($hasHospitalNumber ?? false))
        <div class="alert alert-info border-0 shadow-sm" role="status">
            <i class="bi bi-info-circle me-2"></i>Add your hospital number to your profile to load live HOMIS visit history.
        </div>
    @elseif (! ($homisStatus['available'] ?? false) && !empty($homisStatus['message']) && empty($homisHistory))
        <div class="alert alert-warning border-0 shadow-sm" role="status">
            <i class="bi bi-info-circle me-2"></i>{{ $homisStatus['message'] }}
        </div>
    @endif

    @if (!empty($homisHistory))
        <section class="card soft-card mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h5 mb-1"><i class="bi bi-hospital me-2"></i>HOMIS Visit History</h2>
                        <p class="text-muted small mb-0">Outpatient, emergency, and admission encounters recorded by the hospital.</p>
                    </div>
                    <span class="badge text-bg-success">Live</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Visit type</th>
                                <th>Encounter code</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($homisHistory as $visit)
                                @php
                                    $visitDate = trim((string) ($visit['date'] ?? ''));
                                    $visitTimestamp = $visitDate !== '' ? strtotime($visitDate) : false;
                                @endphp
                                <tr>
                                    <td><span class="badge text-bg-light text-dark">{{ $visit['visit_type'] ?: 'Visit' }}</span></td>
                                    <td>{{ $visit['encounter_code'] ?: '—' }}</td>
                                    <td>{{ $visitTimestamp ? date('M d, Y', $visitTimestamp) : ($visitDate ?: '—') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    <div class="card soft-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>File</th>
                            <th>Added</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="recordsBody">
                        @forelse ($records as $record)
                            @php
                                $path = (string) $record->file_path;
                                $fileUrl = $path === ''
                                    ? null
                                    : (str_starts_with($path, 'http') ? $path : asset($path));
                            @endphp
                            <tr data-id="{{ $record->id }}"
                                data-type="{{ $record->record_type }}"
                                data-description="{{ $record->description }}"
                                data-file-path="{{ $path }}"
                                data-file-url="{{ $fileUrl }}"
                                data-file-name="{{ $path === '' ? '' : basename($path) }}">
                                <td data-label="#">{{ $record->id }}</td>
                                <td data-label="Type">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="record-icon"><i class="bi bi-file-earmark-medical"></i></span>
                                        <span class="fw-semibold">{{ $record->record_type }}</span>
                                    </div>
                                </td>
                                <td data-label="Description">
                                    {{ \Illuminate\Support\Str::limit($record->description ?? '—', 80) }}
                                </td>
                                <td data-label="File">
                                    @if ($fileUrl)
                                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener"
                                           class="btn btn-sm btn-outline-primary btn-pill">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Added">{{ $record->created_at?->format('M d, Y h:i A') ?? '—' }}</td>
                                <td data-label="Actions" class="text-end">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary btn-pill me-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#recordModal"
                                            onclick='openEditModal(this.closest("tr"))'>
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-pill"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal"
                                            onclick='openDeleteModal(this.closest("tr"))'>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="empty-state {{ $records->isNotEmpty() ? 'd-none' : '' }}" id="emptyState">
                <i class="bi bi-folder-x d-block mb-2"></i>
                <p class="mb-0">{{ $canEdit ? 'No medical records yet. Add your first one.' : 'No medical records to show.' }}</p>
            </div>
        </div>
    </div>

    {{-- ADD / EDIT MODAL (moved to <body> by the dashboard loader when shown inside the modal) --}}
    <div class="modal fade" id="recordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="recordForm" enctype="multipart/form-data" data-store-url="{{ route('records.store') }}">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="recordModalTitle">
                            <i class="bi bi-file-earmark-plus me-2"></i>Add Record
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div id="modalAlert"></div>

                        <div class="mb-3">
                            <label class="form-label" for="record_type">Record Type</label>
                            <input type="text"
                                   name="record_type"
                                   id="record_type"
                                   class="form-control"
                                   list="recordTypeOptions"
                                   required
                                   maxlength="100"
                                   placeholder="e.g. Laboratory Result">
                            <datalist id="recordTypeOptions">
                                <option value="Laboratory Result">
                                <option value="Radiology / X-Ray">
                                <option value="Ultrasound">
                                <option value="Prescription">
                                <option value="Discharge Summary">
                                <option value="Medical Certificate">
                                <option value="Operative Record">
                                <option value="Consultation Note">
                                <option value="Others">
                            </datalist>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="description">Description</label>
                            <textarea name="description"
                                      id="description"
                                      class="form-control"
                                      rows="3"
                                      maxlength="2000"
                                      placeholder="What is this record about?"></textarea>
                        </div>

                        <div class="mb-1">
                            <label class="form-label" for="file">File (optional)</label>
                            <input type="file"
                                   name="file"
                                   id="file"
                                   class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                            <div class="form-text">
                                PDF, image or document up to 10 MB.
                                <span id="currentFileHint" class="d-none">
                                    Currently: <strong id="currentFileName"></strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="recordSubmitBtn" class="btn btn-primary px-4">
                            Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- DELETE CONFIRM MODAL --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>Delete Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    Delete <strong id="deleteRecordType">this record</strong>?
                    The uploaded file will be removed too. This cannot be undone.
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="deleteConfirmBtn" class="btn btn-danger px-4">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- Only used on the full page. Inside the dashboard modal these load from patient-dashboard-modals. --}}
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/records.js') }}"></script>
@endpush