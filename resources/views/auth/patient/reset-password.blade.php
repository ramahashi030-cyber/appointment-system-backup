@extends('auth.guest')

@section('title', 'Reset Password')
@section('description', 'Reset your QMMC Patient Portal password using your username and birthdate.')

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
