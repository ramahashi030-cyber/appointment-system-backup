@extends('auth.guest')

@section('title', 'Registration')
@section('description', 'Create your QMMC Patient Portal account.')
@section('body-class', 'auth-registration-page auth-portal')

{{-- LEFT: hospital photo panel (shared with login) --}}
@section('art')
    @include('auth.partials.portal-hero')
@endsection

@push('styles')
    @include('auth.partials.portal-styles')
    @include('auth.partials.portal-register')
@endpush

@section('brand')
    <div class="auth-brand">
        @if (file_exists(public_path('images/logo.png')))
            <img class="auth-brand-logo" src="{{ asset('images/logo.png') }}" alt="Qalinga logo">
        @else
            <span class="auth-brand-wordmark">Qalinga</span>
        @endif

        <span class="auth-brand-rule"></span>

        <span class="auth-brand-text">
            <strong>QMMC Patient Portal</strong>
            <span>Create your patient portal account</span>
        </span>
    </div>
@endsection

@section('content')

    <form id="registrationForm" action="{{ route('register.store') }}" method="POST"
          data-register-url="{{ route('register.store') }}"
          data-verify-url="{{ route('register.verify.attempt') }}"
          data-resend-url="{{ route('register.resend') }}" novalidate>
        @csrf

        @if ($notice)
            <div class="alert alert-warning" role="alert">{{ $notice }}</div>
        @endif

        <div id="registrationErrors" class="alert alert-danger d-none" role="alert"></div>

        <div class="auth-field">
            <label class="auth-label" for="hospital_number">QMMC Hospital Number <span class="fw-normal">(if available)</span></label>
            <input type="text" class="auth-input" id="hospital_number" name="hospital_number"
                    value="{{ old('hospital_number', $hn) }}" autocapitalize="off" spellcheck="false"
                    inputmode="numeric" maxlength="6"
                    onkeydown="return (event.key >= '0' && event.key <= '9') || ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(event.key)"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)">
        </div>
        @error('hospital_number') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-grid">
            <div class="auth-field">
                <label class="auth-label" for="first_name">First Name</label>
                <input type="text" class="auth-input text-uppercase" id="first_name" name="first_name"
                       value="{{ old('first_name', $person['patfirst'] ?? '') }}" required
                       oninput="this.value = this.value.replace(/[0-9]/g, '')">
            </div>
            <div class="auth-field">
                <label class="auth-label" for="middlename">Middle Name</label>
                <input type="text" class="auth-input text-uppercase" id="middlename" name="middlename"
                       value="{{ old('middlename', $person['patmiddle'] ?? '') }}"
                       oninput="this.value = this.value.replace(/[0-9]/g, '')">
            </div>
        </div>
        @error('first_name') <span class="auth-error-text">{{ $message }}</span> @enderror
        @error('middlename') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field">
            <label class="auth-label" for="last_name">Last Name</label>
            <input type="text" class="auth-input text-uppercase" id="last_name" name="last_name"
                   value="{{ old('last_name', $person['patlast'] ?? '') }}" required
                   oninput="this.value = this.value.replace(/[0-9]/g, '')">
        </div>
        @error('last_name') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-grid">
            <div class="auth-field auth-date-field">
                <label class="auth-label" for="dob">Date of Birth</label>
                <input type="text" class="auth-input" id="dob" name="dob" data-flatpickr
                       value="{{ old('dob', isset($person['patbdate']) && $person['patbdate'] ? date('Y-m-d', strtotime($person['patbdate'])) : '') }}"
                       placeholder="Select date of birth" autocomplete="bday" inputmode="numeric"
                       aria-label="Date of Birth" required>
            </div>
            <div class="auth-field">
                <label class="auth-label" for="gender">Gender</label>
                <select class="auth-input" id="gender" name="gender" required>
                    <option value="">Select gender</option>
                    <option value="Male" @selected(old('gender') === 'Male')>Male</option>
                    <option value="Female" @selected(old('gender') === 'Female')>Female</option>
                </select>
            </div>
        </div>
        @error('dob') <span class="auth-error-text">{{ $message }}</span> @enderror
        @error('gender') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field">
            <label class="auth-label" for="contact_number">Cellphone Number</label>
            <input type="text" class="auth-input" id="contact_number" name="contact_number"
                   placeholder="11-digit cellphone number" inputmode="numeric" maxlength="11"
                   value="{{ old('contact_number', $person['pattelno'] ?? '') }}" required>
        </div>
        @error('contact_number') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field">
            <label class="auth-label" for="address">Address</label>
            <textarea class="auth-input" id="address" name="address" rows="2">{{ old('address', $address) }}</textarea>
        </div>
        @error('address') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field">
            <label class="auth-label" for="username">Username</label>
            <input type="text" class="auth-input" id="username" name="username" autocomplete="username"
                   autocapitalize="off" spellcheck="false" value="{{ old('username', $hn) }}" required>
        </div>
        @error('username') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field">
            <label class="auth-label" for="password">Password</label>
            <div class="auth-input-wrap">
                <input type="password" class="auth-input has-eye" id="password" name="password"
                       autocomplete="new-password" required>
                <button class="auth-eye" type="button" data-eye="password"
                        aria-label="Show password" aria-pressed="false">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        @error('password') <span class="auth-error-text">{{ $message }}</span> @enderror
 
        <button type="submit" class="auth-submit">Register</button>
        
        <div class="auth-links">
            <p class="auth-alt">Already registered? <a href="{{ route('auth.login') }}">Sign in</a></p>
        </div>
    </form>

    <div id="otpModal" class="auth-otp-modal" role="dialog" aria-modal="true"
         aria-labelledby="otpModalTitle" aria-describedby="otpModalDescription"
         aria-hidden="true" hidden>
        <div class="auth-otp-modal__backdrop" data-otp-backdrop></div>
        <div class="auth-otp-modal__dialog" role="document">
            <button type="button" class="auth-otp-modal__close" data-otp-close
                    aria-label="Close verification dialog">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>

            <div class="auth-otp-modal__body">
                <div class="auth-otp-modal__icon" aria-hidden="true">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h2 id="otpModalTitle">Verify your account</h2>
                <p id="otpModalDescription">
                    We sent a 6-digit code to
                    <strong id="otpContactText">your registered number</strong>.
                </p>

                <div id="otpStatus" class="auth-otp-status d-none" role="status"></div>

                <form id="otpForm" action="{{ route('register.verify.attempt') }}" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="contact" id="otpContactInput">

                    <div class="auth-field">
                        <label class="auth-label" for="otpInput">6-digit verification code</label>
                        <input type="text" class="auth-input auth-otp-input" id="otpInput" name="otp"
                               placeholder="Enter OTP" maxlength="6" inputmode="numeric"
                               autocomplete="one-time-code" pattern="[0-9]{6}" required>
                    </div>
                    <div id="otpError" class="auth-error-text d-none" role="alert"></div>

                    <button type="submit" class="auth-submit" id="otpVerifyButton">Verify account</button>
                </form>
            </div>

            <div class="auth-otp-modal__footer">
                <button type="button" class="auth-otp-resend" id="otpResendButton">Resend OTP</button>
                <span class="auth-otp-countdown" id="otpCountdown"></span>
            </div>
        </div>
    </div>

