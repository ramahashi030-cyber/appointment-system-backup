{{-- Shared photo hero for the login + register screens.
     The wide drone photo already contains the curved edge, so no overlay is needed.
     Logo and tagline are live HTML (unchanged text). --}}
<div class="login-hero">
    <picture class="login-hero-media">
        <source type="image/avif" srcset="{{ asset('images/qmmc-hero-wide.avif') }}">
        <source type="image/webp" srcset="{{ asset('images/qmmc-hero-wide.webp') }}">
        <img class="login-hero-img" src="{{ asset('images/qmmc-hero-wide.jpg') }}"
             alt="" width="2144" height="1882" fetchpriority="high" decoding="async">
    </picture>

    <div class="login-hero-brand">
        <span class="login-hero-mark">
            <img src="{{ asset('images/logo.png') }}" alt="">
        </span>
        <span class="login-hero-divider" aria-hidden="true"></span>
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