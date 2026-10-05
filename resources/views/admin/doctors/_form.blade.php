@php
    $availability = old('availability', $doctor?->availability ?? []);
    $selectedDays = old('availability_days', $availability['days'] ?? []);
    $selectedDays = is_array($selectedDays) ? $selectedDays : [];
    $days = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];
    $formAction = $formAction ?? ($doctor ? route('admin.doctors.update', $doctor) : route('admin.doctors.store'));
    $formMethod = $formMethod ?? ($doctor ? 'PUT' : 'POST');
    $submitLabel = $submitLabel ?? ($doctor ? 'Save changes' : 'Add doctor');
    // Mirrors DoctorRequest::NAME_PATTERN (letters and the spaces between
    // them) so the browser and the server agree on what a name is.
    $namePattern = '[A-Za-z]+( +[A-Za-z]+)*';
    // Employee ID: digits with optional space or hyphen separators.
    $employeeIdPattern = '[0-9]+([ -]?[0-9]+)*';
    // Contact number: digits only.
    $contactPattern = '[0-9]+';
    // Mirrors the password rules in DoctorRequest.
    $passwordMinLength = \App\Http\Requests\Admin\DoctorRequest::PASSWORD_MIN;
    $passwordMaxLength = \App\Http\Requests\Admin\DoctorRequest::PASSWORD_MAX;
    $passwordSymbolThreshold = \App\Http\Requests\Admin\DoctorRequest::PASSWORD_SYMBOL_THRESHOLD;
    $passwordHint = $passwordMinLength.' to '.$passwordMaxLength
        .' characters, with uppercase and lowercase letters and a number'
        .' (over '.$passwordSymbolThreshold.' characters also needs a special character).';
@endphp

