@extends('auth.guest')

@section('title', 'Verify OTP')
@section('description', 'Enter the OTP sent to your cellphone number to activate your account.')

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
            <span>OTP verification</span>
        </span>
    </div>
@endsection

@section('content')

    <p class="auth-heading">
        A 6-digit code was sent to<br>
        <strong class="text-dark">{{ $contact }}</strong>
    </p>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if (! $sent)
        <div class="alert alert-warning" role="alert">
            The SMS gateway could not be reached. Contact the clinic for your code, or check
            <code>storage/logs/laravel.log</code>.
        </div>
    @endif

    <form action="{{ route('register.verify.attempt') }}" method="POST">
        @csrf
        <input type="hidden" name="contact" value="{{ $contact }}">

        <div class="auth-field">
            <label class="visually-hidden" for="otp">OTP</label>
            <input type="text" class="auth-input text-center" id="otp" name="otp"
                   placeholder="Enter 6-digit OTP" maxlength="6" inputmode="numeric"
                   autocomplete="one-time-code" pattern="[0-9]{6}" required
                   style="letter-spacing: 6px; font-size: 17px;">
        </div>
        @error('otp') <span class="auth-error-text">{{ $message }}</span> @enderror

        <button type="submit" class="auth-submit">Verify</button>
    </form>

    <form action="{{ route('register.resend') }}" method="POST">
        @csrf
        <input type="hidden" name="contact" value="{{ $contact }}">
        <button type="submit" class="auth-linkbtn">Resend OTP</button>
    </form>

    <p class="auth-note">
        <span id="countdown">Time remaining: {{ $window }}s</span>
    </p>

    <p class="auth-alt">
        Wrong number? <a href="{{ route('register') }}">Register again</a>
    </p>

@endsection

@push('scripts')
<script>
    (function () {
        var seconds = {{ $window }};
        var el = document.getElementById('countdown');
        if (!el) return;

        var timer = setInterval(function () {
            seconds--;
            el.textContent = 'Time remaining: ' + seconds + 's';

            if (seconds <= 0) {
                clearInterval(timer);
                el.textContent = 'OTP expired. Please register again.';
            }
        }, 1000);
    })();
</script>
@endpush
