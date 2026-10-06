<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Doctor Login — QMMC Doctor Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    @vite('resources/css/doctor-dashboard.css')
</head>
<body class="doctor-login-body">
    <main class="doctor-login-card">
        <section class="doctor-login-visual" aria-label="QMMC doctor portal introduction">
            <div class="doctor-login-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Qalinga Medical Center">
                <div>
                    <strong>QMMC</strong>
                    <span>DOCTORS PORTAL</span>
                </div>
            </div>
            <div class="doctor-login-message">
                <span><i class="bi bi-camera-video-fill me-1" aria-hidden="true"></i> Telemedicine Workspace</span>
                <h1>Your patients and schedule, together.</h1>
                <p>Review every telemedicine appointment, open patient intake details, and start secure Jitsi consultations from one focused dashboard.</p>
                <img class="doctor-login-image" src="{{ asset('images/qmmc-telemedicine-banner.jpg') }}" alt="Qalinga Memorial Medical Center">
            </div>
        </section>

        <section class="doctor-login-form-panel">
            <div class="doctor-login-form-wrap">
                <span class="doctor-login-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                <h2>Welcome, Doctor</h2>
                <p>Sign in with your dedicated doctor account.</p>
                @if ($errors->any())
                    <div class="doctor-login-error" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="doctor-login-error border-success bg-success-subtle text-success" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <form action="{{ route('doctor.login.attempt') }}" method="POST">
                    @csrf

                    <div class="doctor-login-field">
                        <label for="doctorEmail">Doctor email</label>
                        <div class="doctor-login-input">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input
                                id="doctorEmail"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="doctor-login-field">
                        <label for="doctorPassword">Password</label>
                        <div class="doctor-login-input">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input
                                id="doctorPassword"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="doctor-login-submit">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        Sign in to dashboard
                    </button>
                </form>

                <a href="{{ route('auth.login') }}" class="doctor-login-back">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                    Back to unified login
                </a>
            </div>
        </section>
    </main>
</body>
</html>
