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

        {{-- Light panel fill that draws the curved boundary over the photo.
             The clip-path polygon follows the arc measured from the mockup and
             scales with the pane, so the seam with the form pane stays invisible. --}}
        <div class="login-curve" aria-hidden="true"></div>

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
    {{-- auth.css wraps this stack in a <style> tag, so only raw CSS is pushed here.

         Patient login, rebuilt to match the approved mockup:
           - 63.5% photo pane whose right boundary is a gentle arc, drawn by
             .login-curve (clip-path polygon over the light panel colour).
           - Light teal panel (#ecf7fa) with a white ring arc, corner washes and
             the teal shield badge, all via scoped pseudo-elements.
           - White card (540px, radius 24) right-aligned in the form pane with
             the measured internal rhythm: 60px inputs, 59px gradient button.
           - The hospital-number switch sits absolutely below the card.

         Every rule is scoped to body.auth-login-page (or the .pl-* /
         .login-hero classes this view alone renders), so register, reset,
         verify and forced-password screens keep auth.css untouched. --}}
    body.auth-login-page {
        padding: 0;
        align-items: stretch;
        background: #ecf7fa;
    }

    /* ---------- full-bleed split layout ---------- */
    body.auth-login-page .auth-card {
        max-width: none;
        min-height: 100vh;
        border-radius: 0;
        box-shadow: none;
        background: #ecf7fa;
        grid-template-columns: minmax(0, 1.74fr) minmax(340px, 1fr);
    }

    body.auth-login-page .auth-card::before {
        display: none;
    }

    body.auth-login-page .auth-pane-form {
        order: 2;
        align-items: flex-start;
        /* -2px: reach back over the photo pane's subpixel right edge so no
           hairline of photo shows through at the grid boundary. */
        margin-left: -2px;
        padding: 40px clamp(36px, 4.4vw, 80px) 40px 0;
        background:
            /* faint teal wash behind the shield, top-right corner */
            radial-gradient(360px 260px at 100% -4%, rgba(56, 180, 196, .07) 0, rgba(56, 180, 196, 0) 75%),
            /* soft white glow at mid-right */
            radial-gradient(430px 470px at 100% 47%, rgba(255, 255, 255, .55) 0, rgba(255, 255, 255, 0) 72%),
            /* deeper teal corner, bottom-right */
            radial-gradient(330px 260px at 103% 106%, rgba(56, 170, 186, .22) 0, rgba(56, 170, 186, 0) 72%),
            #ecf7fa;
    }

    /* Teal shield badge, top-right — drawn from the mockup measurements. */
    body.auth-login-page .auth-pane-form::before {
        content: '';
        position: absolute;
        top: 2.15vw;
        right: 2.7vw;
        width: 3.83vw;
        height: 4.73vw;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 79'%3E%3Cpath d='M32 0 L63 12 L63 45 C63 62 49 73 32 78 C15 73 1 62 1 45 L1 12 Z' fill='%23a4d9e2'/%3E%3Crect x='30' y='24' width='9' height='24' rx='2' fill='%23ffffff' fill-opacity='.8'/%3E%3Crect x='22' y='32' width='25' height='9' rx='2' fill='%23ffffff' fill-opacity='.8'/%3E%3C/svg%3E") no-repeat center / contain;
        pointer-events: none;
        z-index: 1;
    }

    /* Large white ring — centre sits at 87.26vw / 27.69vw of the viewport, so
       the copy in this pane is offset by the pane start (63.5vw). */
    body.auth-login-page .auth-pane-form::after {
        content: '';
        position: absolute;
        left: calc(23.76vw + 2px);
        top: 27.69vw;
        width: 54.9vw;
        height: 54.9vw;
        transform: translate(-50%, -50%);
        box-sizing: border-box;
        border: .85vw solid rgba(255, 255, 255, .85);
        border-radius: 50%;
        pointer-events: none;
        z-index: 0;
    }

    body.auth-login-page .auth-art {
        order: 1;
        overflow: hidden;
        border-radius: 0;
        background: #0d3b52;
    }

    /* The shared decorative artwork is replaced by the photo hero. */
    body.auth-login-page .auth-frond,
    body.auth-login-page .auth-icons,
    body.auth-login-page .auth-wave,
    body.auth-login-page .auth-deco,
    body.auth-login-page .auth-foot {
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
        padding: 54px 56px 70px;
        background-color: #0b2d47; /* only visible if the photo fails to load */
    }

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
        object-position: 8% center;
    }

    /* Mockup tint: flat teal wash (rgba(26,150,156,.33)) plus neutral scrims
       top (brand) and bottom (tagline) for contrast. */
    .login-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        background:
            linear-gradient(rgba(0, 45, 40, .14), rgba(0, 45, 40, .14)),
            linear-gradient(180deg, rgba(5, 30, 52, .42) 0%, rgba(5, 30, 52, .12) 22%, rgba(5, 30, 52, 0) 38%),
            linear-gradient(0deg, rgba(5, 30, 52, .4) 0%, rgba(5, 30, 52, .1) 20%, rgba(5, 30, 52, 0) 36%),
            rgba(26, 150, 156, .33);
    }

    /* The curved boundary: the light panel colour clipped to the arc measured
       from the mockup (x runs from the pane edge at the top to ~83% at the
       bottom). Percentages keep it proportional at every desktop width. */
    .login-curve {
        position: absolute;
        inset: 0;
        z-index: 2;
        pointer-events: none;
        background: #ecf7fa;
        clip-path: polygon(
            100% 0%,
            99.34% 5.3%,
            98.59% 8.5%,
            98.3% 11.7%,
            97.65% 17%,
            96.61% 22.8%,
            96.7% 25.5%,
            94.44% 49.2%,
            93.79% 58%,
            92.84% 66%,
            91.53% 75%,
            89.74% 83%,
            87.2% 91%,
            82.86% 100%,
            100% 100%
        );
    }

    .login-hero-brand {
        position: relative;
        z-index: 3;
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
        z-index: 3;
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 0;
        color: #fff;
        font-size: 16px;
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

    /* ---------- form card ---------- */
    body.auth-login-page .auth-inner {
        position: relative;
        z-index: 3;
        width: 100%;
        max-width: 540px;
        margin-left: auto;
        padding: 46px 45px 56px;
        background: #fff;
        border: 0;
        border-radius: 24px;
        box-shadow: 0 16px 34px rgba(20, 86, 105, .18), 0 3px 10px rgba(20, 86, 105, .07);
    }

    /* Brand block inside the card. */
    .pl-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 13px;
        margin-bottom: 29px;
        text-align: center;
    }

    .pl-brand-logo {
        height: 58px;
        width: auto;
        display: block;
    }

    .pl-brand-text {
        line-height: 1.2;
    }

    .pl-brand-text strong {
        display: block;
        font-size: 21px;
        font-weight: 700;
        color: #02345b;
    }

    .pl-brand-text span {
        display: block;
        margin-top: 1px;
        font-size: 18px;
        font-weight: 500;
        color: #02345b;
    }

    .pl-head {
        margin-bottom: 32px;
    }

    .pl-title {
        margin: 0;
        text-align: center;
        font-size: 40px;
        line-height: 1.15;
        font-weight: 600;
        letter-spacing: -1.5px;
        color: #0b4570;
    }

    .pl-subtitle {
        margin: 14px 0 0;
        text-align: center;
        font-size: 17px;
        color: #5a6c80;
    }

    /* ---------- fields ---------- */
    body.auth-login-page .pl-field {
        position: relative;
        margin-bottom: 21px;
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
        height: 61px;
        padding-left: 50px;
        padding-right: 20px;
        border: 1px solid #e5ecf0;
        border-radius: 12px;
        box-shadow: none;
        font-size: 15px;
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
        margin: -14px 0 16px 4px;
    }

    /* ---------- actions ---------- */
    .pl-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin: 21px 0 37px;
    }

    .pl-forgot {
        font-size: 14.5px;
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
        height: 59px;
        margin: 0;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(90deg, #1b9299 0%, #016091 100%);
        color: #fff;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: .3px;
        box-shadow: 0 14px 26px rgba(15, 110, 140, .26);
        transition: transform .15s ease, box-shadow .15s ease, background .2s ease;
    }

    body.auth-login-page .pl-submit:hover:not(:disabled) {
        background: linear-gradient(90deg, #17858c 0%, #01547f 100%);
        box-shadow: 0 18px 30px rgba(15, 110, 140, .32);
        transform: translateY(-1px);
    }

    body.auth-login-page .pl-submit:active:not(:disabled) {
        transform: translateY(0);
    }

    .pl-btn-arrow {
        font-size: 18px;
        line-height: 1;
    }

    .pl-or {
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 30px 0 26px;
        color: #9aa7b8;
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
        color: #5a6c80;
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

    /* Hospital number / birthdate switch: sits just below the card on desktop
       (absolute, so the card keeps the mockup's height), rejoining the flow on
       small screens. */
    .pl-switch-wrap {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 18px);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    body.auth-login-page .pl-switch {
        justify-content: center;
        margin: 0;
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
        margin: 0;
        font-size: 11.5px;
        color: #8b90a3;
    }

    /* ---------- mobile: photo banner on top, card below ---------- */
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

        .login-curve,
        body.auth-login-page .auth-pane-form::before,
        body.auth-login-page .auth-pane-form::after {
            display: none;
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
            margin-left: 0;
            justify-content: center;
            align-items: center;
            padding: 30px 18px 34px;
            background: #f2f7fb;
        }

        body.auth-login-page .auth-inner {
            max-width: 540px;
            margin: 0 auto;
            padding: 34px 28px 30px;
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(13, 55, 92, .12);
        }

        .pl-brand {
            gap: 10px;
            margin-bottom: 20px;
        }

        .pl-brand-logo {
            height: 54px;
        }

        .pl-brand-text strong {
            font-size: 19px;
        }

        .pl-brand-text span {
            font-size: 15px;
        }

        .pl-head {
            margin-bottom: 24px;
        }

        .pl-title {
            font-size: 30px;
        }

        .pl-subtitle {
            font-size: 14.5px;
        }

        body.auth-login-page .pl-field .auth-input {
            height: 52px;
        }

        .pl-meta {
            margin: 18px 0 26px;
        }

        .pl-or {
            margin: 26px 0 20px;
        }

        .pl-switch-wrap {
            position: static;
            margin-top: 20px;
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
