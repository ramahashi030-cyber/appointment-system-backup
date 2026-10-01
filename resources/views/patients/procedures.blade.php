{{--
    Patient procedures from the HOMIS procedure-order tables.
    Renders as a full page normally, or as bare content inside the dashboard modal.
--}}
@extends(request()->ajax() ? 'layouts.modal' : 'layouts.app')

@section('title', 'My Procedures')

@section('content')
    @php
        $procedureRows = $procedures ?? [];
        $hasProcedures = count($procedureRows) > 0;
        $homisMessage = $homisStatus['message'] ?? null;
        $hasHospitalNumber = $hasHospitalNumber ?? false;
    @endphp

    @unless (request()->ajax())
        <div class="mb-4">
            <h1 class="page-title h3 mb-0">
                <i class="bi bi-activity me-2"></i>My Procedures
            </h1>
            <p class="page-subtitle mb-0">Procedures performed for you</p>
        </div>
    @endunless

    @if (! $hasHospitalNumber)
        <div class="alert alert-info border-0 shadow-sm" role="status">
            <i class="bi bi-info-circle me-2"></i>Add your hospital number to your profile to load live HOMIS procedures.
        </div>
    @elseif (! ($homisStatus['available'] ?? false) && $homisMessage && ! $hasProcedures)
        <div class="alert alert-warning border-0 shadow-sm" role="status">
            <i class="bi bi-info-circle me-2"></i>{{ $homisMessage }}
        </div>
    @endif

    @if ($hasProcedures)
        <div class="card soft-card">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h5 mb-1"><i class="bi bi-hospital me-2"></i>HOMIS Procedures</h2>
                        <p class="text-muted small mb-0">Procedure orders and result availability from HOMIS.</p>
                    </div>
                    <span class="badge text-bg-success">Live</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order / Date</th>
                                <th>Procedure</th>
                                <th>Encounter</th>
                                <th>Result</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($procedureRows as $procedure)
                                @php
                                    $procedureDate = trim((string) ($procedure['date'] ?? ''));
                                    $dateTimestamp = $procedureDate !== '' ? strtotime($procedureDate) : false;
                                    $resultAvailable = (bool) ($procedure['result_available'] ?? false);
                                    $resultUrl = $procedure['result_url'] ?? null;
                                @endphp
                                <tr>
                                    <td class="text-nowrap">
                                        <strong>{{ $procedure['procedure_number'] ?: '—' }}</strong><br>
                                        <small class="text-muted">{{ $dateTimestamp ? date('M d, Y', $dateTimestamp) : ($procedureDate ?: '—') }}</small>
                                    </td>
                                    <td class="fw-semibold">{{ $procedure['procedure'] ?: 'Procedure' }}</td>
                                    <td>{{ $procedure['encounter_code'] ?: '—' }}</td>
                                    <td>
                                        @if ($resultAvailable)
                                            <span class="badge text-bg-success">Available</span>
                                        @else
                                            <span class="badge text-bg-secondary">Not available</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($resultUrl)
                                            <a href="{{ $resultUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary btn-pill">
                                                <i class="bi bi-eye me-1"></i>View result
                                            </a>
                                        @elseif ($resultAvailable)
                                            <span class="text-muted small">Ask the hospital for access</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card soft-card">
            <div class="empty-state">
                <i class="bi bi-clipboard2-pulse d-block mb-2"></i>
                <p class="mb-1">No procedures to show yet.</p>
                <p class="text-muted small mb-0">
                    Procedures recorded by the hospital will appear here when HOMIS is available.
                </p>
            </div>
        </div>
    @endif
@endsection