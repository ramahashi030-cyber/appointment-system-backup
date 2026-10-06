<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Processed Requests — {{ $printedAt->format('M j, Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite('resources/css/admin/triager-processed.css')
    <style>
        /* Responsive table: desktop natural width, tablet horizontal scroll, phone stacked cards */
        .admin-processed-table {
            width: 100%;
            min-width: 0;
            table-layout: auto;
            border-collapse: collapse;
        }

        .admin-processed-table th,
        .admin-processed-table td {
            padding: 12px 14px;
            border: 1px solid #dbe8f7;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .admin-processed-table thead {
            background: #f4f8ff;
            color: #0a326c;
            font-weight: 600;
        }

        .admin-processed-table tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        .admin-processed-table .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .admin-processed-table .badge-approved,
        .admin-processed-table .badge-scheduled {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .admin-processed-table .badge-rejected,
        .admin-processed-table .badge-cancelled {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .admin-processed-table .badge-processed {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .admin-processed-table .badge-default {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        /* Tablet: horizontal scroll instead of breaking layout */
        @media (min-width: 768px) and (max-width: 1199.98px) {
            .admin-processed-table-wrap {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }
            .admin-processed-table-wrap .admin-processed-table {
                min-width: 680px !important;
            }
        }

        /* Phone: stacked cards */
        @media (max-width: 767.98px) {
            .admin-processed-table-wrap {
                overflow-x: visible;
            }
            .admin-processed-table,
            .admin-processed-table tbody {
                display: block;
                width: 100%;
                min-width: 0;
            }
            .admin-processed-table thead {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0 0 0 0);
                white-space: nowrap;
            }
            .admin-processed-table tbody tr {
                display: block;
                margin-bottom: 12px;
                padding: 14px;
                border: 1px solid #d7e6f7;
                border-radius: 12px;
                background: #fff;
            }
            .admin-processed-table tbody td {
                display: block;
                min-width: 0;
                padding: 0;
                border: 0;
                text-align: left;
                white-space: normal;
                overflow-wrap: anywhere;
            }
            .admin-processed-table tbody td[data-label]::before {
                display: block;
                margin-bottom: 3px;
                color: #6a83a4;
                content: attr(data-label);
                font-size: 10px;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }
            .admin-processed-table .badge {
                align-self: flex-start;
            }
        }

        /* Print: show full table, no card layout */
        @media print {
            .admin-processed-table-wrap {
                overflow: visible !important;
            }
            .admin-processed-table thead {
                position: static !important;
                width: auto !important;
                height: auto !important;
                overflow: visible !important;
                clip: auto !important;
            }
            .admin-processed-table tbody tr {
                display: table-row !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                background: transparent !important;
            }
            .admin-processed-table tbody td {
                display: table-cell !important;
                padding: 12px 14px !important;
                border: 1px solid #dbe8f7 !important;
            }
            .admin-processed-table tbody td[data-label]::before {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <h1>Processed Appointment Requests</h1>
    <p class="subtitle">{{ $printedAt->format('F j, Y') }} &middot; QMMC Triager Dashboard</p>

    @if (empty($processedToday))
        <p>No processed requests today.</p>
    @else
        <div class="admin-processed-table-wrap">
            <table class="admin-processed-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Hospital No</th>
                        <th>Mode</th>
                        <th>Action</th>
                        <th>Processed At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($processedToday as $item)
                        <tr>
                            <td data-label="Patient">{{ $item['patient_name'] }}</td>
                            <td data-label="Hospital No">{{ $item['hospital_number'] }}</td>
                            <td data-label="Mode">{{ $item['id'] ? 'Face-to-Face' : 'Telemedicine' }}</td>
                            <td data-label="Action">
                                <span class="badge badge-{{ strtolower(str_replace(' ', '-', $item['triager_action'] ?? 'default')) }}">
                                    {{ $item['triager_action'] ?? 'Processed' }}
                                </span>
                            </td>
                            <td data-label="Processed At">{{ $item['requested_at']?->format('h:i A') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p class="footer">Printed on {{ $printedAt->format('M j, Y h:i A') }}</p>

    <button class="btn btn-primary no-print" onclick="window.print()">Print</button>
</body>
</html>