<form class="admin-doctor-form" method="POST" action="{{ $formAction }}">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <section class="admin-panel" aria-labelledby="doctorIdentityTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-person-vcard" aria-hidden="true"></i>
                <h2 id="doctorIdentityTitle">Identity &amp; access</h2>
            </div>
        </header>
        <div class="admin-doctor-form-grid">
            <div class="form-field">
                <label for="firstname">First name</label>
                <input class="form-control @error('firstname') is-invalid @enderror" id="firstname" name="firstname" value="{{ old('firstname', $doctor?->FirstName) }}" required maxlength="100" @if (! $doctor) pattern="{{ $namePattern }}" data-letters-only @endif>
                @error('firstname') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="middlename">Middle name <span>(optional)</span></label>
                <input class="form-control @error('middlename') is-invalid @enderror" id="middlename" name="middlename" value="{{ old('middlename', $doctor?->MiddleName) }}" maxlength="100" @if (! $doctor) pattern="{{ $namePattern }}" data-letters-only @endif>
                @error('middlename') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="lastname">Last name</label>
                <input class="form-control @error('lastname') is-invalid @enderror" id="lastname" name="lastname" value="{{ old('lastname', $doctor?->LastName) }}" required maxlength="100" @if (! $doctor) pattern="{{ $namePattern }}" data-letters-only @endif>
                @error('lastname') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="username">Username</label>
                <input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $doctor?->username) }}" required maxlength="50" autocomplete="off">
                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="employee_id">Employee ID <span>(optional)</span></label>
                <input class="form-control @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" value="{{ old('employee_id', $doctor?->employee_id) }}" maxlength="10" @if (! $doctor) pattern="{{ $employeeIdPattern }}" inputmode="numeric" data-digits-only @endif>
                @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="legacy_doctor_id">Legacy doctor ID <span>(optional)</span></label>
                <input class="form-control @error('legacy_doctor_id') is-invalid @enderror" id="legacy_doctor_id" name="legacy_doctor_id" type="number" min="1" step="1" value="{{ old('legacy_doctor_id', $doctor?->legacy_doctor_id) }}">
                @error('legacy_doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="email">Email</label>
                <input class="form-control @error('email') is-invalid @endif" id="email" name="email" type="email" value="{{ old('email', $doctor?->email) }}" maxlength="191" {{ $doctor ? '' : 'required' }}>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="contactno">Contact number</label>
                <input class="form-control @error('contactno') is-invalid @enderror" id="contactno" name="contactno" value="{{ old('contactno', $doctor?->contactno) }}" required maxlength="11" @if (! $doctor) pattern="{{ $contactPattern }}" inputmode="numeric" data-digits-only @endif>
                @error('contactno') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="site">Primary site</label>
                <select class="form-select @error('site') is-invalid @enderror" id="site" name="site">
                    <option value="">Any site</option>
                    @foreach (['TELE' => 'Telemedicine', 'FACE' => 'Face-to-face', 'BOTH' => 'Both'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('site', $doctor?->site) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('site') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </section>

    @if ($doctor)
        <section class="admin-panel" aria-labelledby="doctorPracticeTitle">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-briefcase-medical" aria-hidden="true"></i>
                    <h2 id="doctorPracticeTitle">Practice details</h2>
                </div>
            </header>
            <div class="admin-doctor-form-grid">
                <div class="form-field">
                    <label for="consultation_type">Consultation type</label>
                    <input class="form-control @error('consultation_type') is-invalid @enderror" id="consultation_type" name="consultation_type" value="{{ old('consultation_type', $doctor?->consultation_type) }}" required maxlength="100" placeholder="e.g. Initial consultation">
                    @error('consultation_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>
    @endif

    <section class="admin-panel" aria-labelledby="doctorScheduleTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-calendar2-week" aria-hidden="true"></i>
                <h2 id="doctorScheduleTitle">Availability &amp; schedule</h2>
            </div>
        </header>
        <div class="admin-doctor-schedule-fields">
            <div class="form-field">
                <label>Available days</label>
                <div class="admin-doctor-day-grid">
                    @foreach ($days as $value => $label)
                        <label class="admin-doctor-day-option">
                            <input type="checkbox" name="availability_days[]" value="{{ $value }}" @checked(in_array($value, $selectedDays, true))>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('availability_days.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="admin-doctor-time-grid">
                <div class="form-field">
                    <label for="shift_start">Shift starts</label>
                    <input class="form-control @error('shift_start') is-invalid @enderror" id="shift_start" name="shift_start" type="time" value="{{ old('shift_start', $availability['start'] ?? '08:00') }}">
                    @error('shift_start') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="shift_end">Shift ends</label>
                    <input class="form-control @error('shift_end') is-invalid @enderror" id="shift_end" name="shift_end" type="time" value="{{ old('shift_end', $availability['end'] ?? '17:00') }}">
                    @error('shift_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </section>

    <section class="admin-panel" aria-labelledby="doctorSecurityTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <h2 id="doctorSecurityTitle">Account status &amp; security</h2>
            </div>
        </header>
        <div class="admin-doctor-form-grid">
            <div class="form-field">
                <label for="password">Password {{ $doctor ? '(leave blank to keep current)' : '' }}</label>
                <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" {{ $doctor ? '' : 'required' }} @if ($doctor) minlength="8" @else minlength="{{ $passwordMinLength }}" maxlength="{{ $passwordMaxLength }}" @endif autocomplete="new-password">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @unless ($doctor)
                    <div class="form-text">{{ $passwordHint }}</div>
                @endunless
            </div>
            <div class="form-field">
                <label for="password_confirmation">Confirm password</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" {{ $doctor ? '' : 'required' }} @if ($doctor) minlength="8" @else minlength="{{ $passwordMinLength }}" maxlength="{{ $passwordMaxLength }}" @endif autocomplete="new-password">
            </div>
            <div class="form-field admin-doctor-active-field">
                <label for="is_active">Availability status</label>
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $doctor?->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Active for appointment assignment</label>
                </div>
            </div>
        </div>
    </section>

    <div class="admin-doctor-form-actions">
        @isset($cancelUrl)
            <a class="admin-secondary-button" href="{{ $cancelUrl }}">Cancel</a>
        @endisset
        <button class="admin-primary-button {{ $doctor ? '' : 'admin-doctor-add-button' }}" type="submit">
            <i class="bi bi-check2" aria-hidden="true"></i>
            {{ $submitLabel }}
        </button>
    </div>
</form>
