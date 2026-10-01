<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', 'Sign in to the QMMC portal to manage appointments, telemedicine, records, and results.')">

    <title>@yield('title', 'Login page') — @yield('portal-name', 'QMMC Portal')</title>

    {{-- Bootstrap 5 (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @stack('head')
    @vite([
        'resources/css/auth.css',
        'resources/js/app.js',
    ])

    <style>
        @stack('styles')
    </style>
</head>

<body class="auth-body @yield('body-class')">

    {{-- reusable shapes --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="gCoral" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#ff7f45"/>
                <stop offset="100%" stop-color="#ffb877"/>
            </linearGradient>
            <linearGradient id="gCoral2" x1="1" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#ff9a52"/>
                <stop offset="100%" stop-color="#ff6f4d"/>
            </linearGradient>
            <linearGradient id="gLeafBlue" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#3fc7d6"/>
                <stop offset="100%" stop-color="#7fe0e8"/>
            </linearGradient>
            <linearGradient id="gLeafBlue2" x1="1" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#1f8f9b"/>
                <stop offset="100%" stop-color="#49b6c4"/>
            </linearGradient>
            <linearGradient id="gHeart" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#3fd0f2"/>
                <stop offset="100%" stop-color="#1e79dd"/>
            </linearGradient>

            {{-- leaf outline (filled) and veins (stroked) are kept separate so the
                 veins can be drawn in a lighter tone instead of blending into the fill --}}
            <path id="qLeaf"
                  d="M60 4 C 92 56, 116 132, 60 236 C 4 132, 28 56, 60 4 Z"/>
            <path id="qLeafVeins" fill="none" stroke-linecap="round"
                  d="M60 24 L60 222
                     M60 70 L86 96 M60 70 L34 96
                     M60 118 L90 150 M60 118 L30 150
                     M60 166 L82 196 M60 166 L38 196"/>

            {{-- single blade, base at (0,0), tip at (120,0) — used for the coral frond --}}
            <path id="qBlade" d="M0 0 C 26 -24, 78 -30, 120 0 C 78 30, 26 24, 0 0 Z"/>
        </defs>
    </svg>

    <main class="auth-card">

        {{-- ============ LEFT: form ============ --}}
        <section class="auth-pane auth-pane-form">

            <svg class="auth-frond" viewBox="0 0 220 185" aria-hidden="true">
                {{-- stem / rachis, drawn first so the blades sit on top of it --}}
                <path d="M-14 -4 C 0 34, 8 76, 12 124"
                      fill="none" stroke="url(#gCoral2)" stroke-width="7" stroke-linecap="round"/>

                <g>
                    <g transform="translate(-16 2) rotate(15) scale(1.18)">
                        <use href="#qBlade" fill="url(#gCoral)" stroke="url(#gCoral)" stroke-width="5"/>
                    </g>
                    <g transform="translate(-12 26) rotate(42) scale(1.02)">
                        <use href="#qBlade" fill="url(#gCoral2)" stroke="url(#gCoral2)" stroke-width="5"/>
                    </g>
                    <g transform="translate(-6 54) rotate(68) scale(.92)">
                        <use href="#qBlade" fill="url(#gCoral)" stroke="url(#gCoral)" stroke-width="5"/>
                    </g>
                    <g transform="translate(6 88) rotate(94) scale(.78)">
                        <use href="#qBlade" fill="url(#gCoral2)" stroke="url(#gCoral2)" stroke-width="5"/>
                    </g>
                </g>
            </svg>

            <div class="auth-inner">

                @yield('brand')

                @include('partials.flash')

                @yield('content')
            </div>

            <p class="auth-foot">@yield('portal-footer', '© '.date('Y').' Qalinga App · QMMC Portal')</p>
        </section>

        {{-- ============ RIGHT: artwork ============ --}}
        <aside class="auth-art" aria-hidden="true">

            <div class="auth-icons">
                @php
                    $authIcons = [
                        'bi-heart-pulse', 'bi-thermometer-half', 'bi-capsule', 'bi-bandaid',
                        'bi-eye', 'bi-emoji-smile', 'bi-clock-history', 'bi-clipboard2-pulse',
                        'bi-droplet', 'bi-activity', 'bi-journal-medical', 'bi-pill',
                        'bi-stethoscope', 'bi-virus2', 'bi-hospital','bi-person-badge',
                        'bi-calendar2-check', 'bi-gender-female', 'bi-smile', 'bi-train-front',
                    ];
                @endphp
                @for ($i = 0; $i < 45; $i++)
                    <i class="bi {{ $authIcons[$i % count($authIcons)] }}"></i>
                @endfor
            </div>

            <svg class="auth-wave" viewBox="0 0 340 900" preserveAspectRatio="none" aria-hidden="true">
                <path fill="#1d3f70" opacity=".85"
                      d="M74 0 C 24 66, -18 138, -12 244 C -6 348, 116 350, 116 440
                         C 116 528, 22 566, 14 656 C 6 748, 62 826, 96 900
                         L 340 900 L 340 0 Z"/>
                <path fill="#0e1c33"
                      d="M118 0 C 72 62, 28 134, 38 240 C 48 344, 158 348, 158 438
                         C 158 526, 66 564, 58 654 C 50 746, 106 824, 142 900
                         L 340 900 L 340 0 Z"/>
            </svg>

            {{-- tropical leaves --}}
            <svg class="auth-deco auth-leaf-top" viewBox="0 0 120 240">
                <use href="#qLeaf" fill="url(#gLeafBlue)"/>
                <use href="#qLeafVeins" stroke="#eafcff" stroke-width="3.5" opacity=".55"/>
            </svg>
            <svg class="auth-deco auth-leaf-top-2" viewBox="0 0 120 240">
                <use href="#qLeaf" fill="url(#gLeafBlue2)"/>
                <use href="#qLeafVeins" stroke="#eafcff" stroke-width="3.5" opacity=".5"/>
            </svg>
            <svg class="auth-deco auth-leaf-right" viewBox="0 0 120 240">
                <use href="#qLeaf" fill="url(#gLeafBlue2)"/>
                <use href="#qLeafVeins" stroke="#eafcff" stroke-width="3.5" opacity=".5"/>
            </svg>
            <svg class="auth-deco auth-leaf-bottom" viewBox="0 0 120 240">
                <use href="#qLeaf" fill="url(#gLeafBlue)"/>
                <use href="#qLeafVeins" stroke="#eafcff" stroke-width="3.5" opacity=".55"/>
            </svg>
            <svg class="auth-deco auth-leaf-bottom-2" viewBox="0 0 120 240">
                <use href="#qLeaf" fill="url(#gLeafBlue2)"/>
                <use href="#qLeafVeins" stroke="#eafcff" stroke-width="3.5" opacity=".55"/>
            </svg>

            {{-- heart with network mesh --}}
            <svg class="auth-deco auth-heart" viewBox="0 0 200 184">
                <defs>
                    <clipPath id="heartClip">
                        <path d="M100 178 C 30 126, 4 88, 4 54 C 4 22, 27 4, 52 4 C 73 4, 91 17, 100 36
                                 C 109 17, 127 4, 148 4 C 173 4, 196 22, 196 54 C 196 88, 170 126, 100 178 Z"/>
                    </clipPath>
                </defs>
                <path fill="url(#gHeart)"
                      d="M100 178 C 30 126, 4 88, 4 54 C 4 22, 27 4, 52 4 C 73 4, 91 17, 100 36
                         C 109 17, 127 4, 148 4 C 173 4, 196 22, 196 54 C 196 88, 170 126, 100 178 Z"/>
                <g clip-path="url(#heartClip)" stroke="#eaf7ff" stroke-width="1.4" fill="#eaf7ff" opacity=".75">
                    <path d="M20 40 L70 66 L120 34 L176 70 M70 66 L58 118 L112 140 L162 108 L176 70
                             M112 140 L96 174 M58 118 L20 96 M120 34 L146 14 M112 140 L150 150" fill="none"/>
                    <circle cx="20" cy="40" r="3.4"/><circle cx="70" cy="66" r="4"/>
                    <circle cx="120" cy="34" r="3.6"/><circle cx="176" cy="70" r="3.4"/>
                    <circle cx="58" cy="118" r="3.6"/><circle cx="112" cy="140" r="4.2"/>
                    <circle cx="162" cy="108" r="3.4"/><circle cx="96" cy="174" r="3.2"/>
                    <circle cx="20" cy="96" r="3"/><circle cx="146" cy="14" r="3"/>
                    <circle cx="150" cy="150" r="3.2"/>
                </g>
            </svg>

            {{-- stethoscope --}}
            <svg class="auth-deco auth-steth" viewBox="0 0 300 400">
                <g fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <!-- ear tips -->
                    <rect x="70" y="6" width="26" height="46" rx="13" fill="#f2f6ff" stroke="#cdd9f7" stroke-width="4"/>
                    <rect x="204" y="16" width="26" height="46" rx="13" fill="#f2f6ff" stroke="#cdd9f7" stroke-width="4"/>
                    <!-- upper (white) tubing -->
                    <path d="M84 50 C 74 96, 96 124, 126 148" stroke="#e9efff" stroke-width="15"/>
                    <path d="M217 60 C 232 112, 198 138, 168 164" stroke="#e9efff" stroke-width="15"/>
                    <!-- blue main tube -->
                    <path d="M126 148 C 152 176, 170 196, 172 236" stroke="#2f7bff" stroke-width="19"/>
                    <path d="M168 164 C 176 190, 176 212, 172 236" stroke="#2f7bff" stroke-width="19"/>
                    <!-- tube curving down into the chest piece -->
                    <path d="M172 236 C 178 306, 152 356, 112 352 C 76 348, 62 312, 84 290"
                          stroke="#2f7bff" stroke-width="19"/>
                </g>
                <!-- chest piece -->
                <circle cx="84" cy="286" r="34" fill="#f4f8ff" stroke="#c7d5f5" stroke-width="9"/>
                <circle cx="84" cy="286" r="13" fill="#dbe5fb"/>
            </svg>
        </aside>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
