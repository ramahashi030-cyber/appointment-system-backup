@php
    $reqId = $request['id'] ?? 0;
    $reqStatus = $request['triager_status'] ?? 'Pending';
    $reqStatusClass = strtolower(str_replace(' ', '-', $reqStatus));
    $reqAction = $request['triager_action'] ?? '';
    $reqRemarks = $request['triager_remarks'] ?? '';
    $reqExisting = $request['existing_appointments'] ?? [];
    $locked = $request['is_processed_by_other'] ?? false;
    $canEdit = ($request['can_edit'] ?? false) && ! $locked;
    $canSchedule = ($request['can_schedule'] ?? false) && ! $locked;
    $requestMode = strtoupper($request['request_mode'] ?? ($mode ?? 'TELE'));
@endphp

<article class="triager-card {{ $locked ? 'triager-card-locked' : '' }}" data-request-card="{{ $reqId }}">
    <header class="triager-card-header">
        <span class="triager-card-status {{ $reqStatusClass }}">
            @if ($reqStatus === 'Pending')
                <span class="status-dot" aria-hidden="true"></span>
            @endif
            @if ($reqStatus === 'Processing' && ($request['is_owner'] ?? false))
                You are Processing
            @elseif ($reqStatus === 'Approved' && ($request['is_owner'] ?? false))
                Approved — ready to schedule
            @elseif ($reqStatus === 'In Progress')
                In Progress
            @elseif ($reqStatus === 'Pending' && ! $locked)
                <button type="button" class="triager-start-btn" data-start-processing="{{ $reqId }}">
                    Start Processing
                </button>
            @elseif ($locked)
                <span class="triager-locked-badge">Being Processed</span>
            @else
                {{ $reqStatus }}
            @endif
        </span>
        <span class="triager-card-pending-badge">{{ $reqStatus }}</span>
    </header>

    <div class="triager-card-body">
        <h3 class="triager-patient-name">{{ $request['patient_name'] }}</h3>
        <p class="triager-patient-meta">
            <strong>Hospital No:</strong> {{ $request['hospital_number'] }}
        </p>

        <div class="triager-symptoms">
            <strong>Symptoms and Complaint Details:</strong>
            @if (! empty($request['symptoms_text']))
                {{ $request['symptoms_text'] }}
            @endif
            @if (! empty($request['complaint_details']) && $requestMode === 'TELE')
                @if (! empty($request['symptoms_text']))
                    |
                @endif
                {{ $request['complaint_details'] }}
            @endif
            @if (! empty($request['consultation_reason_label']) && $requestMode === 'FACE')
                {{ $request['consultation_reason_label'] }}
            @endif
        </div>

        <p class="triager-timestamp">
            {{ $request['requested_at']?->format('Y-m-d H:i:s') ?? '—' }}
        </p>

        @if ($locked)
            <p class="triager-locked-note">Another triager is processing this request. You cannot make changes.</p>
        @else
            <form class="triager-card-actions" action="{{ route('triager.requests.update', $reqId) }}" method="POST">
                @csrf
                <select name="triager_action" class="triager-select-action" required data-triager-action @disabled(! $canEdit && $reqStatus !== 'Pending')>
                    <option value="">Select Action</option>
                    @foreach ($triageActions as $action)
                        <option value="{{ $action }}" @selected($reqAction === $action)>
                            {{ $action }}
                        </option>
                    @endforeach
                </select>

                <textarea
                    name="triager_remarks"
                    class="triager-remarks"
                    placeholder="Remarks..."
                    maxlength="2000"
                    data-triager-remarks
                    @disabled(! $canEdit && $reqStatus !== 'Pending')
                >{{ $reqRemarks }}</textarea>
            </form>
        @endif

        @if (! empty($reqExisting))
            <div class="triager-appointments">
                <strong>Appointments:</strong>
                @foreach ($reqExisting as $existing)
                    <div class="triager-appointment-item">
                        {{ $existing['date'] }} - {{ $existing['time_slot'] }} -
                        @if (($existing['mode'] ?? '') === 'TELE')
                            Telemedicine
                        @else
                            Face-to-Face
                        @endif
                        Consultation <strong>{{ $existing['consultation'] }}</strong>
                    </div>
                @endforeach
            </div>
        @endif

        @if (! $locked)
            <div class="triager-schedule-buttons">
                @if ($requestMode === 'TELE')
                    <button
                        type="button"
                        class="triager-schedule-btn telemed {{ $canSchedule ? '' : 'disabled' }}"
                        data-schedule-telemed="{{ $reqId }}"
                        data-patient-name="{{ $request['patient_name'] }}"
                        @disabled(! $canSchedule)
                    >
                        <i class="bi bi-camera-video" aria-hidden="true"></i>
                        Add Telemed Schedule
                    </button>
                @endif
                @if ($requestMode === 'FACE')
                    <button
                        type="button"
                        class="triager-schedule-btn face {{ $canSchedule ? '' : 'disabled' }}"
                        data-schedule-face="{{ $reqId }}"
                        data-patient-name="{{ $request['patient_name'] }}"
                        @disabled(! $canSchedule)
                    >
                        <i class="bi bi-hospital" aria-hidden="true"></i>
                        Add Face-to-Face Schedule
                    </button>
                @endif
            </div>

            <button
                type="button"
                class="triager-save-btn {{ ($canEdit || ($reqStatus === 'Processing' && ($request['is_owner'] ?? false))) ? 'active' : 'disabled' }}"
                data-save-request="{{ $reqId }}"
                @if (! $canEdit && ! ($reqStatus === 'Processing' && ($request['is_owner'] ?? false))) disabled @endif
            >
                Save Update
            </button>
        @endif
    </div>
</article>
