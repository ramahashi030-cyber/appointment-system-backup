@forelse ($appointments as $appointment)
    @php
        $serviceName = strtoupper((string) $appointment->mode) === 'TELE'
            ? ($appointment->serviceTele?->service_name ?: 'General consultation')
            : ($appointment->service?->service_name ?: 'General consultation');
        $providerName = $appointment->staff
            ? trim((string) $appointment->staff->FirstName.' '.(string) $appointment->staff->LastName)
            : 'Unassigned';
        $status = strtolower((string) ($appointment->status ?: 'completed'));
    @endphp
    <tr>
        <td>
            <div class="admin-doctor-appointment-patient">
                <strong>{{ $serviceName }}</strong>
                <small>{{ $appointment->consultation_reason ?: 'No consultation reason' }}</small>
            </div>
        </td>
        <td>
            <strong>{{ $appointment->date?->format('M j, Y') ?: '—' }}</strong>
            <small>{{ $appointment->time_slot ?: '—' }}</small>
        </td>
        <td>{{ $providerName }}</td>
        <td>
            <span class="admin-status-pill {{ $status }}">{{ ucfirst($status) }}</span>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4">
            <div class="admin-doctor-empty compact">
                <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                <strong>No consultations to show</strong>
                <span>Completed consultations for this patient will appear here.</span>
            </div>
        </td>
    </tr>
@endforelse
