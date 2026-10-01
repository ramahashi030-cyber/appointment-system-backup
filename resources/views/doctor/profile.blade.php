@extends('doctor.layout')

@section('title', 'My Profile')

@section('content')
    <div class="doctor-page-header">
        <div>
            <h1>Doctor Profile</h1>
            <p>Account details used by the dedicated QMMC doctor portal.</p>
        </div>
        <div class="doctor-page-header-actions">
            <button type="button" class="doctor-action-button primary" data-bs-toggle="modal" data-bs-target="#doctorProfileModal">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>Edit profile
            </button>
            <a href="{{ route('doctor.dashboard') }}" class="doctor-action-button">
                <i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard
            </a>
        </div>
    </div>

    <div class="doctor-profile-grid">
        <section class="doctor-profile-card text-center">
            <span class="doctor-profile-avatar">
                @if ($doctor['profile_pic'])
                    <img src="{{ $doctor['profile_pic'] }}" alt="{{ $doctor['name'] }}">
                @else
                    {{ $doctor['initials'] }}
                @endif
            </span>
            <h2>{{ $doctor['name'] }}</h2>
            <p>{{ $doctor['specialty'] }}</p>
            <div class="doctor-status mt-3">Active doctor account</div>
        </section>

        <section class="doctor-profile-details">
            <div class="doctor-detail-list">
                <div class="doctor-detail-row"><span>Full name</span><strong>{{ $doctor['name'] }}</strong></div>
                <div class="doctor-detail-row"><span>Specialty</span><strong>{{ $doctor['specialty'] }}</strong></div>
                <div class="doctor-detail-row"><span>Doctor email</span><strong>{{ $doctor['email'] }}</strong></div>
                <div class="doctor-detail-row"><span>Contact number</span><strong>{{ $doctor['contactno'] ?: 'Not provided' }}</strong></div>
                <div class="doctor-detail-row"><span>Portal access</span><strong>All telemedicine appointments</strong></div>
                <div class="doctor-detail-row"><span>Appointment permissions</span><strong>View patient details and start Jitsi consultations</strong></div>
                <div class="doctor-detail-row"><span>Status controls</span><strong>Managed by the kiosk and authorized staff</strong></div>
            </div>
        </section>
    </div>

    <div class="modal fade doctor-profile-modal" id="doctorProfileModal" tabindex="-1" aria-labelledby="doctorProfileModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="doctorProfileModalTitle">Edit profile</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('doctor.profile.update') }}" method="POST" enctype="multipart/form-data" id="doctorProfileUpdateForm">
                        @csrf
                        @method('PUT')

                        <div class="doctor-edit-photo">
                            <span class="doctor-edit-avatar">
                                @if ($doctor['profile_pic'])
                                    <img id="doctorProfilePicPreview" src="{{ $doctor['profile_pic'] }}" alt="Profile picture preview">
                                @else
                                    <span id="doctorProfilePicInitials">{{ $doctor['initials'] }}</span>
                                @endif
                            </span>
                            <div class="doctor-edit-photo-copy">
                                <strong>Profile photo</strong>
                                <small>Shown in the portal header and your profile card.</small>
                                <div class="doctor-edit-photo-actions">
                                    <label for="doctorProfilePicInput" class="doctor-action-button">
                                        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>Upload photo
                                    </label>
                                    <input type="file" name="profile_pic" id="doctorProfilePicInput" accept="image/*" data-doctor-photo-input hidden>
                                    @error('profile_pic') <div class="doctor-form-error">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="doctor-edit-grid doctor-edit-grid-names">
                            <div class="doctor-edit-field">
                                <label for="doctorFirstName">First name</label>
                                <input type="text" id="doctorFirstName" name="first_name" value="{{ old('first_name', $doctor['first_name']) }}" maxlength="100" required>
                                @error('first_name') <div class="doctor-form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="doctor-edit-field">
                                <label for="doctorMiddleName">Middle name</label>
                                <input type="text" id="doctorMiddleName" name="middle_name" value="{{ old('middle_name', $doctor['middle_name']) }}" maxlength="100">
                                @error('middle_name') <div class="doctor-form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="doctor-edit-field">
                                <label for="doctorLastName">Last name</label>
                                <input type="text" id="doctorLastName" name="last_name" value="{{ old('last_name', $doctor['last_name']) }}" maxlength="100" required>
                                @error('last_name') <div class="doctor-form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="doctor-edit-grid">
                            <div class="doctor-edit-field">
                                <label for="doctorEmail">Doctor email</label>
                                <input type="email" id="doctorEmail" name="email" value="{{ old('email', $doctor['email']) }}" maxlength="150" required>
                                @error('email') <div class="doctor-form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="doctor-edit-field">
                                <label for="doctorContact">Contact number</label>
                                <input type="text" id="doctorContact" name="contactno" value="{{ old('contactno', $doctor['contactno']) }}" maxlength="20">
                                @error('contactno') <div class="doctor-form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </form>

                    <hr class="doctor-edit-divider">

                    <form action="{{ route('doctor.profile.password') }}" method="POST" id="doctorProfilePasswordForm">
                        @csrf
                        @method('PUT')

                        <h3 class="doctor-edit-heading">Change password</h3>
                        <div class="doctor-edit-grid">
                            <div class="doctor-edit-field">
                                <label for="doctorCurrentPassword">Current password</label>
                                <input type="password" id="doctorCurrentPassword" name="current_password" maxlength="200" required>
                                @error('current_password') <div class="doctor-form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="doctor-edit-field">
                                <label for="doctorNewPassword">New password</label>
                                <input type="password" id="doctorNewPassword" name="password" minlength="8" maxlength="200" required>
                                @error('password') <div class="doctor-form-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="doctor-edit-field">
                                <label for="doctorConfirmPassword">Confirm new password</label>
                                <input type="password" id="doctorConfirmPassword" name="password_confirmation" maxlength="200" required>
                            </div>
                        </div>
                        <div class="doctor-edit-form-actions">
                            <button type="submit" class="doctor-action-button primary">
                                <i class="bi bi-shield-lock" aria-hidden="true"></i>Update password
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="doctor-action-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="doctorProfileUpdateForm" class="doctor-action-button primary">
                        <i class="bi bi-check2" aria-hidden="true"></i>Save changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        @push('scripts')
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    const modalElement = document.getElementById('doctorProfileModal');
                    if (modalElement) bootstrap.Modal.getOrCreateInstance(modalElement).show();
                });
            </script>
        @endpush
    @endif
@endsection
