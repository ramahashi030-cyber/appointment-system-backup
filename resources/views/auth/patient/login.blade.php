@extends('auth.guest')

@section('title', 'Login page')
@section('description', 'Sign in to the QMMC portal with your administrator or patient credentials.')
@section('body-class', 'auth-login-page')

@section('brand')
    <div class="auth-brand">
        @if (file_exists(public_path('images/logo.png')))
            <img class="auth-brand-logo" src="{{ asset('images/logo.png') }}" alt="Qalinga logo">
        @else
            <span class="auth-brand-wordmark">Qalinga</span>
        @endif

        <span class="auth-brand-rule"></span>

        <span class="auth-brand-text">
            <strong>QMMC PORTAL</strong>
            <span>Patient or administrator login</span>
        </span>
    </div>
@endsection

@section('content')

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form action="{{ route('login.attempt') }}" method="POST" id="loginForm" novalidate>
        @csrf

        {{-- hospital-number / birthdate mode (same behaviour as QALINGA1 login.php) --}}
        <div class="auth-switch">
            <input type="checkbox" id="toggleHospitalNumber">
            <label for="toggleHospitalNumber">Sign in with hospital number</label>
            <input type="hidden" name="use_hospital" id="useHospital" value="0">
        </div>

        <p class="auth-hint d-none" id="hospitalNote">Birthdate format: MMDDYYYY</p>

        <div class="auth-field">
            <label class="visually-hidden" for="username">Username</label>
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

        <div class="auth-field">
            <label class="visually-hidden" for="password">Password</label>
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

        <button type="submit" class="auth-submit" id="loginBtn">
            <span id="loginBtnLabel">Login</span>
        </button>
        
        <div class="auth-links">
            <a href="{{ route('register') }}">Signup</a>
            <span class="auth-sep">|</span>
            <a href="{{ route('password.reset') }}">Reset Password</a>
        </div>
    </form>

@endsection

@push('styles')
    {{-- auth.css wraps this stack in a <style> tag, so only raw CSS is pushed here.

         Mobile login: centre the form in the available height and keep the
         copyright on the page footer instead of under the form.

         Below 992px auth.css sets .auth-pane-form to justify-content:flex-start,
         which is what leaves the form stuck against the top of the screen, and it
         drops .auth-foot to position:static so the copyright collapses underneath
         the form. Both are corrected for the login page only. Desktop is left
         alone because these rules sit inside the same max-width query, so the
         >991.98px layout still uses the original auth.css values.

         The footer is absolutely positioned exactly as it already is on desktop,
         and the pane reserves a matching bottom padding band, so the two can
         never collide: when the form is taller than the viewport the pane grows
         (min-height, not a fixed height) and carries the footer down with it.
         Nothing here uses fixed offsets, so short and landscape viewports simply
         scroll instead of overlapping. --}}
    @media (max-width: 991.98px) {
        .auth-login-page .auth-pane-form {
            justify-content: center;
            padding-bottom: 104px;
        }

        .auth-login-page .auth-foot {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 30px;
            margin-top: 0;
            padding: 0 18px;
        }
    }
@endpush

@push('scripts')
<script>
    (function () {
        const checkbox   = document.getElementById('toggleHospitalNumber');
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
            } else {
                userInput.placeholder = 'Username';
                passInput.placeholder  = 'Password';
                hospitalNote.classList.add('d-none');
                useHospital.value = '0';
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
