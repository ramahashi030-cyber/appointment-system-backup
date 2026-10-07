@once
    <style>
        /* Live date/time pinned to the LEFT side of the admin navbar.
           overflow:hidden plus a very large flex-shrink let the clock absorb any
           overflow itself, so it can never squeeze the bell/profile/logout
           controls when the viewport is narrow. */
        .admin-header-datetime {
            min-width: 0;
            overflow: hidden;
            flex-shrink: 999;
            color: #dbeaff;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: .2px;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-variant-numeric: tabular-nums;
        }

        .admin-header-datetime-time,
        .admin-header-datetime-seconds {
            color: #fff;
            font-weight: 600;
        }

        /* Phones/small tablets: keep the time, drop the date (not enough room). */
        @media (max-width: 767.98px) {
            .admin-header-datetime-date,
            .admin-header-datetime-sep {
                display: none;
            }
        }

        /* Narrow phones: time only, without seconds/AM-PM. */
        @media (max-width: 479.98px) {
            .admin-header-datetime {
                font-size: 10px;
            }

            .admin-header-datetime-seconds,
            .admin-header-datetime-meridiem {
                display: none;
            }
        }

        @media (max-width: 379.98px) {
            .admin-header-datetime {
                font-size: 9px;
            }
        }

        /* The account cluster is just the profile circle now, so keep its
           hover highlight perfectly circular around the avatar. */
        .admin-header .admin-user:hover {
            border-radius: 100%;
        }
    </style>
@endonce

@php
    $admin = auth('admin')->user();
    $adminName = $admin !== null
        ? trim($admin->firstname.' '.$admin->lastname)
        : 'Admin User';
    // The middle name field only shows when the `admin` table has that column.
    $hasMiddleName = $admin !== null && array_key_exists('middlename', $admin->getAttributes());
    $nameColumn = $hasMiddleName ? 'col-md-4' : 'col-md-6';
    // Mirrors Admin\ProfileController::NAME_PATTERN (letters and the spaces
    // between them) so the browser and the server agree on what a name is.
    $namePattern = '[A-Za-z]+( +[A-Za-z]+)*';
    // Mirrors the password rules in Admin\ProfileController.
    $passwordMinLength = \App\Http\Controllers\Admin\ProfileController::PASSWORD_MIN;
    $passwordMaxLength = \App\Http\Controllers\Admin\ProfileController::PASSWORD_MAX;
    $passwordSymbolThreshold = \App\Http\Controllers\Admin\ProfileController::PASSWORD_SYMBOL_THRESHOLD;
    $passwordHint = $passwordMinLength.' to '.$passwordMaxLength
        .' characters, with uppercase and lowercase letters and a number'
        .' (over '.$passwordSymbolThreshold.' characters also needs a special character).';
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

        {{-- Running date/time — always on the left of the bar, kept live by the script below. --}}
        <div class="admin-header-datetime" id="adminHeaderDateTime" role="timer" aria-live="off"
             aria-label="Current date and time"><span class="admin-header-datetime-date" data-admin-clock-date></span><span class="admin-header-datetime-sep" data-admin-clock-sep aria-hidden="true"></span><span class="admin-header-datetime-time" data-admin-clock-time></span><span class="admin-header-datetime-seconds" data-admin-clock-seconds></span><span class="admin-header-datetime-meridiem" data-admin-clock-meridiem></span></div>

        <div class="admin-header-actions">

            @include('partials.notification-bell', ['rtVariant' => 'admin', 'rtModalId' => 'realtimeNotificationsModal'])

            <div class="dropdown">
                <button class="admin-user" type="button" id="adminUserMenuToggle"
                        title="Account menu" aria-label="Open admin account menu"
                        @if ($admin !== null)
                            data-admin-profile-trigger
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        @endif>
                    <span class="admin-user-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                </button>

                @if ($admin !== null)
                    <div class="dropdown-menu dropdown-menu-end admin-user-menu" aria-labelledby="adminUserMenuToggle">
                        <div class="admin-user-menu-header">
                            <Strong>{{ $adminName }}</Strong>
                            <small>Administrator</small>
                        </div>
                        <button type="button" class="dropdown-item"
                                data-bs-toggle="modal" data-bs-target="#adminProfileModal">
                            <i class="bi bi-person-circle" aria-hidden="true"></i> Show Profile
                        </button>
                        <button type="button" class="dropdown-item"
                                data-bs-toggle="modal" data-bs-target="#newAdminModal">
                            <i class="bi bi-person-plus" aria-hidden="true"></i> New Admin
                        </button>
                    </div>
                @endif
            </div>

            <button type="button" class="admin-logout-btn" data-bs-toggle="modal" data-bs-target="#adminLogoutModal">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Logout</span>
                </button>
        </div>
    </div>
