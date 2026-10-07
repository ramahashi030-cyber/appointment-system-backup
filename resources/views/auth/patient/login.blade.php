@extends('auth.guest')

@section('title', 'Login page')
@section('description', 'Sign in to the QMMC portal with your administrator or patient credentials.')
@section('body-class', 'auth-login-page auth-portal')

{{-- ============ LEFT: hospital photo panel (shared with register) ============ --}}
@section('art')
    @include('auth.partials.portal-hero')
@endsection

@section('brand')
    <div class="pl-brand">
        @if (file_exists(public_path('images/logo.png')))
            <img class="pl-brand-logo" src="{{ asset('images/logo.png') }}" alt="Qalinga logo">
        @endif

        <span class="pl-brand-text">
            <strong>Quirino Memorial</strong>
            <span>Medical Center</span>
        </span>
    </div>
@endsection

@section('content')

    <div class="pl-head">
        <h1 class="pl-title">Welcome Back</h1>
        <p class="pl-subtitle">Sign in to your patient portal</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form action="{{ route('login.attempt') }}" method="POST" id="loginForm" novalidate class="pl-form">
        @csrf
    
        <div class="auth-field pl-field">
            <label class="visually-hidden" for="username">Username</label>
            <i class="bi bi-person-fill pl-field-icon" aria-hidden="true"></i>
            <input
                type="text"
                class="auth-input @error('username') is-invalid @enderror"
                id="username"
                name="username"
                placeholder="Username or Hospital ID"
                autocomplete="username"
                autocapitalize="off"
                spellcheck="false"
                value="{{ old('username') }}"
                required
            >
        </div>
        @error('username') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field pl-field">
            <label class="visually-hidden" for="password">Password</label>
            <i class="bi bi-lock-fill pl-field-icon" aria-hidden="true"></i>
            <div class="auth-input-wrap">
                <input
                    type="password"
                    class="auth-input has-eye"
                    id="password"
                    name="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    required
                >
                <button class="auth-eye" type="button" id="togglePassword"
                        aria-label="Show password" aria-pressed="false">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>
        </div>
        @error('password') <span class="auth-error-text">{{ $message }}</span> @enderror

        @error('login')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror

        <div class="pl-meta">
            <a class="pl-forgot" href="{{ route('password.reset') }}">Forgot password?</a>
        </div>

        <button type="submit" class="auth-submit pl-submit" id="loginBtn">
            <span id="loginBtnLabel">Sign In</span>
            <i class="bi bi-arrow-right pl-btn-arrow" aria-hidden="true"></i>
        </button>

        <div class="pl-or"><span>or</span></div>

        <p class="pl-register">Don't have an account? <a href="{{ route('register') }}">Register</a></p>

        {{-- hospital-number / birthdate mode (same behaviour as QALINGA1 login.php).
             Visually detached below the card on desktop, still inside the form. --}}
        <div class="pl-switch-wrap">
            <div class="auth-switch pl-switch">
                <input type="checkbox" id="toggleHospitalNumber">
                <label for="toggleHospitalNumber">Sign in with hospital number</label>
                <input type="hidden" name="use_hospital" id="useHospital" value="0">
            </div>

            <p class="auth-hint d-none pl-hint" id="hospitalNote">Birthdate format: MMDDYYYY</p>
        </div>
    </form>

@endsection

