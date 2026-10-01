{{--
    Patient prescriptions from both the local portal table and HOMIS.
    Renders as a full page normally, or as bare content inside the dashboard modal.
--}}
@extends(request()->ajax() ? 'layouts.modal' : 'layouts.app')

@section('title', 'My Prescriptions')

@section('content')
    @php
        $homisPrescriptionRows = $homisPrescriptions ?? [];
        $hasHomisPrescriptions = count($homisPrescriptionRows) > 0;
        $hasLocalPrescriptions = isset($prescriptions) && $prescriptions->isNotEmpty();
        $homisMessage = $homisStatus['message'] ?? null;
        $hasHospitalNumber = $hasHospitalNumber ?? false;
    @endphp

    @unless (request()->ajax())
        <div class="mb-4">
            <h1 class="page-title h3 mb-0">
                <i class="bi bi-capsule-pill me-2"></i>My Prescriptions
            </h1>
            <p class="page-subtitle mb-0">Medications prescribed to you</p>
        </div>
    @endunless

    @if (! $hasHospitalNumber)
        <div class="alert alert-info border-0 shadow-sm" role="status">
            <i class="bi bi-info-circle me-2"></i>Add your hospital number to your profile to load live HOMIS prescriptions.
        </div>
    @elseif (! ($homisStatus['available'] ?? false) && $homisMessage && ! $hasHomisPrescriptions)
        <div class="alert alert-warning border-0 shadow-sm" role="status">
            <i class="bi bi-info-circle me-2"></i>{{ $homisMessage }}
        </div>
    @endif

    @if ($hasHomisPrescriptions)
        <section class="card soft-card mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h5 mb-1"><i class="bi bi-hospital me-2"></i>HOMIS Prescriptions</h2>
                        <p class="text-muted small mb-0">Live medication history from the hospital information system.</p>
                    </div>
                    <span class="badge text-bg-success">Live</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Rx / Date</th>
                                <th>Medicine</th>
                                <th>Qty</th>
                                <th>Instructions</th>
                                <th>Doctor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($homisPrescriptionRows as $prescription)
                                @php
                                    $prescriptionDate = trim((string) ($prescription['date'] ?? ''));
                                    $dateTimestamp = $prescriptionDate !== '' ? strtotime($prescriptionDate) : false;
                                    $instructions = trim((string) ($prescription['instructions'] ?? ''));
                                    $remarks = trim((string) ($prescription['remarks'] ?? ''));
                                @endphp
                                <tr>
                                    <td class="text-nowrap">
                                        <strong>{{ $prescription['prescription_number'] ?: '—' }}</strong><br>
                                        <small class="text-muted">{{ $dateTimestamp ? date('M d, Y', $dateTimestamp) : ($prescriptionDate ?: '—') }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $prescription['medicine'] ?: '—' }}</span>
                                        @if ($remarks !== '')
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($remarks, 80) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $prescription['quantity'] ?: '—' }}</td>
                                    <td>{{ $instructions ?: '—' }}</td>
                                    <td>{{ $prescription['doctor_name'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    @if ($hasLocalPrescriptions)
        <section class="card soft-card mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h2 class="h5 mb-1"><i class="bi bi-file-earmark-medical me-2"></i>Portal Prescriptions</h2>
                <p class="text-muted small mb-0">Prescriptions saved in the patient portal.</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Doctor</th>
                                <th>Notes</th>
                                <th>Attachment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prescriptions as $prescription)
                                <tr>
                                    <td class="text-nowrap">{{ $prescription->created_at?->format('M d, Y') }}</td>
                                    <td>{{ $prescription->doctor_name ?: '—' }}</td>
                                    <td>{{ $prescription->notes ?: '—' }}</td>
                                    <td>
                                        @if ($prescription->file_path)
                                            <a href="{{ \Illuminate\Support\Str::startsWith($prescription->file_path, ['http://', 'https://']) ? $prescription->file_path : asset($prescription->file_path) }}"
                                               target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary btn-pill">
                                                <i class="bi bi-eye me-1"></i>View
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    @if (! $hasHomisPrescriptions && ! $hasLocalPrescriptions)
        <div class="card soft-card">
            <div class="empty-state">
                <i class="bi bi-capsule d-block mb-2"></i>
                <p class="mb-0">You have no prescriptions yet.</p>
            </div>
        </div>
    @endif
@endsection