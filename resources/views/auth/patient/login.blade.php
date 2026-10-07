@extends('auth.guest')

@section('title', 'Login page')
@section('description', 'Sign in to the QMMC portal with your administrator or patient credentials.')
@section('body-class', 'auth-login-page')

{{-- ============ LEFT: hospital photo panel ============ --}}
@section('art')
    <div class="login-hero">
        <picture class="login-hero-media">
            <source type="image/avif" srcset="{{ asset('images/qmmc-login-hero.avif') }}">
            <source type="image/webp" srcset="{{ asset('images/qmmc-login-hero.webp') }}">
            <img class="login-hero-img" src="{{ asset('images/qmmc-login-hero.jpg') }}"
                 alt="" width="2400" height="1351" fetchpriority="high" decoding="async">
        </picture>

        <div class="login-hero-brand">
            <span class="login-hero-mark">
                <img src="{{ asset('images/logo.png') }}" alt="">
            </span>
            <span class="login-hero-text">
                <strong>Quirino Memorial</strong>
                <span>Medical Center</span>
            </span>
        </div>

        <p class="login-hero-tagline">
            <span class="login-hero-rule"></span>
           Dekalidad na Serbisyo<span class="login-hero-dot">&bull;</span> Alagang QMMC
        </p>
    </div>
@endsection

@section('brand')
    <div class="pl-brand">
        @if (file_exists(public_path('images/logo.png')))
            <img class="pl-brand-logo" src="{{ asset('images/logo.png') }}" alt="Qalinga logo">
        @endif

        <span class="pl-brand-text">
            <strong>QMMC PORTAL</strong>
            <span>Patient or administrator login</span>
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
                placeholder="Username"
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

        <p class="pl-register">Don't have an account? <a href="{{ route('register') }}">Signup</a></p>

        {{-- hospital-number / birthdate mode (same behaviour as QALINGA1 login.php) --}}
        <div class="auth-switch pl-switch">
            <input type="checkbox" id="toggleHospitalNumber">
            <label for="toggleHospitalNumber">Sign in with hospital number</label>
            <input type="hidden" name="use_hospital" id="useHospital" value="0">
        </div>

        <p class="auth-hint d-none pl-hint" id="hospitalNote">Birthdate format: MMDDYYYY</p>
    </form>

@endsection

