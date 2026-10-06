{{--
    Shared "enlarged QR code" lightbox for Face-to-Face appointments.

    Triggers anywhere in the app point at it with:
      data-qr-expand="#appointmentQrEnlargeModal"
      data-qr-src="<image url>"
      data-qr-alt="<image description>"
    resources/js/patient-dashboard.js copies the source into the image and
    opens the modal (see [data-qr-expand]).
--}}

<div class="modal fade qr-enlarge-modal" id="appointmentQrEnlargeModal" tabindex="-1" aria-labelledby="appointmentQrEnlargeTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <header class="qr-enlarge-header">
                <div>
                    <h2 id="appointmentQrEnlargeTitle">Appointment QR code</h2>
                    <span>Click close when you are ready to return to your appointments.</span>
                </div>
                <button type="button" class="qr-enlarge-close" data-bs-dismiss="modal" aria-label="Close enlarged QR code">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div class="modal-body qr-enlarge-body">
                <img data-qr-enlarged-image alt="Enlarged appointment QR code" src="">
            </div>
        </div>
    </div>
</div>