@endsection

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
<script>
    document.querySelectorAll('.text-uppercase').forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    });

    document.querySelectorAll('[data-eye]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var field = document.getElementById(this.dataset.eye);
            var icon  = this.querySelector('i');
            var shown = field.type === 'password';

            field.type = shown ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !shown);
            icon.classList.toggle('bi-eye-slash', shown);
            this.setAttribute('aria-pressed', shown ? 'true' : 'false');
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    (function () {
        var dateInput = document.getElementById('dob');

        if (!dateInput || typeof window.flatpickr !== 'function') {
            return;
        }

        window.flatpickr(dateInput, {
            dateFormat: 'Y-m-d',
            altFormat: 'M j, Y',
            altInput: true,
            altInputClass: 'auth-date-alt-input',
            allowInput: true,
            disableMobile: true,
            maxDate: 'today',
            position: 'auto center',
            onReady: function (selectedDates, dateStr, instance) {
                var displayInput = instance.altInput;
                var dateLabel = document.querySelector('label[for="dob"]');

                if (!displayInput) {
                    return;
                }

                displayInput.classList.add('auth-input');
                displayInput.id = 'dob-display';
                displayInput.placeholder = 'Select date of birth';
                displayInput.setAttribute('aria-label', 'Date of Birth');
                displayInput.setAttribute('aria-required', 'true');

                if (dateLabel) {
                    dateLabel.setAttribute('for', 'dob-display');
                }
            },
            onChange: function (selectedDates) {
                dateInput.setCustomValidity('');

                if (selectedDates.length > 0 && selectedDates[0] > new Date()) {
                    dateInput.setCustomValidity('Date of birth cannot be in the future.');
                }
            }
        });
    })();
</script>

