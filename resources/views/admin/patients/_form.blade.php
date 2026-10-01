@php
    $formAction = $formAction ?? route('admin.patients.update', $patient);
    $formMethod = $formMethod ?? 'PUT';
    $submitLabel = $submitLabel ?? 'Save changes';
@endphp

<form class="admin-doctor-form" method="POST" action="{{ $formAction }}">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <section class="admin-panel" aria-labelledby="patientIdentityTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-person-vcard" aria-hidden="true"></i>
                <h2 id="patientIdentityTitle">Identity &amp; access</h2>
            </div>
        </header>
        <div class="admin-doctor-form-grid">
            <div class="form-field">
                <label for="firstname">First name</label>
                <input class="form-control @error('firstname') is-invalid @enderror" id="firstname" name="firstname" value="{{ old('firstname', $patient?->first_name) }}" required maxlength="100">
                @error('firstname') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="middlename">Middle name <span>(optional)</span></label>
                <input class="form-control @error('middlename') is-invalid @endif" id="middlename" name="middlename" value="{{ old('middlename', $patient?->middlename) }}" maxlength="100">
                @error('middlename') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="lastname">Last name</label>
                <input class="form-control @error('lastname') is-invalid @endif" id="lastname" name="lastname" value="{{ old('lastname', $patient?->last_name) }}" required maxlength="100">
                @error('lastname') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-field">
                <label for="username">Username</label>
                <input class="form-control @error('username') is-invalid @endif" id="username" name="username" value="{{ old('username', $patient?->username) }}" required maxlength="50" autocomplete="off">
                @error('username') <div class="invalid-feedback">{{ $message }}</div> @endif
            </div>
            <div class="form-field">
                <label for="password">New password <span>(optional)</span></label>
                <input class="form-control @error('password') is-invalid @endif" id="password" name="password" type="password" minlength="6" maxlength="60" autocomplete="new-password">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @endif
            </div>
            <div class="form-field">
                <label for="password_confirmation">Confirm password</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            </div>
        </div>
    </section>

    <section class="admin-panel" aria-labelledby="patientContactTitle">
        <header class="admin-panel-header">
            <div class="admin-panel-title">
                <i class="bi bi-telephone" aria-hidden="true"></i>
                <h2 id="patientContactTitle">Contact &amp; demographics</h2>
            </div>
        </header>
        <div class="admin-doctor-form-grid">
            <div class="form-field">
                <label for="email">Email <span>(optional)</span></label>
                <input class="form-control @error('email') is-invalid @endif" id="email" name="email" type="email" value="{{ old('email', $patient?->email) }}" maxlength="191">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @endif
            </div>
            <div class="form-field">
                <label for="contactno">Contact number <span>(optional)</span></label>
                <input class="form-control @error('contactno') is-invalid @endif" id="contactno" name="contactno" value="{{ old('contactno', $patient?->contact_number) }}" maxlength="11" inputmode="tel">
                @error('contactno') <div class="invalid-feedback">{{ $message }}</div> @endif
            </div>
            <div class="form-field">
                <label for="dob">Date of birth <span>(optional)</span></label>
                <input class="form-control @error('dob') is-invalid @endif" id="dob" name="dob" type="date" value="{{ old('dob', $patient?->dob?->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
                @error('dob') <div class="invalid-feedback">{{ $message }}</div> @endif
            </div>
            <div class="form-field">
                <label for="gender">Gender <span>(optional)</span></label>
                <select class="form-select @error('gender') is-invalid @endif" id="gender" name="gender">
                    <option value="">Not specified</option>
                    <option value="Male" @selected(old('gender', $patient?->gender) === 'Male')>Male</option>
                    <option value="Female" @selected(old('gender', $patient?->gender) === 'Female')>Female</option>
                </select>
                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @endif
            </div>
            <div class="form-field">
                <label for="hospital_number">Hospital number <span>(optional)</span></label>
                <input class="form-control @error('hospital_number') is-invalid @endif" id="hospital_number" name="hospital_number" value="{{ old('hospital_number', $patient?->hospital_number) }}" maxlength="20" inputmode="numeric">
                @error('hospital_number') <div class="invalid-feedback">{{ $message }}</div> @endif
            </div>
            <div class="form-field">
                <label for="address">Address <span>(optional)</span></label>
                <textarea class="form-control @error('address') is-invalid @endif" id="address" name="address" rows="2" maxlength="500">{{ old('address', $patient?->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @endif
            </div>
            <div class="form-field">
                <label for="status">Account status</label>
                <select class="form-select @error('status') is-invalid @endif" id="status" name="status">
                    @foreach (['Active' => 'Active', 'Pending' => 'Pending (deactivated)'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $patient?->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @endif
            </div>
        </div>
    </section>

    <div class="admin-doctor-form-actions">
        <button class="admin-primary-button" type="submit">{{ $submitLabel }}</button>
    </div>
</form>
