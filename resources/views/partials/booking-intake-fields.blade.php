@php
    $selectedSymptoms = collect(old('symptoms', []))
        ->filter(fn ($symptom) => is_string($symptom))
        ->values();
@endphp

<div class="booking-intake" data-booking-intake>
    <fieldset class="booking-intake-section">
        <legend class="booking-step-label">
            <span>1</span>
            <span>
                Ano ang ipapakonsulta? (Pumili ng Isa)
                <small>What would you like to consult about? (Choose one)</small>
            </span>
        </legend>

        <div class="booking-reason-grid">
            @foreach (($consultationReasons ?? []) as $reasonValue => $reasonLabel)
                <label class="booking-choice-card booking-reason-card">
                    <input
                        type="radio"
                        name="consultation_reason"
                        value="{{ $reasonValue }}"
                        @checked(old('consultation_reason') === $reasonValue)
                        data-booking-reason
                        required
                    >
                    <span>{{ $reasonLabel }}</span>
                </label>
            @endforeach
        </div>
        @error('consultation_reason')
            <p class="booking-field-error" role="alert">{{ $message }}</p>
        @enderror
    </fieldset>

    <fieldset class="booking-intake-section">
        <legend class="booking-step-label booking-symptom-heading">
            <span>2</span>
            <span>
                Please select at least 1 and maximum of 3 symptoms.
                <small lang="fil">Pumili ng Isa o hanggang sa Tatlong Sintomas</small>
            </span>
            <strong data-booking-symptom-count aria-live="polite">0 / 3 selected</strong>
        </legend>

        <div class="booking-symptom-grid" data-booking-symptoms>
            @foreach (($symptoms ?? []) as $symptomValue => $symptomLabel)
                <label class="booking-choice-card booking-symptom-card">
                    <input
                        type="checkbox"
                        name="symptoms[]"
                        value="{{ $symptomValue }}"
                        @checked($selectedSymptoms->contains($symptomValue))
                        data-booking-symptom
                    >
                    <span>{{ $symptomLabel }}</span>
                </label>
            @endforeach
        </div>
        @error('symptoms')
            <p class="booking-field-error" role="alert">{{ $message }}</p>
        @enderror
        @error('symptoms.*')
            <p class="booking-field-error" role="alert">{{ $message }}</p>
        @enderror
    </fieldset>

    <fieldset class="booking-intake-section">
        <legend class="booking-step-label">
            <span>3</span>
            <span>
                Details about your Complaint
                <small lang="fil">Magbigay ng konting detalye ukol sa inyong karamdaman</small>
            </span>
        </legend>

        <label class="visually-hidden" for="bookingComplaintDetails">Details about your complaint</label>
        <textarea
            class="booking-complaint-input"
            id="bookingComplaintDetails"
            name="complaint_details"
            rows="5"
            maxlength="2000"
            required
            placeholder="Enter here..."
            data-booking-complaint-details
        >{{ old('complaint_details') }}</textarea>
        <div class="booking-character-count">
            <span>Keep the details clear and medically relevant.</span>
            <span data-booking-detail-count>0 / 2000</span>
        </div>
        @error('complaint_details')
            <p class="booking-field-error" role="alert">{{ $message }}</p>
        @enderror
    </fieldset>

    <div class="booking-intake-error" data-booking-intake-error hidden role="alert"></div>
</div>
