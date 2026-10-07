/* =====================================================================
       QMMC auth portal: shared split layout (login + register).
       Raw CSS only (auth.css wraps the styles stack in a <style> tag).
       Scoped to body.auth-portal, so other auth screens are untouched.
       ===================================================================== */
    body.auth-portal {
        padding: 0;
        align-items: stretch;
        background: #ecf7fa;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }

    /* auth.css gives body.auth-body.auth-registration-page its own top/bottom
       padding at the same specificity; this cancels it so the layout is full-bleed. */
    body.auth-body.auth-portal { padding: 0; }

    body.auth-portal .auth-card {
        max-width: none;
        min-height: 100vh;
        border-radius: 0;
        box-shadow: none;
        background: #ecf7fa;
        grid-template-columns: minmax(0, 1.74fr) minmax(340px, 1fr);
    }

    body.auth-portal .auth-card::before { display: none; }

    /* ---------- right pane ---------- */
    body.auth-portal .auth-pane-form {
        order: 2;
        align-items: flex-start;
        margin-left: -2px; /* hides the photo pane's subpixel edge */
        padding: 40px clamp(36px, 4.4vw, 80px) 40px 0;
        background:
            /* pale circle behind the shield, top-right */
            radial-gradient(circle 10.6vw at calc(100% - .7vw) 0, rgba(150, 212, 226, .26) 0, rgba(150, 212, 226, .26) 97%, rgba(150, 212, 226, 0) 100%),
            /* soft white glow near the seam */
            radial-gradient(520px 620px at 0% 40%, rgba(255, 255, 255, .7) 0, rgba(255, 255, 255, 0) 72%),
            /* deeper teal corner, bottom-right */
            radial-gradient(circle 16vw at calc(100% + 3vw) 108%, rgba(56, 170, 186, .2) 0, rgba(56, 170, 186, .2) 96%, rgba(56, 170, 186, 0) 100%),
            #ecf7fa;
    }

    /* Shield badge, top-right */
    body.auth-portal .auth-pane-form::before {
        content: '';
        position: absolute;
        top: 2.15vw;
        right: 2.7vw;
        width: 3.83vw;
        height: 4.73vw;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 79'%3E%3Cpath d='M32 0 L63 12 L63 45 C63 62 49 73 32 78 C15 73 1 62 1 45 L1 12 Z' fill='%23a9dbe5'/%3E%3Crect x='30' y='24' width='9' height='24' rx='2' fill='%23ffffff' fill-opacity='.85'/%3E%3Crect x='22' y='32' width='25' height='9' rx='2' fill='%23ffffff' fill-opacity='.85'/%3E%3C/svg%3E") no-repeat center / contain;
        pointer-events: none;
        z-index: 1;
    }

    /* (the old white ring is gone: the mockup has none) */
    body.auth-portal .auth-pane-form::after { display: none; }

    /* ---------- left pane (photo) ---------- */
    body.auth-portal .auth-art {
        order: 1;
        overflow: hidden;
        border-radius: 0;
        background: #0d3b52;
    }

    body.auth-portal .auth-frond,
    body.auth-portal .auth-icons,
    body.auth-portal .auth-wave,
    body.auth-portal .auth-deco,
    body.auth-portal .auth-foot { display: none; }

    .login-hero {
        position: absolute;
        inset: 0;
        z-index: 5;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 24px;
        padding: 54px 56px 64px;
        background-color: #0b3a5a;
    }

    .login-hero-media {
        position: absolute;
        inset: 0;
        display: block;
    }

    /* The photo has the curved edge built in and ends in the panel colour, so it
       is anchored to the right (meets the form pane seamlessly) and cropped from
       the left / bottom when the pane is a different shape. */
    .login-hero-img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: 100% 30%;
    }

    /* Light top scrim so the white brand text stays readable over the sky. */
    .login-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        background: linear-gradient(180deg, rgba(4, 38, 68, .3) 0%, rgba(4, 38, 68, .1) 16%, rgba(4, 38, 68, 0) 28%);
    }

    .login-hero-brand {
        position: relative;
        z-index: 3;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .login-hero-mark {
        width: 56px;
        height: 56px;
        flex: none;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(4, 32, 54, .3);
    }

    .login-hero-mark img {
        width: 36px;
        height: 36px;
        object-fit: contain;
    }

    .login-hero-divider {
        width: 1px;
        height: 44px;
        flex: none;
        background: rgba(255, 255, 255, .6);
    }

    .login-hero-text {
        line-height: 1.25;
        text-shadow: 0 1px 12px rgba(0, 30, 54, .4);
    }

    .login-hero-text strong {
        display: block;
        color: #fff;
        font-size: 20px;
        font-weight: 600;
        letter-spacing: .1px;
    }

    .login-hero-text span {
        display: block;
        color: rgba(255, 255, 255, .92);
        font-size: 15px;
    }

    .login-hero-tagline {
        position: relative;
        z-index: 3;
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 0;
        color: #fff;
        font-size: 17px;
        font-style: italic;
        letter-spacing: .3px;
        text-shadow: 0 2px 12px rgba(0, 40, 60, .5);
    }

    .login-hero-rule {
        width: 38px;
        height: 2px;
        flex: none;
        border-radius: 2px;
        background: rgba(255, 255, 255, .92);
    }

    .login-hero-dot { opacity: .8; }

    /* ---------- card ---------- */
    body.auth-portal .auth-inner {
        position: relative;
        z-index: 3;
        width: 100%;
        max-width: clamp(460px, 32.2vw, 640px);
        margin-left: auto;
        padding: 46px 45px 52px;
        background: #fff;
        border: 1px solid rgba(255, 255, 255, .9);
        border-radius: 28px;
        box-shadow: 0 24px 56px rgba(14, 82, 104, .16), 0 4px 14px rgba(14, 82, 104, .06);
    }

    body.auth-portal .auth-submit {
        border: 0;
        border-radius: 12px;
        background: linear-gradient(90deg, #1b9299 0%, #016091 100%);
        color: #fff;
        font-weight: 600;
        letter-spacing: .3px;
        box-shadow: 0 14px 26px rgba(15, 110, 140, .26);
        transition: transform .15s ease, box-shadow .15s ease, background .2s ease;
    }

    body.auth-portal .auth-submit:hover:not(:disabled) {
        background: linear-gradient(90deg, #17858c 0%, #01547f 100%);
        box-shadow: 0 18px 32px rgba(15, 110, 140, .34);
        transform: translateY(-1px);
    }

    body.auth-portal .auth-submit:active:not(:disabled) { transform: translateY(0); }

    body.auth-portal .auth-submit:focus-visible,
    body.auth-portal .auth-input:focus-visible,
    body.auth-portal a:focus-visible {
        outline: 3px solid rgba(27, 146, 153, .45);
        outline-offset: 2px;
    }

    /* ---------- narrow desktop: keep the card comfortable ---------- */
    @media (min-width: 992px) and (max-width: 1199.98px) {
        body.auth-portal .auth-pane-form { padding-right: 28px; }
        body.auth-portal .auth-inner { padding: 36px 28px 40px; border-radius: 24px; }
        .login-hero { padding: 40px 36px 48px; }
    }

    /* ---------- mobile: photo banner on top, card below ---------- */
    @media (max-width: 991.98px) {
        body.auth-portal .auth-card {
            grid-template-columns: 1fr;
            min-height: 100vh;
            background: #f2f7fb;
        }

        body.auth-portal .auth-art {
            order: 1;
            display: block;
            height: 210px;
            border-radius: 0 0 30px 30px;
        }

        .login-hero { gap: 16px; padding: 22px 22px 20px; }

        .login-hero-img { width: 130%; object-position: 0% 38%; }

        body.auth-portal .auth-pane-form::before,
        body.auth-portal .auth-pane-form::after { display: none; }

        .login-hero-mark { width: 44px; height: 44px; border-radius: 14px; }
        .login-hero-mark img { width: 28px; height: 28px; }
        .login-hero-divider { height: 34px; }
        .login-hero-brand { gap: 12px; }
        .login-hero-text strong { font-size: 15.5px; }
        .login-hero-text span { font-size: 12.5px; }
        .login-hero-tagline { gap: 10px; font-size: 13px; }
        .login-hero-rule { width: 26px; }

        body.auth-portal .auth-pane-form {
            order: 2;
            min-height: 0;
            margin-left: 0;
            justify-content: center;
            align-items: center;
            padding: 30px 18px 34px;
            background: #f2f7fb;
        }

        body.auth-portal .auth-inner {
            max-width: 540px;
            margin: 0 auto;
            padding: 34px 28px 30px;
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(13, 55, 92, .12);
        }
    }

    @media (max-width: 420px) {
        body.auth-portal .auth-art { height: 180px; }
        body.auth-portal .auth-pane-form { padding: 24px 14px 30px; }
        .login-hero { padding: 18px 16px 16px; }
        body.auth-portal .auth-inner { padding: 30px 20px 26px; border-radius: 22px; }
    }

    @media (prefers-reduced-motion: reduce) {
        body.auth-portal .auth-submit { transition: none; transform: none; }
    }