</header>

@push('scripts')
<script>
/*
 * Running date/time in the admin navbar (e.g. "October 7, 2026 | 08:16:25 AM").
 * Reads the browser's local clock on every tick and schedules the next tick on
 * the upcoming second boundary, so it stays accurate while the page stays open.
 */
(function () {
    var root = document.getElementById('adminHeaderDateTime');

    if (!root) {
        return;
    }

    // The header outlives the admin's page-swap navigation, which re-runs inline
    // scripts on every swap, so clear any previous timer first: one clock only.
    if (window.__adminHeaderDateTimeTimer) {
        window.clearTimeout(window.__adminHeaderDateTimeTimer);
    }

    var dateEl = root.querySelector('[data-admin-clock-date]');
    var sepEl = root.querySelector('[data-admin-clock-sep]');
    var timeEl = root.querySelector('[data-admin-clock-time]');
    var secondsEl = root.querySelector('[data-admin-clock-seconds]');
    var meridiemEl = root.querySelector('[data-admin-clock-meridiem]');

    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    function render() {
        var now = new Date();
        var hours = now.getHours();

        dateEl.textContent = now.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
        sepEl.textContent = ' | ';
        timeEl.textContent = pad(hours % 12 || 12) + ':' + pad(now.getMinutes());
        secondsEl.textContent = ':' + pad(now.getSeconds());
        meridiemEl.textContent = hours >= 12 ? ' PM' : ' AM';
    }

    function tick() {
        render();
        window.__adminHeaderDateTimeTimer = window.setTimeout(tick, 1000 - (Date.now() % 1000));
    }

    tick();
})();
</script>
@endpush

{{-- Real-time notification bell detail modal (additive — see partials/notification-modal). --}}
@include('partials.notification-modal', ['rtVariant' => 'admin', 'rtModalId' => 'realtimeNotificationsModal'])

