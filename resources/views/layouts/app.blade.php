<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Telemedicine') — QMMC Patient Appointment System</title>

    {{-- Bootstrap (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite([
        'resources/css/app.css',
        'resources/css/patient-dashboard.css',
        'resources/css/patient/pages.css',
        'resources/js/app.js',
    ])

    <style>
        @yield('styles')
    </style>
</head>
<body class="@yield('body-class')">

    @hasSection('dashboard-sidebar')
        <div class="patient-dashboard-shell">
            @yield('dashboard-sidebar')

            <div class="patient-dashboard-page">
                @yield('dashboard-header')

                <main class="patient-dashboard-main">
                    @include('partials.flash')

                    @yield('content')
                </main>
            </div>
        </div>
    @else
        {{-- Menu set follows the signed-in user's role — see partials/navbar.blade.php --}}
        @include('partials.navbar')

        <main class="page-wrap pt-4">
            <div class="container-fluid px-3 px-md-4">

                @include('partials.flash')

                @yield('content')

            </div>
        </main>
    @endif

    {{-- Bootstrap JS (CDN) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')

</body>
</html>
