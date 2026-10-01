<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Processed Requests — {{ $printedAt->format('M j, Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite('resources/css/admin/triager-processed.css')
</head>
<body>
    <h1>Processed Appointment Requests</h1>
    <p class="subtitle">{{ $printedAt->format('F j, Y') }} &middot; QMMC Triager Dashboard</p>

    @if (empty($processedToday))
        <p>No processed requests today.</p>
    @else
        <table>
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
                        <td>{{ $item['patient_name'] }}</td>
                        <td>{{ $item['hospital_number'] }}</td>
                        <td>{{ $item['id'] ? 'Face-to-Face' : 'Telemedicine' }}</td>
                        <td>
                            <span class="badge badge-{{ strtolower(str_replace(' ', '-', $item['triager_action'] ?? 'default')) }}">
                                {{ $item['triager_action'] ?? 'Processed' }}
                            </span>
                        </td>
                        <td>{{ $item['requested_at']?->format('h:i A') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footer">Printed on {{ $printedAt->format('M j, Y h:i A') }}</p>

    <button class="btn btn-primary no-print" onclick="window.print()">Print</button>
</body>
</html>