@if ($admin !== null)
    {{-- MY PROFILE MODAL (same structure as the Add patient modal on the Patients page) --}}
    <div class="modal fade admin-doctor-modal" id="adminProfileModal" tabindex="-1" aria-labelledby="adminProfileModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
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
                            <p class="admin-telemedicine-description">Update admin information.</p>
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

                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-person-fill text-primary" aria-hidden="true"></i>
                            <h3 class="h6 fw-semibold mb-0">Personal information</h3>
                        </div>

                        <div class="row g-2">
                            <div class="{{ $nameColumn }}">
                                <label class="form-label" for="adminProfileFirstname">First name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="adminProfileFirstname" name="firstname" value="{{ $admin->firstname }}" placeholder="First name" required maxlength="100" pattern="{{ $namePattern }}" data-letters-only autocomplete="given-name">
                                <div class="form-text">Letters and spaces only</div>
                            </div>
                            @if ($hasMiddleName)
                                <div class="{{ $nameColumn }}">
                                    <label class="form-label" for="adminProfileMiddlename">Middle name</label>
                                    <input type="text" class="form-control" id="adminProfileMiddlename" name="middlename" value="{{ $admin->middlename }}" placeholder="Middle name" maxlength="100" autocomplete="additional-name">
                                </div>
                            @endif
                            <div class="{{ $nameColumn }}">
                                <label class="form-label" for="adminProfileLastname">Last name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="adminProfileLastname" name="lastname" value="{{ $admin->lastname }}" placeholder="Last name" required maxlength="100" pattern="{{ $namePattern }}" data-letters-only autocomplete="family-name">
                                <div class="form-text">Letters and spaces only</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="adminProfileEmail">Email</label>
                                <input type="text" class="form-control" id="adminProfileEmail" value="{{ $admin->email }}" readonly autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="adminProfileContactNo">Contact number</label>
                                <input type="text" class="form-control" id="adminProfileContactNo" value="{{ $admin->contact_no }}" readonly autocomplete="off">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Save changes
                            </button>
                        </div>
                    </form>

                    <div class="d-flex justify-content-end mt-2">
                        <button type="button" class="btn btn-outline-primary" id="adminProfilePasswordToggle" aria-expanded="false" aria-controls="adminPasswordSection">Change password</button>
                    </div>

                    <div id="adminPasswordSection" class="d-none">
                        <hr class="my-2">

                        {{-- Change password --}}
                        <form id="adminPasswordForm" method="POST" action="{{ route('admin.profile.password') }}" novalidate>
                            @csrf
                            @method('PUT')

                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-shield-lock-fill text-primary" aria-hidden="true"></i>
                                <h3 class="h6 fw-semibold mb-0">Change password</h3>
                            </div>

                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label" for="adminProfileCurrentPassword">Current password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="adminProfileCurrentPassword" name="current_password" placeholder="Current password" required maxlength="100" autocomplete="current-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="adminProfileNewPassword">New password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="adminProfileNewPassword" name="password" placeholder="New password" required minlength="{{ $passwordMinLength }}" maxlength="{{ $passwordMaxLength }}" autocomplete="new-password">
                                    <div class="form-text">{{ $passwordHint }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="adminProfileConfirmPassword">Confirm new password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="adminProfileConfirmPassword" name="password_confirmation" placeholder="Confirm new password" required minlength="{{ $passwordMinLength }}" maxlength="{{ $passwordMaxLength }}" autocomplete="new-password">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-2">
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
    </div>

    @once
        <style>
            /* My Profile modal: compact fields/buttons, borderless inputs kept
               delineated by a soft shadow, and a one-shot danger border blink
               when an outside click is blocked. Scoped to #adminProfileModal so
               no other admin-doctor-modal is affected. */
            #adminProfileModal .modal-body .form-control {
                border-color: transparent;
                box-shadow: 0 1px 2px rgba(13, 40, 90, .08), 0 3px 10px rgba(13, 40, 90, .06);
                padding: .52rem .8rem;
                font-size: 14px;
            }

            #adminProfileModal .modal-body .form-control:focus {
                border-color: transparent;
                box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25), 0 1px 2px rgba(13, 40, 90, .08), 0 3px 10px rgba(13, 40, 90, .06);
            }

            #adminProfileModal .modal-body .form-control::placeholder {
                font-size: 13px;
            }

            #adminProfileModal .modal-body .btn {
                padding: .3rem .75rem;
                font-size: 13px;
            }

            /* Compact vertical rhythm: tighter modal-body padding and label
               spacing, plus the slightly larger section icons. */
            #adminProfileModal .modal-body {
                padding: 14px 20px 16px;
            }

            #adminProfileModal .form-label {
                margin-bottom: .25rem;
            }

            #adminProfileModal .form-text {
                margin-top: .2rem;
            }

            #adminProfileModal .admin-telemedicine-mark > i {
                font-size: 32px;
            }

            #adminProfileModal .modal-body .bi-shield-lock-fill {
                font-size: 20px;
                line-height: 1;
            }

            @keyframes adminProfileModalBlocked {
                0%,
                100% {
                    border-color: #1b4d91;
                    box-shadow: 0 24px 60px rgba(7, 30, 61, .3);
                }

                45% {
                    border-color: #d9534f;
                    box-shadow: 0 0 0 4px rgba(217, 83, 79, .22), 0 24px 60px rgba(7, 30, 61, .3);
                }
            }

            #adminProfileModal .modal-content.admin-profile-modal-blocked {
                border-color: #d9534f;
                animation: adminProfileModalBlocked .5s ease-in-out 1;
            }

            @media (prefers-reduced-motion: reduce) {
                #adminProfileModal .modal-content.admin-profile-modal-blocked {
                    animation-duration: .4s;
                }
            }
        </style>
    @endonce

    {{-- NEW ADMIN MODAL (blank — input fields and functionality come in a later task) --}}
    <div class="modal fade admin-doctor-modal" id="newAdminModal" tabindex="-1" aria-labelledby="newAdminModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="newAdminModalTitle">New Admin</h2>
                            <p class="admin-telemedicine-description">Add a new administrator account.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>

                <div class="modal-body">
                    {{-- New Admin form fields will be added in a separate task. --}}
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(function () {
        var $newAdminModal = $('#newAdminModal');

        if (!$newAdminModal.length) {
            return;
        }

        // Keep the modal directly under <body> (same stacking-context safeguard
        // as the profile modal) so the header can never place it behind the backdrop.
        if (!$newAdminModal.parent().is('body')) {
            $newAdminModal.appendTo(document.body);
        }
    });
    </script>
    @endpush

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

        var $profileContent = $modal.find('.modal-content');
        $profileContent.off('.adminProfile');

        // Outside clicks cannot close this modal (static backdrop, keyboard
        // disabled): a blocked hide attempt flashes the content border once
        // with the danger color, then the animation returns it to normal.
        $modal.on('hidePrevented.bs.modal.adminProfile', function () {
            var content = $profileContent[0];

            content.classList.remove('admin-profile-modal-blocked');
            void content.offsetWidth;
            content.classList.add('admin-profile-modal-blocked');
        });

        $profileContent.on('animationend.adminProfile animationcancel.adminProfile', function (event) {
            if (event.originalEvent && event.originalEvent.animationName === 'adminProfileModalBlocked') {
                $profileContent.removeClass('admin-profile-modal-blocked');
            }
        });

        $modal.on('hidden.bs.modal.adminProfile', function () {
            $profileContent.removeClass('admin-profile-modal-blocked');
        });

        // Password fields stay collapsed until "Change password" is clicked so
        // the modal remains compact while they are hidden. Clicking the same
        // button again collapses the section, and any close resets it for the
        // next open.
        var $passwordSection = $('#adminPasswordSection');
        var $passwordToggle = $('#adminProfilePasswordToggle');

        if ($passwordToggle.length) {
            $passwordToggle.off('.adminProfile').on('click.adminProfile', function () {
                var expand = $passwordSection.hasClass('d-none');
                $passwordSection.toggleClass('d-none', !expand);
                $passwordToggle.attr('aria-expanded', String(expand));
                if (expand) {
                    $passwordSection.find('input').first().trigger('focus');
                }
            });
        }

        $modal.on('hidden.bs.modal.adminProfile', function () {
            $passwordSection.addClass('d-none');
            $passwordToggle.attr('aria-expanded', 'false');
        });

        // First and last name are letters only. Anything else is dropped as it
        // is typed or pasted, and surrounding spaces are trimmed, so the value
        // that reaches the server already matches the expected name format.
        $modal.on('input.adminProfile', '[data-letters-only]', function () {
            this.value = this.value
                .replace(/[^A-Za-z ]+/g, '')
                .replace(/^ +| +$/g, '');
        });

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

    {{-- LOGOUT CONFIRMATION MODAL --}}
    <div class="modal fade admin-logout-modal" id="adminLogoutModal" tabindex="-1" aria-labelledby="adminLogoutModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body admin-logout-modal-body">
                    <div class="admin-logout-modal-icon" aria-hidden="true">
                        <i class="bi bi-box-arrow-right"></i>
                    </div>
                    <h2 class="admin-logout-modal-title" id="adminLogoutModalTitle">Logout</h2>
                    <p class="admin-logout-modal-text">Are you sure you want to logout?</p>
                    <div class="admin-logout-modal-actions">
                        <button type="button" class="btn admin-logout-cancel" data-bs-dismiss="modal">Cancel</button>
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn admin-logout-confirm">Yes, logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var logoutModal = document.getElementById('adminLogoutModal');
        if (logoutModal) {
            // Ensure modal backdrop doesn't close on click (already set via data-bs-backdrop="static")
            // But also prevent keyboard dismissal
            logoutModal.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                }
            });
        }
    });
    </script>
    @endpush
@endif