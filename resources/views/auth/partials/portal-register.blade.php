/* =====================================================================
       Register-only styles (loaded after portal-styles)
       Only presentation: field ids, names, scripts and the OTP modal logic
       are untouched.
       ===================================================================== */

    @media (min-width: 1200px) {
        body.auth-registration-page .auth-inner {
            max-width: 620px;
            padding: 42px 46px 46px;
        }
    }

    /* ---------- brand / heading ---------- */
    body.auth-registration-page .auth-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        margin: 0 0 26px;
        text-align: center;
    }

    body.auth-registration-page .auth-brand-logo { height: 56px; width: auto; display: block; }
    body.auth-registration-page .auth-brand-rule { display: none; }
    body.auth-registration-page .auth-brand-text { line-height: 1.2; }

    body.auth-registration-page .auth-brand-text strong {
        display: block;
        font-size: 27px;
        font-weight: 700;
        letter-spacing: -.4px;
        color: #0a3d66;
    }

    body.auth-registration-page .auth-brand-text span {
        display: block;
        margin-top: 8px;
        font-size: 15.5px;
        font-weight: 400;
        color: #4d6278;
    }

    /* ---------- fields (card only; the OTP modal keeps its own look) ---------- */
    body.auth-registration-page .auth-inner .auth-field { margin-bottom: 18px; }

    body.auth-registration-page .auth-inner .auth-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 16px;
    }

    body.auth-registration-page .auth-inner .auth-label {
        display: block;
        margin: 0 0 7px 2px;
        font-size: 13px;
        font-weight: 600;
        color: #35506a;
    }

    body.auth-registration-page .auth-inner .auth-label .fw-normal { color: #7a8ca0; }

    body.auth-registration-page .auth-inner .auth-input {
        width: 100%;
        height: 54px;
        padding: 0 18px;
        border: 1px solid #dce7ee;
        border-radius: 13px;
        background-color: #fff;
        box-shadow: 0 1px 2px rgba(15, 70, 95, .04);
        font-size: 15px;
        color: #0f2b46;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    body.auth-registration-page .auth-inner .auth-input::placeholder { color: #8a9bb0; }

    body.auth-registration-page .auth-inner .auth-input:focus {
        border-color: rgba(27, 146, 153, .7);
        box-shadow: 0 0 0 4px rgba(27, 146, 153, .15);
    }

    body.auth-registration-page .auth-inner textarea.auth-input {
        height: auto;
        min-height: 88px;
        padding: 14px 18px;
        resize: vertical;
        line-height: 1.45;
    }

    body.auth-registration-page .auth-inner select.auth-input {
        padding-right: 42px;
        -webkit-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='9' viewBox='0 0 14 9'%3E%3Cpath d='M1 1.5l6 6 6-6' fill='none' stroke='%231f7a92' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 18px center;
    }

    body.auth-registration-page .auth-inner .auth-input.has-eye { padding-right: 52px; }
    /* auth.css shrinks .auth-eye to 40px on desktop; keep it as tall as the field. */
    body.auth-registration-page .auth-inner .auth-eye { right: 14px; width: 34px; height: 100%; color: #2b6e8a; }
    body.auth-registration-page .auth-inner .auth-eye:hover,
    body.auth-registration-page .auth-inner .auth-eye:focus { color: #0a5f86; }

    body.auth-registration-page .auth-inner .auth-error-text {
        display: block;
        margin: -11px 0 14px 4px;
        font-size: 12.5px;
    }

    /* ---------- actions ---------- */
    body.auth-registration-page .auth-inner > form > .auth-submit,
    body.auth-registration-page #registrationForm > .auth-submit {
        display: block;
        width: 100%;
        height: 58px;
        margin-top: 26px;
        font-size: 16.5px;
    }

    body.auth-registration-page .auth-links { margin-top: 22px; text-align: center; }

    body.auth-registration-page .auth-alt {
        margin: 0;
        font-size: 14.5px;
        color: #4d6278;
    }

    body.auth-registration-page .auth-alt a {
        color: #0f6a86;
        font-weight: 600;
        text-decoration: none;
    }

    body.auth-registration-page .auth-alt a:hover,
    body.auth-registration-page .auth-alt a:focus { text-decoration: underline; }

    /* ---------- date picker + OTP popup: match the new palette ---------- */
    body.auth-body.auth-registration-page .flatpickr-months {
        background: linear-gradient(90deg, #1b9299 0%, #016091 100%);
    }

    body.auth-body.auth-registration-page .flatpickr-day.selected,
    body.auth-body.auth-registration-page .flatpickr-day.startRange,
    body.auth-body.auth-registration-page .flatpickr-day.endRange {
        background: #1b9299;
        border-color: #1b9299;
    }

    body.auth-body.auth-registration-page .flatpickr-day:hover,
    body.auth-body.auth-registration-page .flatpickr-day:focus {
        background: rgba(27, 146, 153, .14);
        border-color: rgba(27, 146, 153, .14);
    }

    body.auth-body.auth-registration-page .flatpickr-weekdays { background: #f2f9fb; }

    body.auth-registration-page .auth-otp-modal .auth-input { border-radius: 13px; }
    body.auth-registration-page .auth-otp-modal__icon {
        background: linear-gradient(135deg, rgba(27, 146, 153, .16), rgba(1, 96, 145, .12));
    }

    @media (max-width: 991.98px) {
        body.auth-registration-page .auth-brand { margin-bottom: 20px; }
        body.auth-registration-page .auth-brand-text strong { font-size: 22px; }
        body.auth-registration-page .auth-inner .auth-input { height: 50px; }
    }

    @media (max-width: 480px) {
        body.auth-registration-page .auth-inner .auth-grid { grid-template-columns: 1fr; }
    }

    @media (prefers-reduced-motion: reduce) {
        body.auth-registration-page .auth-inner .auth-input { transition: none; }
    }