@push('styles')
    {{-- Shared split layout, photo hero, curve, card shell, buttons, responsive. --}}
    @include('auth.partials.portal-styles')

    /* =====================================================================
       Login-only styles
       ===================================================================== */

    /* ---------- brand + heading ---------- */
    .pl-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        margin-bottom: 26px;
        text-align: center;
    }

    .pl-brand-logo { height: 60px; width: auto; display: block; }
    .pl-brand-text { line-height: 1.2; }

    .pl-brand-text strong {
        display: block;
        font-size: 21px;
        font-weight: 700;
        color: #0a3d66;
    }

    .pl-brand-text span {
        display: block;
        margin-top: 2px;
        font-size: 18px;
        font-weight: 500;
        color: #0a3d66;
    }

    .pl-head { margin-bottom: 30px; }

    .pl-title {
        margin: 0;
        text-align: center;
        font-size: 38px;
        line-height: 1.15;
        font-weight: 700;
        letter-spacing: -.6px;
        color: #0a3d66;
    }

    .pl-subtitle {
        margin: 12px 0 0;
        text-align: center;
        font-size: 16.5px;
        color: #4d6278;
    }

    /* ---------- fields ---------- */
    body.auth-login-page .pl-field {
        position: relative;
        margin-bottom: 20px;
    }

    .pl-field .pl-field-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
        font-size: 17px;
        color: #1f7a92;
        pointer-events: none;
        transition: color .15s ease;
    }

    .pl-field:focus-within .pl-field-icon { color: #0a5f86; }

    body.auth-login-page .pl-field .auth-input {
        height: 60px;
        padding-left: 52px;
        padding-right: 20px;
        border: 1px solid #dce7ee;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 70, 95, .04);
        font-size: 15.5px;
        color: #0f2b46;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    body.auth-login-page .pl-field .auth-input::placeholder { color: #8a9bb0; }
    body.auth-login-page .pl-field .auth-input.has-eye { padding-right: 52px; }

    body.auth-login-page .pl-field .auth-input:focus {
        border-color: rgba(27, 146, 153, .7);
        box-shadow: 0 0 0 4px rgba(27, 146, 153, .15);
    }

    body.auth-login-page .pl-field .auth-input.is-invalid { border-color: var(--auth-coral); }

    .pl-field .auth-eye { right: 16px; width: 34px; color: #2b6e8a; }
    .pl-field .auth-eye:hover,
    .pl-field .auth-eye:focus { color: #0a5f86; }

    body.auth-login-page .auth-error-text {
        display: block;
        font-size: 12.5px;
        color: var(--auth-coral-dark);
        margin: -13px 0 16px 4px;
    }

    /* ---------- actions ---------- */
    .pl-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin: 20px 0 32px;
    }

    .pl-forgot {
        font-size: 14.5px;
        font-weight: 500;
        color: #0f6a86;
        text-decoration: none;
    }

    .pl-forgot:hover,
    .pl-forgot:focus { color: #0a5f86; text-decoration: underline; }

    body.auth-login-page .pl-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        height: 60px;
        margin: 0;
        font-size: 16.5px;
    }

    .pl-btn-arrow {
        font-size: 18px;
        line-height: 1;
        transition: transform .15s ease;
    }

    body.auth-login-page .pl-submit:hover:not(:disabled) .pl-btn-arrow { transform: translateX(3px); }

    .pl-or {
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 28px 0 24px;
        color: #8a9bb0;
        font-size: 13px;
    }

    .pl-or::before,
    .pl-or::after {
        content: '';
        flex: 1 1 auto;
        height: 1px;
        background: #e1e9ef;
    }

    .pl-register {
        margin: 0;
        text-align: center;
        font-size: 14.5px;
        color: #4d6278;
    }

    .pl-register a { color: #0f6a86; font-weight: 600; text-decoration: none; }
    .pl-register a:hover,
    .pl-register a:focus { text-decoration: underline; }

        /* Hospital-number switch: inside the card, under the Register link,
       separated by a thin divider. */
    .pl-switch-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
        padding-top: 20px;
        
    }

    body.auth-login-page .pl-switch {
        justify-content: center;
        margin: 0;
        gap: 8px;
        font-size: 13px;
        color: #5f7186;
    }

    .pl-switch input[type="checkbox"] { accent-color: #1b9299; }

    .pl-switch label {
        cursor: pointer;
        border-bottom: 1px dashed #aebccb;
    }

    .pl-switch input[type="checkbox"]:checked + label {
        color: #0f6a86;
        border-bottom-color: #0f6a86;
        font-weight: 500;
    }

    body.auth-login-page .pl-hint {
        text-align: center;
        margin: 0;
        font-size: 12px;
        color: #6f7f94;
    }

    /* ---------- login-only responsive tweaks ---------- */
    @media (min-width: 992px) and (max-width: 1199.98px) {
        .pl-title { font-size: 32px; }
    }

    @media (max-width: 991.98px) {
        .pl-brand { gap: 10px; margin-bottom: 20px; }
        .pl-brand-logo { height: 54px; }
        .pl-brand-text strong { font-size: 19px; }
        .pl-brand-text span { font-size: 15px; }
        .pl-head { margin-bottom: 24px; }
        .pl-title { font-size: 30px; }
        .pl-subtitle { font-size: 14.5px; }
        body.auth-login-page .pl-field .auth-input { height: 54px; }
        .pl-meta { margin: 16px 0 24px; }
        .pl-or { margin: 24px 0 20px; }
        .pl-switch-wrap { margin-top: 20px; padding-top: 16px; }
    }

    @media (max-width: 420px) {
        .pl-brand-logo { height: 48px; }
        .pl-title { font-size: 26px; }

        body.auth-login-page .pl-field .auth-input { height: 50px; padding-left: 46px; }
        .pl-field .pl-field-icon { left: 17px; font-size: 16px; }
        body.auth-login-page .pl-submit { height: 52px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .pl-field .pl-field-icon,
        .pl-btn-arrow { transition: none; transform: none; }
    }
@endpush

@push('scripts')
<script>
    (function () {
        const checkbox    = document.getElementById('toggleHospitalNumber');
        const switchLabel = document.querySelector('label[for="toggleHospitalNumber"]');
        const useHospital = document.getElementById('useHospital');
        const hospitalNote = document.getElementById('hospitalNote');
        const userInput   = document.getElementById('username');
        const passInput   = document.getElementById('password');

        // --- Hospital number / birthdate mode (legacy QALINGA1 toggle) ---
        checkbox.addEventListener('change', function () {
            if (checkbox.checked) {
                userInput.placeholder = 'Hospital number';
                passInput.placeholder  = 'Birthdate (MMDDYYYY)';
                hospitalNote.classList.remove('d-none');
                useHospital.value = '1';
                switchLabel.textContent = 'Sign in with username and password';
            } else {
                userInput.placeholder = 'Username or Hospital ID';
                passInput.placeholder  = 'Password';
                hospitalNote.classList.add('d-none');
                useHospital.value = '0';
                switchLabel.textContent = 'Sign in with hospital number';
            }
        });

        // Birthdate mode → digits only, max 8 (MMDDYYYY)
        passInput.addEventListener('input', function (e) {
            if (!checkbox.checked) return;
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 8);
        });

        // --- Show / hide password ---
        const eyeBtn = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('toggleIcon');

        eyeBtn.addEventListener('click', function () {
            const hidden = passInput.type === 'password';
            passInput.type = hidden ? 'text' : 'password';
            eyeIcon.classList.toggle('bi-eye', !hidden);
            eyeIcon.classList.toggle('bi-eye-slash', hidden);
            eyeBtn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            eyeBtn.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
        });

        // --- Submit loading state ---
        const form = document.getElementById('loginForm');
        const btn  = document.getElementById('loginBtn');
        const label = document.getElementById('loginBtnLabel');

        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            btn.disabled = true;
            label.textContent = 'Signing in...';
        });
    })();
</script>
@endpush