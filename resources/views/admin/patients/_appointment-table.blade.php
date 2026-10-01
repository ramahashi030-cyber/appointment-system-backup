@forelse ($appointments as $appointment)
    @php
        $serviceName = strtoupper((string) $appointment->mode) === 'TELE'
            ? ($appointment->serviceTele?->service_name ?: 'General consultation')
            : ($appointment->service?->service_name ?: 'General consultation');
        $status = strtolower((string) ($appointment->status ?: 'booked'));
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
        <td>{{ $appointment->mode ?: '—' }}</td>
        <td>
            <span class="admin-status-pill {{ $status }}">{{ ucfirst($status) }}</span>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4">
            <div class="admin-doctor-empty compact">
                <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                <strong>No appointments to show</strong>
                <span>Appointments booked by this patient will appear here.</span>
            </div>
        </td>
    </tr>
@endforelse
