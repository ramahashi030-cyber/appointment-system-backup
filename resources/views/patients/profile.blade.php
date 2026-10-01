{{--
    Patient: profile — view and edit the signed-in patient's own details.
    Renders as a full page normally, or as bare content inside the dashboard modal.

    Expected variables:
      $patientName  string
      $patient      \App\Models\Patient
--}}
@extends(request()->ajax() ? 'layouts.modal' : 'layouts.app')

@section('title', 'My Profile')

@section('content')
    @php $inModal = request()->ajax(); @endphp

    @unless ($inModal)
        <div class="mb-4">
            <h1 class="page-title h3 mb-0">
                <i class="bi bi-person-circle me-2"></i>My Profile
            </h1>
            <p class="page-subtitle mb-0">Your personal and contact details</p>
        </div>
    @endunless

    <div class="card profile-card">
        <div class="card-body p-4">

            <div class="d-flex align-items-center gap-3 mb-4">
                @if ($patient->profile_pic)
                    <img class="profile-avatar"
                         src="{{ \Illuminate\Support\Str::startsWith($patient->profile_pic, ['http://', 'https://']) ? $patient->profile_pic : asset($patient->profile_pic) }}"
                         alt="Profile picture">
                @else
                    <div class="profile-avatar-fallback">
                        <i class="bi bi-person"></i>
                    </div>
                @endif

                <div>
                    <h2 class="h5 fw-bold mb-1">{{ $patientName }}</h2>
                    <p class="text-muted small mb-0">
                        Username: <strong>{{ $patient->username }}</strong>
                        &middot;
                        Status: <span class="badge bg-success rounded-pill">{{ $patient->status }}</span>
                    </p>
                </div>
            </div>

            <form action="{{ route('patient.profile.update') }}" method="POST" enctype="multipart/form-data" data-modal-form>
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="first_name">First name</label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                               id="first_name" name="first_name"
                               value="{{ old('first_name', $patient->first_name) }}" maxlength="100" required>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="middlename">Middle name</label>
                        <input type="text" class="form-control @error('middlename') is-invalid @enderror"
                               id="middlename" name="middlename"
                               value="{{ old('middlename', $patient->middlename) }}" maxlength="100">
                        @error('middlename') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="last_name">Last name</label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                               id="last_name" name="last_name"
                               value="{{ old('last_name', $patient->last_name) }}" maxlength="100" required>
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="gender">Gender</label>
                        <select class="form-select @error('gender') is-invalid @enderror"
                                id="gender" name="gender" required>
                            <option value="">Select gender</option>
                            <option value="Male" @selected(old('gender', $patient->gender) === 'Male')>Male</option>
                            <option value="Female" @selected(old('gender', $patient->gender) === 'Female')>Female</option>
                        </select>
                        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="dob">Birthdate</label>
                        <input type="date" class="form-control @error('dob') is-invalid @enderror"
                               id="dob" name="dob"
                               value="{{ old('dob', $patient->dob?->format('Y-m-d')) }}">
                        @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="hospital_number">Hospital number</label>
                        <input type="text" class="form-control @error('hospital_number') is-invalid @enderror"
                               id="hospital_number" name="hospital_number"
                               value="{{ old('hospital_number', $patient->hospital_number) }}" maxlength="20">
                        @error('hospital_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="contact_number">Contact number</label>
                        <input type="text" class="form-control @error('contact_number') is-invalid @enderror"
                               id="contact_number" name="contact_number"
                               value="{{ old('contact_number', $patient->contact_number) }}"
                               inputmode="numeric" maxlength="11" required>
                        @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email"
                               value="{{ old('email', $patient->email) }}" maxlength="100">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="address">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror"
                                  id="address" name="address" rows="2"
                                  maxlength="1000">{{ old('address', $patient->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="profile_pic">Profile picture</label>
                        <input type="file" class="form-control @error('profile_pic') is-invalid @enderror"
                               id="profile_pic" name="profile_pic" accept="image/*">
                        <div class="form-text">JPG, PNG or WebP, up to 2 MB. Leave empty to keep the current picture.</div>
                        @error('profile_pic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-pill px-4">
                        <i class="bi bi-check2 me-1"></i>Save changes
                    </button>
                    @if ($inModal)
                        <button type="button" class="btn btn-outline-secondary btn-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    @else
                        <a href="/telemed" class="btn btn-outline-secondary btn-pill px-4">Cancel</a>
                    @endif
                </div>
            </form>

        </div>
    </div>
@endsection