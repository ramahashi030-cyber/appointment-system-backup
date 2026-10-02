@php
    $admin = auth('admin')->user();
    $adminName = $admin !== null
        ? trim($admin->firstname.' '.$admin->lastname)
        : 'Admin User';
    // The middle name field only shows when the `admin` table has that column.
    $hasMiddleName = $admin !== null && array_key_exists('middlename', $admin->getAttributes());
    $nameColumn = $hasMiddleName ? 'col-md-4' : 'col-md-6';
@endphp

<header class="admin-header">
    <div class="admin-header-inner">
        <button class="admin-menu-toggle d-lg-none" type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#adminMobileMenu"
                aria-controls="adminMobileMenu"
                aria-label="Open navigation menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div class="admin-header-actions">

            <div class="admin-user" title="My profile" aria-label="My profile" style="cursor: pointer;"
                 @if ($admin !== null)
                     role="button" tabindex="0" aria-haspopup="dialog"
                     data-bs-toggle="modal" data-bs-target="#adminProfileModal" data-admin-profile-trigger
                 @endif>
                <span class="admin-user-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                <span class="admin-user-copy">
                    <strong>{{ $adminName }}</strong>
                    <small>Administrator</small>
                </span>
            </div>

            <form class="admin-logout" action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</header>

@if ($admin !== null)
    {{-- MY PROFILE MODAL (same structure as the Add patient modal on the Patients page) --}}
    <div class="modal fade admin-doctor-modal" id="adminProfileModal" tabindex="-1" aria-labelledby="adminProfileModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="adminProfileModalTitle">My profile</h2>
                            <p class="admin-telemedicine-description">Update your name and change your password.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>

                <div class="modal-body">
                    <div id="adminProfileNotice" class="alert d-none" role="status" aria-live="polite"></div>

                    {{-- Personal information --}}
                    <form id="adminProfileForm" method="POST" action="{{ route('admin.profile.update') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-person-fill text-primary" aria-hidden="true"></i>
                            <h3 class="h6 fw-semibold mb-0">Personal information</h3>
                        </div>

                        <div class="row g-3">
                            <div class="{{ $nameColumn }}">
                                <label class="form-label" for="adminProfileFirstname">First name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="adminProfileFirstname" name="firstname" value="{{ $admin->firstname }}" placeholder="First name" required maxlength="100" autocomplete="given-name">
                            </div>
                            @if ($hasMiddleName)
                                <div class="{{ $nameColumn }}">
                                    <label class="form-label" for="adminProfileMiddlename">Middle name</label>
                                    <input type="text" class="form-control" id="adminProfileMiddlename" name="middlename" value="{{ $admin->middlename }}" placeholder="Middle name" maxlength="100" autocomplete="additional-name">
                                </div>
                            @endif
                            <div class="{{ $nameColumn }}">
                                <label class="form-label" for="adminProfileLastname">Last name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="adminProfileLastname" name="lastname" value="{{ $admin->lastname }}" placeholder="Last name" required maxlength="100" autocomplete="family-name">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Save changes
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    {{-- Change password --}}
                    <form id="adminPasswordForm" method="POST" action="{{ route('admin.profile.password') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-shield-lock-fill text-primary" aria-hidden="true"></i>
                            <h3 class="h6 fw-semibold mb-0">Change password</h3>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="adminProfileCurrentPassword">Current password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="adminProfileCurrentPassword" name="current_password" placeholder="Current password" required maxlength="100" autocomplete="current-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="adminProfileNewPassword">New password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="adminProfileNewPassword" name="password" placeholder="New password" required minlength="8" maxlength="100" autocomplete="new-password">
                                <div class="form-text">Minimum 8 characters</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="adminProfileConfirmPassword">Confirm new password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="adminProfileConfirmPassword" name="password_confirmation" placeholder="Confirm new password" required minlength="8" maxlength="100" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Change password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(function () {
        var $modal = $('#adminProfileModal');

        if (!$modal.length) {
            return;
        }

        // Keep the modal directly under <body> so the header's stacking context
        // can never place it behind the backdrop.
        if (!$modal.parent().is('body')) {
            $modal.appendTo(document.body);
        }

        var $notice = $('#adminProfileNotice');

        function notify(type, message) {
            $notice
                .removeClass('d-none alert-success alert-danger')
                .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
                .text(message);
        }

        function clearErrors($form) {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.profile-field-error').remove();
        }

        function showErrors($form, errors) {
            $.each(errors, function (field, messages) {
                var $input = $form.find('[name="' + field + '"]').addClass('is-invalid');
                $('<div class="profile-field-error invalid-feedback d-block"></div>')
                    .text(messages[0])
                    .insertAfter($input);
            });
            $form.find('.is-invalid').first().trigger('focus');
        }

        function resetModal() {
            $modal.find('form').each(function () {
                this.reset();
                clearErrors($(this));
            });
            $notice.addClass('d-none').removeClass('alert-success alert-danger').empty();
        }

        // The header persists between page navigations and this script runs
        // again on each one, so remove the old handlers before adding new ones.
        $(document).off('.adminProfile');
        $modal.off('.adminProfile');

        $modal.on('hidden.bs.modal.adminProfile', resetModal);

        $(document).on('keydown.adminProfile', '[data-admin-profile-trigger]', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.click();
            }
        });

        $(document).on('submit.adminProfile', '#adminProfileForm, #adminPasswordForm', function (event) {
            event.preventDefault();

            var $form = $(this);
            var isPasswordForm = $form.attr('id') === 'adminPasswordForm';
            var $submit = $form.find('button[type="submit"]');

            if ($submit.prop('disabled')) {
                return;
            }

            var originalHtml = $submit.html();

            clearErrors($form);
            $notice.addClass('d-none').removeClass('alert-success alert-danger').empty();
            $submit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            }).done(function (response) {
                notify('success', response.message || 'Saved successfully.');

                if (isPasswordForm) {
                    $form[0].reset();
                    return;
                }

                // Remember the saved values so closing the modal does not
                // restore the old ones, and refresh the name in the top bar.
                $form.find('input[type="text"]').each(function () {
                    this.defaultValue = this.value;
                });

                if (response.full_name) {
                    $('.admin-user-copy strong').text(response.full_name);
                }
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                } else {
                    notify('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.');
                }
            }).always(function () {
                $submit.prop('disabled', false).html(originalHtml);
            });
        });
    });
    </script>
    @endpush
@endif