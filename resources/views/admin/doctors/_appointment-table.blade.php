@forelse ($appointments as $appointment)
    @php
        $patientName = $appointment->patient
            ? trim(implode(' ', array_filter([
                $appointment->patient->first_name,
                $appointment->patient->middlename,
                $appointment->patient->last_name,
            ])))
            : 'Unknown patient';
        $serviceName = strtoupper((string) $appointment->mode) === 'TELE'
            ? ($appointment->serviceTele?->service_name ?: 'General consultation')
            : ($appointment->service?->service_name ?: 'General consultation');
        $status = strtolower((string) ($appointment->status ?: 'booked'));
    @endphp
    <tr>
        <td>
            <div class="admin-doctor-appointment-patient">
                <strong>{{ $patientName }}</strong>
                <small>{{ $appointment->consultation_reason ?: 'No consultation reason' }}</small>
            </div>
        </td>
        <td>{{ $serviceName }}</td>
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
        <td colspan="5">
            <div class="admin-doctor-empty compact">
                <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                <strong>No appointments to show</strong>
                <span>Appointments linked to this provider will appear here.</span>
            </div>
        </td>
    </tr>
@endforelse