<script>
    (function () {
        var registrationForm = document.getElementById('registrationForm');
        var modal = document.getElementById('otpModal');

        if (!registrationForm || !modal || !window.fetch) {
            return;
        }

        // Keep the dialog outside the transformed auth card so fixed positioning
        // remains viewport-relative on every screen size.
        document.body.appendChild(modal);

        var otpForm = document.getElementById('otpForm');
        var otpInput = document.getElementById('otpInput');
        var otpContactInput = document.getElementById('otpContactInput');
        var otpContactText = document.getElementById('otpContactText');
        var otpStatus = document.getElementById('otpStatus');
        var otpDialog = modal.querySelector('.auth-otp-modal__dialog');
        var otpError = document.getElementById('otpError');
        var otpVerifyButton = document.getElementById('otpVerifyButton');
        var otpResendButton = document.getElementById('otpResendButton');
        var otpCountdown = document.getElementById('otpCountdown');
        var registrationErrors = document.getElementById('registrationErrors');
        var registerButton = registrationForm.querySelector('button[type="submit"]');
        var registerButtonText = registerButton ? registerButton.textContent : 'Register';
        var csrfToken = registrationForm.querySelector('input[name="_token"]');
        var registerUrl = registrationForm.dataset.registerUrl;
        var verifyUrl = registrationForm.dataset.verifyUrl;
        var resendUrl = registrationForm.dataset.resendUrl;
        var countdownTimer = null;
        var attentionTimer = null;
        var resendSeconds = 0;
        var processing = false;
        var verified = false;

        function csrfValue() {
            return csrfToken ? csrfToken.value : '';
        }

        function requestJson(url, body) {
            return fetch(url, {
                method: 'POST',
                body: body,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfValue()
                }
            }).then(function (response) {
                return response.json()
                    .catch(function () {
                        return {};
                    })
                    .then(function (data) {
                        return { ok: response.ok, data: data };
                    });
            });
        }

        function clearRegistrationErrors() {
            registrationErrors.innerHTML = '';
            registrationErrors.classList.add('d-none');
        }

        function showRegistrationErrors(data, fallback) {
            var messages = [];
            var errors = data.errors || {};

            if (data.message) {
                messages.push(data.message);
            }

            Object.keys(errors).forEach(function (field) {
                var fieldMessages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];

                fieldMessages.forEach(function (message) {
                    if (messages.indexOf(message) === -1) {
                        messages.push(message);
                    }
                });
            });

            if (messages.length === 0) {
                messages.push(fallback);
            }

            registrationErrors.innerHTML = '';
            var list = document.createElement('ul');

            messages.forEach(function (message) {
                var item = document.createElement('li');
                item.textContent = message;
                list.appendChild(item);
            });

            registrationErrors.appendChild(list);
            registrationErrors.classList.remove('d-none');
        }

        function setOtpStatus(message, type) {
            otpStatus.textContent = message;
            otpStatus.className = 'auth-otp-status auth-otp-status--' + type;
            otpStatus.classList.remove('d-none');
        }

        function setOtpError(message) {
            otpError.textContent = message;
            otpError.classList.remove('d-none');
        }

        function clearOtpError() {
            otpError.textContent = '';
            otpError.classList.add('d-none');
        }

        function setRegisterLoading(isLoading) {
            if (!registerButton) {
                return;
            }

            registerButton.disabled = isLoading;
            registerButton.textContent = isLoading ? 'Creating account...' : registerButtonText;
        }

        function setVerifyLoading(isLoading) {
            otpVerifyButton.disabled = isLoading;
            otpVerifyButton.textContent = isLoading ? 'Verifying...' : 'Verify account';
        }

        function stopCountdown() {
            if (countdownTimer) {
                window.clearInterval(countdownTimer);
                countdownTimer = null;
            }
        }

        function startCountdown(seconds) {
            stopCountdown();
            resendSeconds = Math.max(30, parseInt(seconds, 10) || 60);
            otpResendButton.disabled = true;
            otpResendButton.textContent = 'Resend OTP';
            otpCountdown.textContent = 'You can resend in ' + resendSeconds + 's';

            countdownTimer = window.setInterval(function () {
                resendSeconds--;

                if (resendSeconds <= 0) {
                    stopCountdown();
                    otpResendButton.disabled = false;
                    otpCountdown.textContent = 'Code expired. Request a new one.';
                    return;
                }

                otpCountdown.textContent = 'You can resend in ' + resendSeconds + 's';
            }, 1000);
        }

        function openOtpModal(contact) {
            otpContactText.textContent = contact;
            otpContactInput.value = contact;
            otpInput.value = '';
            otpError.textContent = '';
            otpError.classList.add('d-none');
            otpStatus.classList.remove('d-none');
            otpStatus.className = 'auth-otp-status auth-otp-status--info';
            otpStatus.textContent = 'Creating your account and sending the code...';
            otpCountdown.textContent = '';
            otpResendButton.disabled = true;
            processing = true;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('auth-modal-open');

            window.setTimeout(function () {
                otpInput.focus();
            }, 50);
        }

        function finishOtpModal(data) {
            var contact = data.contact || otpContactInput.value;

            otpContactText.textContent = contact;
            otpContactInput.value = contact;
            setOtpStatus(data.message || 'Enter the code sent to your cellphone number.', data.sent === false ? 'error' : 'info');

            if (data.sent === false) {
                otpStatus.textContent = 'The SMS gateway could not be reached. Please contact the clinic for your code.';
            }

            startCountdown(data.window);
            processing = false;
        }

        function flashModalBorder() {
            otpDialog.classList.remove('auth-otp-modal__dialog--attention');
            void otpDialog.offsetWidth;
            otpDialog.classList.add('auth-otp-modal__dialog--attention');
            window.clearTimeout(attentionTimer);
            attentionTimer = window.setTimeout(function () {
                otpDialog.classList.remove('auth-otp-modal__dialog--attention');
                attentionTimer = null;
            }, 1300);
        }

        function closeOtpModal() {
            if (processing) {
                return;
            }

            stopCountdown();
            window.clearTimeout(attentionTimer);
            attentionTimer = null;
            otpDialog.classList.remove('auth-otp-modal__dialog--attention');
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('auth-modal-open');
            setVerifyLoading(false);
            otpResendButton.disabled = false;
        }

        function otpErrorMessage(data, fallback) {
            if (data.errors && data.errors.otp && data.errors.otp.length) {
                return data.errors.otp[0];
            }

            return data.message || fallback;
        }

        registrationForm.addEventListener('submit', function (event) {
            event.preventDefault();
            clearRegistrationErrors();

            if (!registrationForm.checkValidity()) {
                registrationForm.reportValidity();
                return;
            }

            var contactInput = registrationForm.querySelector('[name="contact_number"]');
            var contact = contactInput ? contactInput.value.trim() : '';
            openOtpModal(contact);
            setRegisterLoading(true);

            requestJson(registerUrl, new FormData(registrationForm))
                .then(function (result) {
                    if (!result.ok) {
                        processing = false;
                        closeOtpModal();
                        showRegistrationErrors(result.data, 'We could not create your account. Please try again.');
                        return;
                    }

                    finishOtpModal(result.data);
                })
                .catch(function () {
                    processing = false;
                    closeOtpModal();
                    showRegistrationErrors({}, 'We could not create your account. Please try again.');
                })
                .finally(function () {
                    setRegisterLoading(false);
                });
        });

        otpInput.addEventListener('input', function () {
            otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 6);
            clearOtpError();
        });

        otpForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (verified) {
                return;
            }

            var code = otpInput.value.trim();

            if (!/^\d{6}$/.test(code)) {
                setOtpError('Enter the 6-digit code sent to your cellphone number.');
                return;
            }

            clearOtpError();
            setVerifyLoading(true);

            requestJson(verifyUrl, new FormData(otpForm))
                .then(function (result) {
                    var data = result.data || {};
                    var message = otpErrorMessage(data, 'The OTP could not be verified.');

                    if (!result.ok) {
                        setOtpError(message);

                        if (data.redirect) {
                            setOtpStatus(message, 'error');
                            window.setTimeout(function () {
                                window.location.assign(data.redirect);
                            }, 1200);
                        }

                        return;
                    }

                    verified = true;
                    setOtpStatus(data.message || 'Account verified successfully.', 'success');
                    otpInput.disabled = true;
                    otpResendButton.disabled = true;
                    window.setTimeout(function () {
                        window.location.assign(data.redirect || '/');
                    }, 1200);
                })
                .catch(function () {
                    setOtpError('The OTP could not be verified. Please try again.');
                })
                .finally(function () {
                    setVerifyLoading(false);
                });
        });

        otpResendButton.addEventListener('click', function () {
            if (otpResendButton.disabled || verified) {
                return;
            }

            otpResendButton.disabled = true;
            setOtpStatus('Sending a new code...', 'info');

            var body = new FormData();
            body.append('_token', csrfValue());
            body.append('contact', otpContactInput.value);

            requestJson(resendUrl, body)
                .then(function (result) {
                    if (!result.ok) {
                        otpResendButton.disabled = false;
                        setOtpError(otpErrorMessage(result.data, 'The OTP could not be resent.'));
                        return;
                    }

                    setOtpStatus(result.data.message || 'A new OTP was sent.', result.data.sent === false ? 'error' : 'info');
                    startCountdown(result.data.window);
                })
                .catch(function () {
                    otpResendButton.disabled = false;
                    setOtpError('The OTP could not be resent. Please try again.');
                });
        });

        Array.prototype.forEach.call(modal.querySelectorAll('[data-otp-backdrop]'), function (element) {
            element.addEventListener('click', function (event) {
                event.preventDefault();
                flashModalBorder();
            });
        });

        Array.prototype.forEach.call(modal.querySelectorAll('[data-otp-close]'), function (element) {
            element.addEventListener('click', closeOtpModal);
        });
    })();
</script>
@endpush
