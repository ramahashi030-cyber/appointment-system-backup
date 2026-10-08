/**
 * Prescription Print / Renew for the patient portal.
 *
 * printRx() is a verbatim port of the printRx() function in
 * QALINGA1/prescriptions2.php: it opens the printable QMMC-Rx paper with the
 * same layout, the same medicine/signa rows and the same rx_view.php QR code.
 * (prescriptions2.php replaced the old rx_print.php onclick with this flow —
 * rx_print.php only reads sessionStorage.pcchrgcod, which nothing ever sets,
 * so it renders a blank paper when opened directly.)
 *
 * The click handlers are installed on document because the prescriptions page
 * is also loaded inside the dashboard modal: content injected with
 * innerHTML never executes its own <script> tags, so the buttons rely on this
 * script being loaded once by the page that hosts the modal.
 */
(function () {
    'use strict';

    // Guard against loading twice (dashboard page + full page push).
    if (window.__qalingaRxLoaded) {
        return;
    }
    window.__qalingaRxLoaded = true;

    // Legacy QALINGA1 renewal process — prescriptions2.php's Renew button
    // navigated to undercons.php; the legacy app is served from this network.
    var RENEW_URL = 'http://190.190.0.56/QALINGA1/undercons.php';

    function printRx(records, patient, rxNumber) {
        var win = window.open('', '', 'width=1200,height=900');
        if (!win) {
            return; // popup blocked — nothing sensible to do
        }

        var meds = '';

        records.forEach((r, index) => {
            meds += `
        <tr>
            <td style="padding:8px;border-bottom:1px dashed #999;width:50px;">
                ${index + 1}.
            </td>

            <td style="padding:8px;border-bottom:1px dashed #999;">
    <b style="font-size:12px;">${r.itemdesc}</b>

    <div style="
        margin-top:4px;
        color:#444;
        font-size:10px;
    ">
        SIGNA: ${r.signa || ''}

        ${r.remarks
            ? `<div><b>Remarks:</b> ${r.remarks}</div>`
            : ''
        }
    </div>
</td>

            <td style="padding:8px;border-bottom:1px dashed #999;width:80px;text-align:center;">

                <b style="font-size:12px;">${r.qty}</b>
            </td>
        </tr>
        `;
        });

        var html = `
<!DOCTYPE html>
<html>
<head>
<title>Prescription Print</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"><\/script>

<style>

body{
    font-family: Arial, sans-serif;
    background:#f5f5f5;
    padding:20px;
}

.rx-paper{
    width:850px;
    min-height:1100px;
    background:white;
    margin:auto;
    padding:40px;
    border:1px solid #ccc;
    position:relative;
}

.hospital-header{
    text-align:center;
    border-bottom:3px solid #0a2674;
    padding-bottom:10px;
    margin-bottom:20px;
}

.hospital-header h2{
    margin:0;
    color:#000000;
    font-weight:400;
    font-size:26px;
}

.hospital-header small{
    color:##000000;
}

.patient-box{
    margin-top:15px;
    margin-bottom:25px;
}

.patient-box table{
    width:100%;
}

.patient-box td{
    padding:2px 4px;
    font-size:14px;
    line-height:1.1;
}

.rx-symbol{
    font-size:60px;
    font-family: serif;
    font-weight:bold;
    color:#000000;

    margin-top:-10px;
    margin-bottom:0px;

    line-height:1;
}
.med-table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}

.footer{
    margin-top:80px;
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
}

.signature{
    text-align:center;
    width:300px;
}

.signature-line{
    border-top:1px solid #000;
    margin-top:60px;
    padding-top:5px;
}

.qr-box{
    text-align:center;
}

.print-btn{
    position:fixed;
    top:10px;
    right:10px;
    z-index:9999;
}

@media print{

    .print-btn{
        display:none;
    }

    body{
        background:white;
        padding:0;
    }

    .rx-paper{
        border:none;
        width:100%;
        box-shadow:none;
    }
}

</style>
</head>

<body onload="generateQR()">

<button class="btn btn-primary print-btn" onclick="window.print()">
    Print Prescription
</button>

<div class="rx-paper">

    <!-- HEADER -->
<div class="hospital-header">

    <div style="
        display:flex;
        align-items:center;
        justify-content:flex-start;
        gap:15px;
    ">

        <!-- qmmc LOGO -->
        <img
            src="/assets/qmmclogo.png"
            style="
                width:70px;
                height:70px;
                object-fit:contain;
            "
        >


        <!-- TITLE -->
        <div style="
            flex:1;
            text-align:center;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            padding:0 15px;
        ">
        <small>
                Department of Health
            </small>

            <h2 style="margin:0;">
                Quirino Memorial Medical Center
            </h2>

            <small>
               JP Rizal cor. P. Tuazon St., Project 4, Quezon City, 1109
            </small>

        </div>
        <!-- BG LOGO -->
        <img
            src="/assets/bg.png"
            style="
                width:90px;
                height:90px;
                object-fit:contain;
            "
        >
         <!-- doh LOGO -->
        <img
            src="/assets/logo_doh.png"
            style="
                width:70px;
                height:70px;
                object-fit:contain;
            "
        >

    </div>

</div>

    <!-- PATIENT INFO -->
    <div class="patient-box">

        <table>

            <tr>
                <td width="120"><b>Patient Name:</b></td>
                <td>${patient.patname}</td>

                <td width="80"><b>RX No:</b></td>
                <td>${rxNumber}</td>
            </tr>

            <tr>
                <td><b>Hospital No:</b></td>
                <td>${patient.hospital_number}</td>

                <td><b>Date:</b></td>
                <td>${new Date(patient.dodate).toLocaleDateString()}</td>
            </tr>

            <tr>
                    <td><b>Age / Sex:</b></td>
                    <td>${patient.age} / ${patient.patsex}</td>

                    <td><b>${patient.location_label || 'Ward'}:</b></td>
                    <td>${patient.ward_name || ''}</td>
                </tr>

                <tr>
                    <td><b>Doctor:</b></td>
                    <td>${patient.doctor_name || ''}</td>


        </table>

    </div>

    <!-- RX SYMBOL -->
    <div class="rx-symbol">
    <img
        src="/assets/rx.png"
        style="
            width:60px;
            height:auto;
            object-fit:contain;
            display:block;
        "
    >
</div>

    <!-- MEDICINES -->
    <table class="med-table">

        <thead>
            <tr style="background:#0a2674;color:white;">
                <th style="padding:10px;width:30px;">#</th>
                <th style="padding:10px;">Medicine</th>
                <th style="padding:10px;width:50px;">Qty</th>
            </tr>
        </thead>

        <tbody>
            ${meds}
        </tbody>

    </table>

    <!-- FOOTER -->
    <div class="footer">

        <div class="signature">

            <div class="signature-line">
    ${patient.doctor_name || 'Attending Physician'}
    <br>
    Lic No: ${patient.licno || ''}
</div>

        </div>

        <div class="qr-box">

            <canvas id="qr"></canvas>

            <div style="font-size:12px;margin-top:5px;">
                Scan to Verify
            </div>

        </div>

    </div>

</div>

<script>

function generateQR(){

    const qrUrl =
        "http://qmmc.online/qalinga/rx_view.php?rx=" +
        encodeURIComponent(${JSON.stringify(rxNumber)});



    QRCode.toCanvas(
        document.getElementById("qr"),
        qrUrl,
        {
            width:120,
            margin:2,
            errorCorrectionLevel:"M"
        },
        function(error){
            if(error){
                console.error(error);
            }
        }
    );
}

<\/script>
</body>
</html>
`;

        win.document.write(html);
        win.document.close();
    }

    function parseJson(value, fallback) {
        try {
            return JSON.parse(value);
        } catch (error) {
            console.error('[rx] could not read prescription data', error);
            return fallback;
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || typeof target.closest !== 'function') {
            return;
        }

        var printButton = target.closest('[data-rx-print]');
        if (printButton) {
            var card = printButton.closest('[data-rx-records]');
            if (!card) {
                return;
            }
            printRx(
                parseJson(card.getAttribute('data-rx-records'), []),
                parseJson(card.getAttribute('data-rx-patient'), {}),
                card.getAttribute('data-rx-number') || ''
            );
            return;
        }

        if (target.closest('[data-rx-renew]')) {
            window.location.href = RENEW_URL;
        }
    });

    window.printRx = printRx;
})();
