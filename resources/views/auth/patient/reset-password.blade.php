@extends('auth.guest')

@section('title', 'Reset Password')
@section('description', 'Reset your QMMC Patient Portal password using your username and birthdate.')
@section('body-class', 'auth-reset-page auth-portal')

{{-- LEFT: hospital photo panel (shared with login + register) --}}
@section('art')
    @include('auth.partials.portal-hero')
@endsection

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
            <span>Reset Password</span>
        </span>
    </div>
@endsection

@section('content')

    <p class="auth-heading">
        Verify your identity with your username (or hospital number)
        and your birthdate.
    </p>

    <form action="{{ route('password.reset.attempt') }}" method="POST">
        @csrf

        <div class="auth-field">
            <label class="visually-hidden" for="identifier">Username or hospital number</label>
            <input type="text" class="auth-input" id="identifier" name="identifier"
                   placeholder="Username or hospital number" autocomplete="username"
                   autocapitalize="off" spellcheck="false"
                   value="{{ old('identifier') }}" required>
        </div>
        @error('identifier') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field">
            <label class="visually-hidden" for="birthdate">Birthdate</label>
            <input type="text" class="auth-input" id="birthdate" name="birthdate"
                   placeholder="Birthdate (MMDDYYYY)" inputmode="numeric" maxlength="8"
                   value="{{ old('birthdate') }}" required>
        </div>
        @error('birthdate') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field">
            <label class="visually-hidden" for="password">New password</label>
            <div class="auth-input-wrap">
                <input type="password" class="auth-input has-eye" id="password" name="password"
                       placeholder="New password" autocomplete="new-password" required>
                <button class="auth-eye" type="button" data-eye="password"
                        aria-label="Show password" aria-pressed="false">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        @error('password') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field">
            <label class="visually-hidden" for="password_confirmation">Confirm new password</label>
            <input type="password" class="auth-input" id="password_confirmation"
                   name="password_confirmation" placeholder="Confirm new password"
                   autocomplete="new-password" required>
        </div>

        <div class="auth-links">
            <a href="{{ route('register') }}">Signup</a>
            <span class="auth-sep">|</span>
            <a href="{{ route('auth.login') }}">Signin</a>
        </div>

        <button type="submit" class="auth-submit">Reset</button>
    </form>

    <p class="auth-note">Default password is your birthdate <span>(MMDDYYYY)</span></p>

@endsection

@push('styles')
    {{-- Shared split layout, photo hero, card shell and button. --}}
    @include('auth.partials.portal-styles')

    /* =====================================================================
       Reset-password-only styles (presentation only)
       ===================================================================== */

    /* ---------- brand / heading ---------- */
    body.auth-reset-page .auth-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        margin: 0 0 22px;
        padding: 0;
        text-align: center;
    }

    body.auth-reset-page .auth-brand-logo { height: 58px; width: auto; display: block; }
    body.auth-reset-page .auth-brand-rule { display: none; }
    body.auth-reset-page .auth-brand-text { line-height: 1.2; }

    body.auth-reset-page .auth-brand-text strong {
        display: block;
        font-size: 24px;
        font-weight: 700;
        letter-spacing: -.3px;
        color: #0a3d66;
    }

    body.auth-reset-page .auth-brand-text span {
        display: block;
        margin-top: 6px;
        font-size: 17px;
        font-weight: 500;
        color: #4d6278;
    }

    body.auth-reset-page .auth-heading {
        max-width: 400px;
        margin: 0 auto 26px;
        text-align: center;
        font-size: 14.5px;
        line-height: 1.6;
        font-weight: 400;
        color: #4d6278;
    }

    /* ---------- fields ---------- */
    body.auth-reset-page .auth-inner .auth-field { margin-bottom: 18px; }

    body.auth-reset-page .auth-inner .auth-input {
        width: 100%;
        height: 58px;
        padding: 0 20px;
        border: 1px solid #dce7ee;
        border-radius: 14px;
        background-color: #fff;
        box-shadow: 0 1px 2px rgba(15, 70, 95, .04);
        font-size: 15.5px;
        color: #0f2b46;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    body.auth-reset-page .auth-inner .auth-input::placeholder { color: #8a9bb0; }

    body.auth-reset-page .auth-inner .auth-input:focus {
        border-color: rgba(27, 146, 153, .7);
        box-shadow: 0 0 0 4px rgba(27, 146, 153, .15);
    }

    body.auth-reset-page .auth-inner .auth-input.has-eye { padding-right: 52px; }

    body.auth-reset-page .auth-inner .auth-eye {
        right: 14px;
        width: 34px;
        height: 100%;
        color: #2b6e8a;
    }

    body.auth-reset-page .auth-inner .auth-eye:hover,
    body.auth-reset-page .auth-inner .auth-eye:focus { color: #0a5f86; }

    body.auth-reset-page .auth-inner .auth-error-text {
        display: block;
        margin: -12px 0 14px 4px;
        font-size: 12.5px;
    }

    /* ---------- actions ---------- */
    body.auth-reset-page .auth-links {
        justify-content: center;
        gap: 12px;
        margin: 6px 0 26px;
        font-size: 14.5px;
    }

    body.auth-reset-page .auth-links a {
        color: #0f6a86;
        font-weight: 600;
        text-decoration: none;
    }

    body.auth-reset-page .auth-links a:hover,
    body.auth-reset-page .auth-links a:focus { text-decoration: underline; }

    body.auth-reset-page .auth-links .auth-sep { color: #b9c6d4; }

    body.auth-reset-page .auth-inner .auth-submit {
        display: block;
        width: 100%;
        height: 58px;
        margin: 0;
        font-size: 16.5px;
    }

    body.auth-reset-page .auth-note {
        margin: 22px 0 0;
        font-size: 13px;
        text-align: center;
        color: #6f7f94;
    }

    @media (max-width: 991.98px) {
        body.auth-reset-page .auth-brand-text strong { font-size: 21px; }
        body.auth-reset-page .auth-inner .auth-input { height: 52px; }
        body.auth-reset-page .auth-inner .auth-submit { height: 52px; }
    }

    @media (prefers-reduced-motion: reduce) {
        body.auth-reset-page .auth-inner .auth-input { transition: none; }
    }
@endpush

@push('scripts')
<script>
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

    (function () {
        var field = document.getElementById('birthdate');
        if (!field) return;
        field.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 8);
        });
    })();
</script>
@endpush