{{--
    Shared form body for the Add Service and Edit Service modals.

    Both modals submit the same field names, so both render from this one
    partial. That is what keeps the Face to Face / MFAM and Telemedicine edit
    modals on a single consistent design: they are the same page template, and
    the add and edit forms are the same markup.

    Expected scope:
      $config      the per-type configuration from the controller
      $weekDays    Mon..Sun, for the day picker
      $prefix      'addService' | 'editService' — namespaces element ids
      $formId      id attribute for the <form>
      $formAction  action attribute for the <form>
      $submitLabel primary button caption
      $defaultDays days pre-selected on first paint (add only; edit is filled
                   by script from the stored row)
      $slotRows    initial timeslot rows (add only; edit is filled by script)
--}}

{{--
    `admin-doctor-form` is carried so the fields inherit the established modal
    typography and control styling instead of restating it here.
--}}
<div class="admin-service-form admin-doctor-form">
    {{-- Tells the redirect-back which modal had the validation failure. --}}
    <input type="hidden" name="_form" value="{{ $prefix === 'addService' ? 'add' : 'edit' }}">

    <section class="admin-service-form-section">
        <header class="admin-service-form-section-head">
            <span class="admin-service-form-section-icon"><i class="bi {{ $config['icon'] }}" aria-hidden="true"></i></span>
            <div class="admin-service-form-section-copy">
                <h3>Service details</h3>
                <p>How this {{ strtolower($config['label']) }} service is named and which days it can be booked.</p>
            </div>
        </header>

        <div class="admin-service-form-body">
            <div class="admin-service-field-grid">
                <div class="form-field">
                    <label for="{{ $prefix }}Name">Service Name <span class="admin-field-required">*</span></label>
                    <input type="text" class="form-control" id="{{ $prefix }}Name" name="service_name"
                           value="{{ old('service_name') }}" required maxlength="100"
                           placeholder="e.g. Family Medicine" autocomplete="off">
                    @error('service_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="{{ $prefix }}Homis">HOMIS code <span class="admin-field-optional">optional</span></label>
                    <input type="text" class="form-control" id="{{ $prefix }}Homis" name="homis_code"
                           value="{{ old('homis_code') }}" maxlength="50" placeholder="e.g. MFAM" autocomplete="off">
                    <small class="admin-field-hint">Used by telemedicine booking links.</small>
                    @error('homis_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-field">
                <label id="{{ $prefix }}DaysLabel">Available Days <span class="admin-field-required">*</span></label>
                <div class="admin-service-day-picker" role="group" aria-labelledby="{{ $prefix }}DaysLabel">
                    @foreach ($weekDays as $day)
                        <label class="admin-service-day-option">
                            <input type="checkbox" name="availability_day[]" value="{{ $day }}"
                                   @checked(in_array($day, old('availability_day', $defaultDays), true))>
                            <span>{{ $day }}</span>
                        </label>
                    @endforeach
                </div>
                @error('availability_day') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                @error('availability_day.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
        </div>
    </section>

    <section class="admin-service-form-section">
        <header class="admin-service-form-section-head">
            <span class="admin-service-form-section-icon amber"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
            <div class="admin-service-form-section-copy">
                <h3>Timeslot slot capacities</h3>
                <p>Each row is a bookable window and how many patients it can hold.</p>
            </div>
        </header>

        <div class="admin-service-form-body">
            <div class="admin-service-slot-editor" data-slot-editor>
                <div class="admin-service-slot-editor-head">
                    <span>Time slot</span>
                    <span>Slots</span>
                    <span aria-hidden="true"></span>
                </div>

                <div class="admin-service-slot-editor-rows" data-slot-rows>
                    {{-- Add renders its rows server side (so a rejected submission
                         comes back filled); edit is populated by script. --}}
                    @foreach ($slotRows as $slot)
                        <div class="admin-service-slot-editor-row" data-slot-row>
                            <input type="text" class="form-control" name="timeslots[{{ $loop->index }}][time_slot]"
                                   value="{{ $slot['time_slot'] ?? '' }}" placeholder="08:00 - 10:00"
                                   required maxlength="50" aria-label="Time slot" autocomplete="off">
                            <input type="number" class="form-control" name="timeslots[{{ $loop->index }}][slots]"
                                   value="{{ $slot['slots'] ?? '' }}" placeholder="6"
                                   required min="0" max="999" step="1" aria-label="Slot capacity">
                            <button type="button" class="admin-service-slot-remove" data-slot-remove
                                    aria-label="Remove time slot" @disabled($loop->count === 1 && $loop->first)>
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="admin-service-slot-editor-foot">
                    <button type="button" class="admin-secondary-button admin-service-slot-add" data-slot-add>
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Add Timeslot</span>
                    </button>

                    <small class="admin-service-slot-hint" data-slot-summary aria-live="polite"></small>
                </div>
            </div>

            @error('timeslots') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            @error('timeslots.*.time_slot') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            @error('timeslots.*.slots') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </section>
</div>