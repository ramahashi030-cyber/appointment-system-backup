<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $kioskName }} — QMMC OPD Kiosk</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --kiosk-primary: #0b6bcb;
            --kiosk-primary-dark: #084b91;
            --kiosk-ink: #14243b;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: 'Poppins', system-ui, sans-serif;
            background: radial-gradient(circle at 30% 20%, #e8f3ff 0%, #cfe6fb 45%, #a9d1f5 100%);
            color: var(--kiosk-ink);
        }

        .kiosk-frame {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .kiosk-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem 1.4rem;
            background: rgba(255, 255, 255, 0.85);
            border-bottom: 1px solid rgba(11, 107, 203, 0.18);
        }

        .kiosk-brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-weight: 700;
            font-size: 1rem;
        }

        .kiosk-brand img { height: 38px; width: auto; }

        .kiosk-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #4a5b73;
        }

        .kiosk-exit {
            border: 1px solid #c9d6e5;
            background: #fff;
            color: #4a5b73;
            border-radius: 8px;
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .kiosk-exit:hover { background: #f2f6fb; color: var(--kiosk-ink); }

        .kiosk-stage {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            min-height: 0;
        }

        .kiosk-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(8, 75, 145, 0.22);
            padding: 2.4rem 2.2rem;
            text-align: center;
            width: 94%;
            max-width: 860px;
            max-height: 100%;
            overflow-y: auto;
        }

        .kiosk-title {
            font-size: clamp(1.5rem, 3vw, 2.3rem);
            font-weight: 700;
            margin: 0 0 0.4rem;
        }

        .kiosk-subtitle {
            font-size: 1.02rem;
            color: #4a5b73;
            margin-bottom: 1.6rem;
        }

        #scan-btn {
            font-size: 1.7rem;
            font-weight: 600;
            padding: 1.3rem 2rem;
            border-radius: 16px;
            border: 0;
            background: linear-gradient(180deg, var(--kiosk-primary) 0%, var(--kiosk-primary-dark) 100%);
            color: #fff;
            min-width: min(420px, 90%);
        }

        #scan-btn:hover, #scan-btn:focus { filter: brightness(1.06); color: #fff; }

        #scan-btn i { margin-right: 0.6rem; }

        .kiosk-scan-panel { display: none; }

        .kiosk-scan-panel.is-active { display: block; }

        #reader {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            border: 6px solid #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.3);
            background: #000;
        }

        #reader video {
            width: 100% !important;
            height: auto !important;
            object-fit: cover;
            border-radius: 14px;
            image-rendering: crisp-edges;
        }

        #kiosk-status {
            margin-top: 1.1rem;
            font-size: 1.08rem;
            font-weight: 500;
            min-height: 1.6rem;
        }

        .kiosk-info-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 0.4rem;
            border-bottom: 1px solid #e7eef7;
            font-size: 1.15rem;
            text-align: left;
        }

        .kiosk-info-row strong { font-weight: 600; color: #33465f; }

        .kiosk-info-row span { font-weight: 500; text-align: right; overflow-wrap: anywhere; }

        .kiosk-question {
            margin: 1.4rem 0 1.1rem;
            font-size: 1.05rem;
            font-weight: 600;
            color: #33465f;
        }

        .kiosk-decision {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .kiosk-decision button {
            font-size: 1.35rem;
            font-weight: 600;
            padding: 0.9rem 2.6rem;
            border-radius: 14px;
            border: 0;
            min-width: 220px;
        }

        .kiosk-decision .btn-correct { background: #198754; color: #fff; }
        .kiosk-decision .btn-wrong { background: #dc3545; color: #fff; }
        .kiosk-decision button:disabled { opacity: 0.6; }

        .kiosk-message-icon { font-size: 3rem; line-height: 1; margin-bottom: 0.8rem; }
        .kiosk-message-icon.is-success { color: #198754; }
        .kiosk-message-icon.is-warning { color: #b02a37; }
        .kiosk-message-text { font-size: 1.25rem; font-weight: 500; overflow-wrap: anywhere; }

        .modal-content { border: 0; border-radius: 20px; }
        .modal-title { font-weight: 700; }
        .modal-footer .btn { font-weight: 600; border-radius: 10px; padding: 0.55rem 1.6rem; }

        #kiosk-screensaver {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.2rem;
            background: linear-gradient(160deg, #06284e 0%, #0b6bcb 60%, #084b91 100%);
            color: #fff;
            text-align: center;
        }

        #kiosk-screensaver.is-active { display: flex; }

        #kiosk-screensaver img { height: 110px; width: auto; filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.4)); }
        #kiosk-screensaver h2 { font-weight: 700; margin: 0; }
        #kiosk-screensaver p { opacity: 0.85; margin: 0; font-size: 1.1rem; }

        .kiosk-screensaver-clock { font-size: 2.6rem; font-weight: 600; letter-spacing: 0.04em; }
    </style>
</head>
<body>
<div class="kiosk-frame" data-kiosk-root
     data-verify-url="{{ route('admin.kiosk.verify') }}"
     data-confirm-url="{{ route('admin.kiosk.confirm') }}"
     data-kiosk-name="{{ $kioskName }}"
     data-screensaver-after="{{ $screensaverAfter }}"
     data-reset-after="{{ $resetAfter }}">

    <header class="kiosk-topbar">
        <div class="kiosk-brand">
            <img src="{{ asset('images/logo.png') }}" alt="QMMC logo">
            <span>QMMC Patient Appointment System</span>
        </div>
        <div class="kiosk-meta">
            <span class="kiosk-clock" aria-live="off"></span>
            <span>{{ $kioskName }}</span>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="kiosk-exit" type="submit">Exit</button>
            </form>
        </div>
    </header>

    <main class="kiosk-stage">
        <section class="kiosk-card" aria-live="polite">
            <div id="kiosk-home">
                <h1 class="kiosk-title">Welcome to QMMC OPD</h1>
                <p class="kiosk-subtitle">Scan your appointment QR code to check in for today.</p>
                <button id="scan-btn" type="button">
                    <i class="bi bi-qr-code-scan" aria-hidden="true"></i>Scan QR Code
                </button>
            </div>

            <div id="kiosk-scan-panel" class="kiosk-scan-panel">
                <h1 class="kiosk-title">Scanning</h1>
                <p class="kiosk-subtitle">Hold your appointment QR code in front of the camera.</p>
                <div id="reader"></div>
                <div id="kiosk-status" role="status"></div>
            </div>

            <div id="kiosk-confirm-panel" class="kiosk-scan-panel">
                <h1 class="kiosk-title">Confirm Your Information</h1>
                <p class="kiosk-subtitle">Please check if this information is correct.</p>
                <div id="kiosk-patient-info"></div>
                <p class="kiosk-question">Is this information correct?</p>
                <div class="kiosk-decision">
                    <button type="button" class="btn-correct" id="btn-correct">Correct</button>
                    <button type="button" class="btn-wrong" id="btn-wrong">Not Correct</button>
                </div>
            </div>

            <div id="kiosk-message-panel" class="kiosk-scan-panel">
                <div id="kiosk-message-icon" class="kiosk-message-icon" aria-hidden="true"></div>
                <p id="kiosk-message-text" class="kiosk-message-text"></p>
                <button id="message-ok-btn" type="button" class="btn btn-primary mt-4"
                        style="font-size: 1.2rem; font-weight: 600; padding: 0.7rem 2.4rem; border-radius: 12px;">
                    OK
                </button>
            </div>
        </section>
    </main>

    <div id="kiosk-screensaver" aria-hidden="true">
        <img src="{{ asset('images/logo.png') }}" alt="">
        <div class="kiosk-screensaver-clock" data-screensaver-clock></div>
        <h2>QMMC Patient Appointment System</h2>
        <p>Please speak with our front desk for assistance.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    (function () {
        const root = document.querySelector('[data-kiosk-root]');
        const verifyUrl = root.dataset.verifyUrl;
        const confirmUrl = root.dataset.confirmUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const screensaverAfter = parseInt(root.dataset.screensaverAfter, 10) || 20;
        const resetAfter = parseInt(root.dataset.resetAfter, 10) || 60;

        const homePanel = document.getElementById('kiosk-home');
        const scanPanel = document.getElementById('kiosk-scan-panel');
        const confirmPanel = document.getElementById('kiosk-confirm-panel');
        const messagePanel = document.getElementById('kiosk-message-panel');
        const patientInfo = document.getElementById('kiosk-patient-info');
        const statusBox = document.getElementById('kiosk-status');
        const messageIcon = document.getElementById('kiosk-message-icon');
        const messageText = document.getElementById('kiosk-message-text');
        const btnCorrect = document.getElementById('btn-correct');
        const btnWrong = document.getElementById('btn-wrong');
        const screensaver = document.getElementById('kiosk-screensaver');

        let scanner = null;
        let cameraOn = false;
        let state = 'home';
        let scanId = '';
        let confirmBusy = false;
        let resumeTimer = null;
        let idleSeconds = 0;

        function showPanel(panel) {
            [homePanel, scanPanel, confirmPanel, messagePanel].forEach(function (node) {
                node.classList.remove('is-active');
                node.style.display = 'none';
            });
            if (panel === homePanel) {
                panel.style.display = 'block';
                return;
            }
            panel.classList.add('is-active');
            panel.style.display = 'block';
        }

        function setStatus(text) {
            statusBox.textContent = text;
        }

        function cameraErrorMessage(error) {
            const name = (error && error.name) || '';
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || name === 'SecurityError') {
                return 'Camera permission was denied. Click the camera icon in the address bar, choose Allow, then try again.';
            }
            if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                return 'No camera was found on this device. Connect a camera and try again.';
            }
            if (name === 'NotReadableError' || name === 'TrackStartError') {
                return 'The camera is being used by another application. Close that application and try again.';
            }
            if (name === 'OverconstrainedError') {
                return 'This camera does not support the required video settings. Please try a different camera.';
            }
            return 'Unable to access the camera. Please allow camera permission and try again.';
        }

        function startScanner() {
            if (cameraOn) {
                return;
            }
            if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.error('Kiosk camera blocked: insecure context at ' + window.location.origin
                    + '. Browsers only expose the camera on HTTPS pages (or localhost). Serve the kiosk over HTTPS,'
                    + ' or allow this origin via chrome://flags/#unsafely-treat-insecure-origin-as-secure.');
                showMessage('Camera access requires a secure (HTTPS) connection. Please open this kiosk over HTTPS, then try again.', false);
                return;
            }
            cameraOn = true;
            state = 'scanning';
            showPanel(scanPanel);
            setStatus('');
            if (!scanner) {
                scanner = new Html5Qrcode('reader');
            }
            scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 15,
                    qrbox: function (viewfinderWidth, viewfinderHeight) {
                        const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                        const qrSize = Math.floor(minEdge * 0.7);
                        return { width: qrSize, height: qrSize };
                    },
                    aspectRatio: 1.7778,
                    videoConstraints: {
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        facingMode: 'environment'
                    }
                },
                onQrScanned,
                function () {}
            ).catch(function (error) {
                cameraOn = false;
                state = 'home';
                showPanel(homePanel);
                console.error('Kiosk camera error:', error);
                showMessage(cameraErrorMessage(error), false);
            });
        }

        function stopScanner() {
            clearTimeout(resumeTimer);
            resumeTimer = null;
            if (scanner && cameraOn) {
                scanner.stop().catch(function () {});
            }
            cameraOn = false;
        }

        function post(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body)
            }).then(function (response) { return response.json(); });
        }

        function onQrScanned(qrText) {
            console.log('[kiosk] Scanned QR:', String(qrText).slice(0, 28) + '... (' + qrText.length + ' chars)');
            if (state !== 'scanning') {
                return;
            }
            state = 'verifying';
            setStatus('Verifying your appointment\u2026');
            stopScanner();

            post(verifyUrl, { qr: qrText }).then(function (data) {
                if (data.success) {
                    scanId = data.scan_id;
                    renderPatient(data.patient);
                    state = 'confirm';
                    showPanel(confirmPanel);
                    return;
                }
                showTransientMessage(data.message || 'Invalid QR code. Please try again.');
            }).catch(function (error) {
                console.error('[kiosk] Verify request failed:', error);
                showTransientMessage('We could not reach the appointment system. Please try again.');
            });
        }

        function renderPatient(patient) {
            patientInfo.textContent = '';
            const rows = [
                ['Last Name', patient.last],
                ['First Name', patient.first],
                ['Middle Name', patient.middle],
                ['Birth Date', patient.birth_date],
                ['Hospital Number', patient.hospital_number]
            ];
            rows.forEach(function (row) {
                const line = document.createElement('div');
                line.className = 'kiosk-info-row';
                const label = document.createElement('strong');
                label.textContent = row[0];
                const value = document.createElement('span');
                value.textContent = row[1] || '\u2014';
                line.appendChild(label);
                line.appendChild(value);
                patientInfo.appendChild(line);
            });
        }

        function showTransientMessage(message) {
            state = 'message';
            messageIcon.textContent = '\u26A0';
            messageIcon.className = 'kiosk-message-icon is-warning';
            messageText.textContent = message;
            showPanel(messagePanel);
            resumeTimer = setTimeout(function () {
                resumeTimer = null;
                startScanner();
            }, 5000);
        }

        function showMessage(message, success) {
            state = 'message';
            messageIcon.textContent = success ? '\u2713' : '\u26A0';
            messageIcon.className = 'kiosk-message-icon ' + (success ? 'is-success' : 'is-warning');
            messageText.textContent = message;
            showPanel(messagePanel);
            document.getElementById('message-ok-btn').focus();
        }

        function sendConfirmation(confirmed) {
            if (confirmBusy) {
                return;
            }
            confirmBusy = true;
            btnCorrect.disabled = true;
            btnWrong.disabled = true;

            post(confirmUrl, { scan_id: scanId, confirmed: confirmed })
                .then(function (data) {
                    resetKiosk();
                    showMessage(data.message || 'Thank you.', data.success === true);
                })
                .catch(function () {
                    resetKiosk();
                    showMessage('We could not reach the appointment system. Please try again.', false);
                });
        }

        function resetKiosk() {
            clearTimeout(resumeTimer);
            resumeTimer = null;
            stopScanner();
            scanId = '';
            confirmBusy = false;
            btnCorrect.disabled = false;
            btnWrong.disabled = false;
            patientInfo.textContent = '';
            setStatus('');
            state = 'home';
            showPanel(homePanel);
        }

        document.getElementById('scan-btn').addEventListener('click', function () {
            startScanner();
        });

        btnCorrect.addEventListener('click', function () {
            sendConfirmation(true);
        });

        btnWrong.addEventListener('click', function () {
            sendConfirmation(false);
        });

        document.getElementById('message-ok-btn').addEventListener('click', function () {
            if (state === 'message' && resumeTimer) {
                clearTimeout(resumeTimer);
                resumeTimer = null;
                startScanner();
                return;
            }
            resetKiosk();
        });

        function tickClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            document.querySelectorAll('.kiosk-clock, [data-screensaver-clock]').forEach(function (node) {
                node.textContent = time;
            });
        }

        tickClock();
        setInterval(tickClock, 15000);

        ['mousemove', 'keydown', 'touchstart', 'pointerdown'].forEach(function (eventName) {
            document.addEventListener(eventName, function () {
                idleSeconds = 0;
                screensaver.classList.remove('is-active');
            });
        });

        setInterval(function () {
            idleSeconds += 1;

            if (state === 'scanning' || state === 'verifying') {
                return;
            }

            if (idleSeconds >= resetAfter && (state === 'confirm' || state === 'message')) {
                resetKiosk();
                screensaver.classList.remove('is-active');
                idleSeconds = 0;
                return;
            }

            if (idleSeconds >= screensaverAfter) {
                screensaver.classList.add('is-active');
            }
        }, 1000);
    })();
</script>
</body>
</html>
