{{--
    Patient procedures from the HOMIS procedure-order tables.
    Renders as a full page normally, or as bare content inside the dashboard modal.
    UI mirrors patients/prescriptions.blade.php (RX cards).
--}}
@extends(request()->ajax() ? 'layouts.modal' : 'layouts.app')

@section('title', 'My Procedures')

@section('styles')
    .rx-page {
        --rx-navy: #0a2674;
        --rx-blue: #1e6ef2;
        --rx-green: #6a9a3d;
        max-width: 1280px;
        margin: 0 auto 24px;
        padding: 26px 24px 30px;
        background: #eef2f9;
        border-radius: 18px;
    }

    .modal .rx-page {
        margin-bottom: 0;
        border-radius: 0;
        padding: 10px 4px 16px;
        background: transparent;
    }

    .rx-page-head {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .rx-page-icon {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: #d9e6fd;
        color: var(--rx-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex: none;
    }

    .rx-page-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
        color: var(--rx-navy);
    }

    .rx-page-sub {
        margin: 3px 0 0;
        font-size: 0.875rem;
        color: #64748b;
    }

    .rx-page-rule {
        height: 1px;
        background: #d7e0ee;
        margin: 16px 0 22px;
    }

    .rx-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 6px 18px rgba(15, 40, 90, 0.07);
        overflow: hidden;
        margin-bottom: 22px;
    }

    .rx-card-head {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 16px 20px;
    }

    .rx-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        background: var(--rx-blue);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        flex: none;
    }

    .rx-card-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--rx-navy);
        white-space: nowrap;
    }

    .rx-card-divider {
        color: #cbd5e1;
        font-weight: 400;
    }

    .rx-card-sub {
        font-size: 0.85rem;
        color: #64748b;
    }

    .rx-card-date {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 0.925rem;
        font-weight: 600;
        color: var(--rx-navy);
        white-space: nowrap;
    }

    .rx-card-date i {
        color: var(--rx-blue);
        font-size: 1.05rem;
    }

    .rx-card-table .table {
        min-width: 560px;
    }

    .rx-table {
        margin: 0;
        color: #1e293b;
        --bs-table-bg: transparent;
        --bs-table-hover-bg: #f7faff;
    }

    .rx-table thead th {
        background: var(--rx-navy);
        color: #fff;
        border: 0;
        font-size: 0.9rem;
        font-weight: 600;
        padding: 11px 20px;
        white-space: nowrap;
        vertical-align: middle;
    }

    .rx-table tbody td {
        border-bottom: 1px solid #eef2f7;
        font-size: 0.9rem;
        padding: 12px 20px;
        vertical-align: middle;
    }

    .rx-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .rx-col-date,
    .rx-col-encounter,
    .rx-col-result,
    .rx-col-action {
        width: 1%;
        white-space: nowrap;
    }

    .rx-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 0;
        border-radius: 8px;
        color: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 6px 15px;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
        transition: filter 0.15s ease, transform 0.15s ease;
    }

    .rx-btn:hover,
    .rx-btn:focus-visible {
        color: #fff;
        filter: brightness(1.08);
    }

    .rx-btn:active {
        transform: translateY(1px);
    }

    .rx-btn-print {
        background: var(--rx-blue);
    }

    .rx-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 3px 11px;
        white-space: nowrap;
    }

    .rx-status-available {
        background: #e7f5e9;
        color: #2e7d32;
    }

    .rx-status-pending {
        background: #eef2f7;
        color: #64748b;
    }

    @media (max-width: 576px) {
        .rx-page {
            padding: 20px 14px 24px;
            border-radius: 12px;
        }

        .rx-page-title {
            font-size: 1.25rem;
        }

        .rx-card-head {
            padding: 14px;
        }

        .rx-card-date {
            margin-left: 0;
            width: 100%;
            padding-left: 50px;
        }

        .rx-table thead th,
        .rx-table tbody td {
            padding: 10px 14px;
        }
    }
@endsection

@section('content')
    @php
        $procedureRows = $procedures ?? [];
        $hasProcedures = count($procedureRows) > 0;
        $homisMessage = $homisStatus['message'] ?? null;
        $hasHospitalNumber = $hasHospitalNumber ?? false;

        // Latest procedure date is shown on the card header (like the RX list).
        $latestProcedure = 0;
        foreach ($procedureRows as $procedure) {
            $timestamp = strtotime((string) ($procedure['date'] ?? ''));
            if ($timestamp !== false && $timestamp > $latestProcedure) {
                $latestProcedure = $timestamp;
            }
        }
    @endphp

    <div class="rx-page">
        <div class="rx-page-head">
            <span class="rx-page-icon" aria-hidden="true"><i class="bi bi-clipboard2-pulse"></i></span>
            <div>
                <h1 class="rx-page-title">Procedure List</h1>
                <p class="rx-page-sub">Procedures performed for you</p>
            </div>
        </div>
        <div class="rx-page-rule"></div>

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
            <article class="rx-card">
                <header class="rx-card-head">
                    <span class="rx-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-check-fill"></i></span>
                    <span class="rx-card-title">HOMIS Procedures</span>
                    <span class="rx-card-divider" aria-hidden="true">|</span>
                    <span class="rx-card-sub">Procedure orders and result availability from HOMIS.</span>
                    @if ($latestProcedure > 0)
                        <span class="rx-card-date"><i class="bi bi-calendar3" aria-hidden="true"></i> {{ date('M d, Y', $latestProcedure) }}</span>
                    @endif
                </header>

                <div class="table-responsive rx-card-table">
                    <table class="table rx-table mb-0">
                        <thead>
                            <tr>
                                <th>Order / Date</th>
                                <th>Procedure</th>
                                <th class="rx-col-encounter">Encounter</th>
                                <th class="rx-col-result">Result</th>
                                <th class="rx-col-action">Action</th>
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
                                    <td class="rx-col-encounter">{{ $procedure['encounter_code'] ?: '—' }}</td>
                                    <td class="rx-col-result">
                                        @if ($resultAvailable)
                                            <span class="rx-status rx-status-available">Available</span>
                                        @else
                                            <span class="rx-status rx-status-pending">Not available</span>
                                        @endif
                                    </td>
                                    <td class="rx-col-action">
                                        @if ($resultUrl)
                                            <a href="{{ $resultUrl }}" target="_blank" rel="noopener" class="rx-btn rx-btn-print">
                                                <i class="bi bi-eye" aria-hidden="true"></i>View result
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
            </article>
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
    </div>
@endsection
