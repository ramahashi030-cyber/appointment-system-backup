@php
    $showDate = $showDate ?? false;
    $emptyMessage = $emptyMessage ?? 'No appointments found.';
@endphp

<div class="doctor-table-wrap">
    <table class="doctor-table">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Service</th>
                @if ($showDate)
                    <th>Date</th>
                @endif
                <th>Time</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody data-doctor-filter-list>
            @forelse ($appointments as $appointment)
                @php
                    $statusClass = strtolower((string) ($appointment['status_class'] ?? 'booked'));
                    $statusLabel = $appointment['status_label'] ?? ucfirst((string) ($appointment['status'] ?? 'Booked'));
                    $profilePic = $appointment['profile_pic'] ?? null;
                    $profileUrl = $profilePic
                        ? (\Illuminate\Support\Str::startsWith($profilePic, ['http://', 'https://']) ? $profilePic : asset($profilePic))
                        : null;
                @endphp
                <tr
                    data-doctor-filter-item
                    data-search-text="{{ \Illuminate\Support\Str::lower(implode(' ', [$appointment['patient_name'] ?? '', $appointment['service_name'] ?? '', $appointment['hospital_number'] ?? '', $appointment['consultation_reason_label'] ?? '', $appointment['status_label'] ?? ''])) }}"
                    data-doctor-appointment-row
                    data-appointment-id="{{ $appointment['id'] ?? '' }}"
                    data-room-status-url="{{ ! empty($appointment['id']) ? route('doctor.appointments.room', $appointment['id']) : '' }}"
                >
                    <td>
                        <div class="doctor-patient-cell">
                            <span class="doctor-patient-avatar">
                                @if ($profileUrl)
                                    <img src="{{ $profileUrl }}" alt="{{ $appointment['patient_name'] ?? 'Patient' }}">
                                @else
                                    {{ $appointment['patient_initials'] ?? '?' }}
                                @endif
                            </span>
                            <span class="doctor-patient-copy">
                                <strong>{{ $appointment['patient_name'] ?? 'Unknown patient' }}</strong>
                                <small>{{ $appointment['hospital_number'] ?: 'No hospital number' }}</small>
                            </span>
                        </div>
                    </td>
                    <td>{{ $appointment['service_name'] ?? 'Telemedicine consultation' }}</td>
                    @if ($showDate)
                        <td>{{ $appointment['date_display'] ?? '—' }}</td>
                    @endif
                    <td>{{ $appointment['time_display'] ?? $appointment['time_slot'] ?? '—' }}</td>
                    <td><span class="doctor-status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td>{{ $appointment['consultation_reason_label'] ?? 'Not recorded' }}</td>
                    <td>
                        <div class="doctor-table-actions">
                            <button
                                type="button"
                                class="doctor-action-button"
                                data-doctor-appointment-view
                                data-patient="{{ $appointment['patient_name'] ?? 'Unknown patient' }}"
                                data-service="{{ $appointment['service_name'] ?? 'Telemedicine consultation' }}"
                                data-date="{{ $appointment['date_display'] ?? '—' }}"
                                data-time="{{ $appointment['time_display'] ?? $appointment['time_slot'] ?? '—' }}"
                                data-status="{{ $statusLabel }}"
                                data-reason="{{ $appointment['consultation_reason_label'] ?? 'Not recorded' }}"
                                data-symptoms="{{ ! empty($appointment['symptom_labels']) ? implode(', ', $appointment['symptom_labels']) : 'No symptoms recorded.' }}"
                                data-details="{{ $appointment['complaint_details'] ?: 'No complaint details recorded.' }}"
                                data-meeting-link="{{ $appointment['meeting_link'] ?? '' }}"
                            >
                                View
                            </button>
                            @if ($appointment['can_open_room'] ?? false)
                                <form method="POST" action="{{ route('doctor.appointments.open-room', $appointment['id']) }}" class="d-inline" data-doctor-create-room-form>
                                    @csrf
                                    <button type="submit" class="doctor-action-button primary" data-doctor-room-action="create">
                                        <i class="bi bi-camera-video-fill" aria-hidden="true"></i>Create Jitsi Room
                                    </button>
                                </form>
                            @elseif ($appointment['can_join'] ?? false)
                                <a href="{{ $appointment['meeting_link'] }}" target="_blank" rel="noopener" class="doctor-action-button primary" data-doctor-room-action="join">
                                    <i class="bi bi-camera-video-fill" aria-hidden="true"></i>Join Jitsi
                                </a>
                            @else
                                <span class="doctor-action-button disabled" aria-disabled="true" data-doctor-room-action="unavailable">Unavailable</span>
                            @endif
                            <p class="doctor-room-notice small text-muted mb-0 mt-1" data-doctor-room-notice hidden>{{ $appointment['room_peer_notice'] ?? '' }}</p>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showDate ? 7 : 6 }}" class="doctor-empty-row">{{ $emptyMessage }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
