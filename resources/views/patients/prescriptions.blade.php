{{--
    Patient prescriptions from both the local portal table and HOMIS.

    The HOMIS list is grouped by charge slip (pcchrgcod), exactly like the
    legacy QALINGA1 prescriptions2.php page: one card per RX showing the
    patient, the latest prescription date and its medicine lines, with the
    Print button (printRx — same printable design and rx_view.php QR as
    prescriptions2.php) and the Renew button (legacy undercons.php).

    Renders as a full page normally, or as bare content inside the dashboard
    modal — both surfaces use this single design.
--}}
@extends(request()->ajax() ? 'layouts.modal' : 'layouts.app')

@section('title', 'My Prescriptions')

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

    .rx-card-patient {
        font-size: 1rem;
        font-weight: 700;
        color: var(--rx-navy);
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

    .rx-col-qty,
    .rx-col-date {
        width: 1%;
        white-space: nowrap;
    }

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

    .rx-btn-renew {
        background: var(--rx-green);
        margin-left: 8px;
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
        $homisPrescriptionRows = $homisPrescriptions ?? [];
        $hasLocalPrescriptions = isset($prescriptions) && $prescriptions->isNotEmpty();
        $homisMessage = $homisStatus['message'] ?? null;
        $hasHospitalNumber = $hasHospitalNumber ?? false;
        $patientName = (string) ($patientName ?? '');

        // prescriptions2.php: one group per charge slip (pcchrgcod = RX number).
        $rxGroups = [];
        foreach ($homisPrescriptionRows as $row) {
            $rxNumber = trim((string) ($row['prescription_number'] ?? ''));
            if ($rxNumber === '') {
                continue;
            }
            $rxGroups[$rxNumber][] = $row;
        }

        // Each card shows its own latest prescription date; newest RX first.
        $rxCards = [];
        foreach ($rxGroups as $rxNumber => $rows) {
            $latest = 0;
            $latestDate = '';
            foreach ($rows as $row) {
                $timestamp = strtotime((string) ($row['date'] ?? ''));
                if ($timestamp !== false && $timestamp > $latest) {
                    $latest = $timestamp;
                    $latestDate = (string) ($row['date'] ?? '');
                }
            }
            $rxCards[] = [
                'number' => $rxNumber,
                'rows' => $rows,
                'latest' => $latest,
                'latestDate' => $latestDate,
            ];
        }
        usort($rxCards, fn (array $a, array $b) => $b['latest'] <=> $a['latest']);

        $hasHomisPrescriptions = count($rxCards) > 0;
        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
    @endphp

    <div class="rx-page">
        <div class="rx-page-head">
            <span class="rx-page-icon" aria-hidden="true"><i class="bi bi-capsule-pill"></i></span>
            <div>
                <h1 class="rx-page-title">Prescription List</h1>
                <p class="rx-page-sub">View and manage your prescriptions</p>
            </div>
        </div>
        <div class="rx-page-rule"></div>

        @if (! $hasHospitalNumber)
            <div class="alert alert-info border-0 shadow-sm" role="status">
                <i class="bi bi-info-circle me-2"></i>Add your hospital number to your profile to load live HOMIS prescriptions.
            </div>
        @elseif (! ($homisStatus['available'] ?? false) && $homisMessage && ! $hasHomisPrescriptions)
            <div class="alert alert-warning border-0 shadow-sm" role="status">
                <i class="bi bi-info-circle me-2"></i>{{ $homisMessage }}
            </div>
        @endif

        @foreach ($rxCards as $card)
            @php
                $firstRow = $card['rows'][0];
                $cardPatientName = trim((string) ($firstRow['patient_name'] ?? ''));
                if ($cardPatientName === '') {
                    $cardPatientName = trim($patientName);
                }
                $cardPatientName = strtoupper($cardPatientName);
                $cardDate = $card['latest'] > 0 ? date('M d, Y', $card['latest']) : '—';

                // printRx() payload — same fields prescriptions2.php passed.
                $printRows = array_map(fn (array $row): array => [
                    'itemdesc' => (string) ($row['medicine'] ?? ''),
                    'qty' => (string) ($row['quantity'] ?? ''),
                    'signa' => (string) ($row['signa'] ?? ''),
                    'remarks' => (string) ($row['remarks'] ?? ''),
                ], $card['rows']);
                $printPatient = [
                    'patname' => $cardPatientName,
                    'hospital_number' => (string) ($firstRow['hospital_number'] ?? ''),
                    'dodate' => $card['latestDate'],
                    'age' => (string) ($firstRow['age'] ?? ''),
                    'patsex' => (string) ($firstRow['sex'] ?? ''),
                    'location_label' => (string) ($firstRow['location_label'] ?? ''),
                    'ward_name' => (string) ($firstRow['ward_name'] ?? ''),
                    'doctor_name' => (string) ($firstRow['doctor_name'] ?? ''),
                    'licno' => (string) ($firstRow['license_no'] ?? ''),
                ];
            @endphp

            <article class="rx-card"
                data-rx-number="{{ $card['number'] }}"
                data-rx-records="{{ json_encode($printRows, $jsonFlags) }}"
                data-rx-patient="{{ json_encode($printPatient, $jsonFlags) }}">
                <header class="rx-card-head">
                    <span class="rx-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-medical-fill"></i></span>
                    <span class="rx-card-title">RX # {{ $card['number'] }}</span>
                    <span class="rx-card-divider" aria-hidden="true">|</span>
                    <span class="rx-card-patient">{{ $cardPatientName }}</span>
                    <span class="rx-card-date"><i class="bi bi-calendar3" aria-hidden="true"></i> {{ $cardDate }}</span>
                </header>

                <div class="table-responsive rx-card-table">
                    <table class="table rx-table mb-0">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th class="rx-col-qty">Qty</th>
                                <th class="rx-col-date">Date</th>
                                <th class="rx-col-action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($card['rows'] as $row)
                                @php
                                    $rowDate = trim((string) ($row['date'] ?? ''));
                                    $rowTimestamp = $rowDate !== '' ? strtotime($rowDate) : false;
                                    $rowDate = $rowTimestamp ? date('M d, Y H:i', $rowTimestamp) : ($rowDate !== '' ? $rowDate : '—');
                                @endphp
                                <tr>
                                    <td>{{ trim((string) ($row['medicine'] ?? '')) !== '' ? $row['medicine'] : '—' }}</td>
                                    <td class="rx-col-qty">{{ trim((string) ($row['quantity'] ?? '')) !== '' ? $row['quantity'] : '—' }}</td>
                                    <td class="rx-col-date">{{ $rowDate }}</td>
                                    <td class="rx-col-action">
                                        <button type="button" class="rx-btn rx-btn-print" data-rx-print>
                                            <i class="bi bi-printer" aria-hidden="true"></i>Print
                                        </button>
                                        <button type="button" class="rx-btn rx-btn-renew" data-rx-renew>
                                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>Renew
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        @endforeach

        @if ($hasLocalPrescriptions)
            <section class="rx-card">
                <header class="rx-card-head">
                    <span class="rx-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-medical"></i></span>
                    <span class="rx-card-title">Portal Prescriptions</span>
                    <span class="rx-card-sub">Prescriptions saved in the patient portal.</span>
                </header>
                <div class="table-responsive rx-card-table">
                    <table class="table rx-table mb-0">
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
    </div>
@endsection

{{-- printRx lives in a shared asset: the dashboard page loads it for the modal
     (fetched content never executes its own scripts); the full page loads it here. --}}
@push('scripts')
    <script src="{{ asset('js/rx-prescriptions.js') }}" defer></script>
@endpush
