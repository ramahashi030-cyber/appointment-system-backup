{{-- Telemed schedule modal --}}
<div class="modal fade" id="telemedScheduleModal" tabindex="-1" aria-labelledby="telemedScheduleTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="telemedScheduleTitle">Add Telemed Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="telemedScheduleForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3" data-telemed-patient-label></p>

                    <div class="mb-3">
                        <label for="telemedService" class="form-label">Telemedicine Service</label>
                        <select class="form-select" id="telemedService" name="service_id" required>
                            <option value="">Select a service</option>
                            @foreach ($teleServices as $service)
                                <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="telemedDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="telemedDate" name="date" required min="{{ date('Y-m-d') }}">
                    </div>

                    <div class="mb-3">
                        <label for="telemedTimeSlot" class="form-label">Time Slot</label>
                        <select class="form-select" id="telemedTimeSlot" name="time_slot" required>
                            <option value="">Select a time slot</option>
                        </select>
                    </div>

                    <div class="alert alert-danger" data-telemed-error hidden role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Face-to-face schedule modal --}}
<div class="modal fade" id="faceScheduleModal" tabindex="-1" aria-labelledby="faceScheduleTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="faceScheduleTitle">Add Face-to-Face Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="faceScheduleForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3" data-face-patient-label></p>

                    <div class="mb-3">
                        <label for="faceService" class="form-label">Service</label>
                        <select class="form-select" id="faceService" name="service_id" required>
                            <option value="">Select a service</option>
                            @foreach ($faceServices as $service)
                                <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="faceDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="faceDate" name="date" required min="{{ date('Y-m-d') }}">
                    </div>

                    <div class="mb-3">
                        <label for="faceTimeSlot" class="form-label">Time Slot</label>
                        <select class="form-select" id="faceTimeSlot" name="time_slot" required>
                            <option value="">Select a time slot</option>
                        </select>
                    </div>

                    <div class="alert alert-danger" data-face-error hidden role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
