@extends('auth.guest')

@section('title', 'Change your password')
@section('description', 'Replace the default credentials issued with your hospital number.')

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
            <span>Set your password</span>
        </span>
    </div>
@endsection

@section('content')

    <p class="auth-heading">
        You are signed in with your default credentials.<br>
        Choose your own username and password to continue.
    </p>

    <form action="{{ route('password.force.store') }}" method="POST">
        @csrf

        <div class="auth-field">
            <label class="auth-label" for="username">Username</label>
            <input type="text" class="auth-input" id="username" name="username" autocomplete="username"
                   autocapitalize="off" spellcheck="false"
                   value="{{ old('username', $patient->username) }}" required>
        </div>
        @error('username') <span class="auth-error-text">{{ $message }}</span> @enderror

        <div class="auth-field">
            <label class="auth-label" for="password">New Password</label>
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

        <div class="auth-field">
            <label class="auth-label" for="password_confirmation">Confirm Password</label>
            <input type="password" class="auth-input" id="password_confirmation"
                   name="password_confirmation" autocomplete="new-password" required>
        </div>

        <div class="auth-links">
            <a href="{{ route('auth.login') }}">Signin</a>
            <span class="auth-sep">|</span>
            <a href="{{ route('password.reset') }}">Reset Password</a>
        </div>

        <button type="submit" class="auth-submit">Save</button>
    </form>

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
</script>
@endpush