@push('styles')
    {{-- auth.css wraps this stack in a <style> tag, so only raw CSS is pushed here.

         Patient login redesign: a full-bleed split screen with the hospital
         photograph on the left and a floating white sign-in card on the right.

         Every rule is scoped to body.auth-login-page (or the .pl-* /
         .login-hero classes this view alone renders), so the shared auth
         screens — register, reset password, verify, forced password update —
         keep the original auth.css presentation untouched.

         Layout notes:
           - .auth-card is unframed and full-bleed (100vh) on desktop, with the
             two panes swapped through `order` so the photo sits on the left.
             `order` is used instead of reordering the markup because the markup
             lives in the shared auth.guest layout.
           - The art pane keeps its rounded right edge; the card background
             matches the form pane so the curve reads as one clean sweep.
           - Nothing uses fixed offsets: the pane grows with its content and the
             footer flows beneath the card, so short or landscape viewports
             scroll instead of overlapping. --}}
    body.auth-login-page {
        padding: 0;
        align-items: stretch;
        background: #eef4f9;
    }

    /* ---------- full-bleed split layout ---------- */
    body.auth-login-page .auth-card {
        max-width: none;
        min-height: 100vh;
        border-radius: 0;
        box-shadow: none;
        background: #f4f9fc;
        grid-template-columns: minmax(0, 1.4fr) minmax(400px, 1fr);
    }

    body.auth-login-page .auth-card::before {
        display: none;
    }

    body.auth-login-page .auth-pane-form {
        order: 2;
    }

    body.auth-login-page .auth-art {
        order: 1;
        overflow: hidden;
        border-radius: 0 46px 46px 0;
        background: #0d3b52;
    }

    /* The shared decorative artwork is replaced by the photo hero. */
    body.auth-login-page .auth-frond,
    body.auth-login-page .auth-icons,
    body.auth-login-page .auth-wave,
    body.auth-login-page .auth-deco {
        display: none;
    }

    /* ---------- photo hero ---------- */
    .login-hero {
        position: absolute;
        inset: 0;
        z-index: 5;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 24px;
        padding: 44px 48px 42px;
        background-color: #0b2d47; /* only visible if the photo fails to load */
    }

    /* The photo is a real <img>: WebP is served with a JPEG fallback, and
       without the old teal wash the picture keeps its native colour. */
    .login-hero-media {
        position: absolute;
        inset: 0;
        display: block;
    }

    .login-hero-img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    /* Light teal wash from the mockup — kept softer so the photo still reads
       HD — plus neutral scrims top (brand) and bottom (tagline) for contrast. */
    .login-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        background:
            linear-gradient(180deg, rgba(5, 30, 52, .5) 0%, rgba(5, 30, 52, .15) 24%, rgba(5, 30, 52, 0) 40%),
            linear-gradient(0deg, rgba(5, 30, 52, .44) 0%, rgba(5, 30, 52, .12) 22%, rgba(5, 30, 52, 0) 38%),
            linear-gradient(28deg, rgba(14, 140, 148, .32) 0%, rgba(12, 104, 134, .26) 55%, rgba(12, 104, 134, .2) 100%);
    }

    .login-hero-brand {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .login-hero-mark {
        width: 54px;
        height: 54px;
        flex: none;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 12px 26px rgba(4, 32, 54, .32);
    }

    .login-hero-mark img {
        width: 34px;
        height: 34px;
        object-fit: contain;
    }

    .login-hero-text {
        line-height: 1.25;
    }

    .login-hero-text strong {
        display: block;
        color: #fff;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: .2px;
    }

    .login-hero-text span {
        display: block;
        color: rgba(255, 255, 255, .82);
        font-size: 13.5px;
    }

    .login-hero-tagline {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 0;
        color: #fff;
        font-size: 15.5px;
        font-style: italic;
        text-shadow: 0 2px 12px rgba(0, 0, 0, .45);
    }

    .login-hero-rule {
        width: 36px;
        height: 2px;
        flex: none;
        border-radius: 2px;
        background: rgba(255, 255, 255, .9);
    }

    .login-hero-dot {
        opacity: .75;
    }

    /* ---------- form panel ---------- */
    body.auth-login-page .auth-pane-form {
        justify-content: center;
        padding: 44px 40px 36px;
        background:
            radial-gradient(closest-side, rgba(255, 255, 255, 0) 76%, rgba(47, 184, 198, .22) 77.5%, rgba(47, 184, 198, 0) 81%) 92% 4% / 300px 300px no-repeat,
            radial-gradient(closest-side, rgba(255, 255, 255, 0) 77%, rgba(47, 184, 198, .18) 78.5%, rgba(47, 184, 198, 0) 82%) 112% 86% / 380px 380px no-repeat,
            radial-gradient(560px 320px at 118% -10%, rgba(47, 184, 198, .24) 0, rgba(47, 184, 198, 0) 70%),
            linear-gradient(180deg, #f7fbfd 0%, #edf3f9 100%);
    }

    body.auth-login-page .auth-inner {
        width: 100%;
        max-width: 540px;
        padding: 44px 48px 38px;
        background: #fff;
        border: 1px solid rgba(15, 63, 104, .07);
        border-radius: 28px;
        box-shadow: 0 30px 60px rgba(13, 55, 92, .14), 0 4px 12px rgba(13, 55, 92, .06);
    }

    /* Brand block inside the card. */
    .pl-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        text-align: center;
    }

    .pl-brand-logo {
        height: 64px;
        width: auto;
        display: block;
    }

    .pl-brand-text {
        line-height: 1.3;
    }

    .pl-brand-text strong {
        display: block;
        font-size: 17px;
        font-weight: 700;
        color: #0f3a63;
    }

    .pl-brand-text span {
        display: block;
        margin-top: 2px;
        font-size: 12.5px;
        color: #7d8aa1;
    }

    .pl-title {
        margin: 0;
        text-align: center;
        font-size: 34px;
        font-weight: 700;
        letter-spacing: -.4px;
        color: #0e3f68;
    }

    .pl-subtitle {
        margin: 8px 0 26px;
        text-align: center;
        font-size: 14.5px;
        color: #6e7f95;
    }

    /* ---------- fields ---------- */
    body.auth-login-page .pl-field {
        position: relative;
        margin-bottom: 16px;
    }

    .pl-field .pl-field-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
        font-size: 17px;
        color: #93a6bb;
        pointer-events: none;
        transition: color .15s ease;
    }

    .pl-field:focus-within .pl-field-icon {
        color: var(--auth-teal-dark);
    }

    body.auth-login-page .pl-field .auth-input {
        height: 54px;
        padding-left: 52px;
        padding-right: 20px;
        border: 1px solid #e3ebf3;
        border-radius: 14px;
        box-shadow: none;
        font-size: 14px;
    }

    body.auth-login-page .pl-field .auth-input::placeholder {
        color: #98a5b7;
    }

    body.auth-login-page .pl-field .auth-input.has-eye {
        padding-right: 50px;
    }

    body.auth-login-page .pl-field .auth-input:focus {
        border-color: rgba(47, 184, 198, .65);
        box-shadow: 0 0 0 4px rgba(47, 184, 198, .16);
    }

    body.auth-login-page .pl-field .auth-input.is-invalid {
        border-color: var(--auth-coral);
    }

    .pl-field .auth-eye {
        right: 16px;
        width: 34px;
        color: #93a6bb;
    }

    .pl-field .auth-eye:hover,
    .pl-field .auth-eye:focus {
        color: var(--auth-teal-dark);
    }

    body.auth-login-page .auth-error-text {
        display: block;
        font-size: 12px;
        color: var(--auth-coral-dark);
        margin: -6px 0 14px 4px;
    }

    /* ---------- actions ---------- */
    .pl-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin: 2px 0 18px;
    }

    .pl-forgot {
        font-size: 13px;
        font-weight: 500;
        color: #12657d;
        text-decoration: none;
    }

    .pl-forgot:hover,
    .pl-forgot:focus {
        color: #0e5a66;
        text-decoration: underline;
    }

    body.auth-login-page .pl-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        height: 54px;
        margin: 4px 0 0;
        border: 0;
        border-radius: 14px;
        background: linear-gradient(90deg, #17838f 0%, #14587a 52%, #123a63 100%);
        color: #fff;
        font-size: 15.5px;
        font-weight: 600;
        letter-spacing: .3px;
        box-shadow: 0 16px 30px rgba(18, 76, 108, .28);
        transition: transform .15s ease, box-shadow .15s ease, background .2s ease;
    }

    body.auth-login-page .pl-submit:hover:not(:disabled) {
        background: linear-gradient(90deg, #12757f 0%, #104c69 52%, #0f3053 100%);
        box-shadow: 0 20px 34px rgba(18, 76, 108, .34);
        transform: translateY(-1px);
    }

    body.auth-login-page .pl-submit:active:not(:disabled) {
        transform: translateY(0);
    }

    .pl-btn-arrow {
        font-size: 17px;
        line-height: 1;
    }

    .pl-or {
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 18px 0 16px;
        color: #9aa7b8;
        font-size: 12.5px;
    }

    .pl-or::before,
    .pl-or::after {
        content: '';
        flex: 1 1 auto;
        height: 1px;
        background: #e6ecf3;
    }

    .pl-register {
        margin: 0;
        text-align: center;
        font-size: 13.5px;
        color: #6e7f95;
    }

    .pl-register a {
        color: #12657d;
        font-weight: 600;
        text-decoration: none;
    }

    .pl-register a:hover,
    .pl-register a:focus {
        text-decoration: underline;
    }

    /* Hospital number / birthdate switch, kept deliberately quiet. */
    body.auth-login-page .pl-switch {
        justify-content: center;
        margin: 18px 0 0;
        gap: 8px;
        font-size: 12.5px;
        color: #7d8aa1;
    }

    .pl-switch label {
        cursor: pointer;
        border-bottom: 1px dashed #b9c6d4;
    }

    .pl-switch input[type="checkbox"]:checked + label {
        color: #12657d;
        border-bottom-color: #12657d;
        font-weight: 500;
    }

    body.auth-login-page .pl-hint {
        text-align: center;
        margin: 10px 0 0;
        font-size: 11.5px;
        color: #8b90a3;
    }

    body.auth-login-page .auth-foot {
        position: static;
        margin-top: 20px;
        text-align: center;
        font-size: 11px;
        color: #93a2b5;
    }

    /* ---------- desktop: photo panels side by side ---------- */
    @media (min-width: 992px) and (max-width: 1279.98px) {
        body.auth-login-page .auth-card {
            grid-template-columns: minmax(0, 1.15fr) minmax(440px, 1fr);
        }

        body.auth-login-page .auth-pane-form {
            padding: 40px 32px 32px;
        }
    }

    /* ---------- mobile: photo hero on top, card below ---------- */
    @media (max-width: 991.98px) {
        body.auth-login-page .auth-card {
            grid-template-columns: 1fr;
            min-height: 100vh;
            background: #f2f7fb;
        }

        body.auth-login-page .auth-art {
            order: 1;
            display: block;
            height: 210px;
            border-radius: 0 0 30px 30px;
        }

        .login-hero {
            gap: 16px;
            padding: 22px 22px 20px;
        }

        .login-hero-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
        }

        .login-hero-mark img {
            width: 28px;
            height: 28px;
        }

        .login-hero-text strong {
            font-size: 15px;
        }

        .login-hero-text span {
            font-size: 12px;
        }

        .login-hero-tagline {
            gap: 10px;
            font-size: 13px;
        }

        .login-hero-rule {
            width: 26px;
        }

        body.auth-login-page .auth-pane-form {
            order: 2;
            min-height: 0;
            justify-content: center;
            padding: 30px 18px 34px;
        }

        body.auth-login-page .auth-inner {
            max-width: 540px;
            margin: 0 auto;
            padding: 34px 28px 30px;
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(13, 55, 92, .12);
        }

        body.auth-login-page .auth-foot {
            position: static;
            margin-top: 22px;
        }

        .pl-brand {
            gap: 10px;
            margin-bottom: 16px;
        }

        .pl-brand-logo {
            height: 54px;
        }

        .pl-title {
            font-size: 29px;
        }

        .pl-subtitle {
            margin-bottom: 20px;
        }

        body.auth-login-page .pl-field .auth-input {
            height: 52px;
        }
    }

    @media (max-width: 420px) {
        body.auth-login-page .auth-art {
            height: 180px;
        }

        body.auth-login-page .auth-pane-form {
            padding: 24px 14px 30px;
        }

        .login-hero {
            padding: 18px 16px 16px;
        }

        body.auth-login-page .auth-inner {
            padding: 30px 20px 26px;
            border-radius: 22px;
        }

        .pl-brand-logo {
            height: 48px;
        }

        .pl-title {
            font-size: 26px;
        }

        body.auth-login-page .pl-field .auth-input {
            height: 48px;
            padding-left: 46px;
        }

        .pl-field .pl-field-icon {
            left: 17px;
            font-size: 16px;
        }

        body.auth-login-page .pl-submit {
            height: 50px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        body.auth-login-page .pl-submit,
        .pl-field .pl-field-icon {
            transition: none;
            transform: none;
        }
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
                userInput.placeholder = 'Username';
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
