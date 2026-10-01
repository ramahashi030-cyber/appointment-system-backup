<div class="modal fade dashboard-modal" id="appointmentCancelModal" tabindex="-1" aria-labelledby="appointmentCancelModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-modal-header">
                <div class="dashboard-modal-title-group">
                    <span class="dashboard-modal-title-icon red" aria-hidden="true"><i class="bi bi-x-circle-fill"></i></span>
                    <span>
                        <h2 id="appointmentCancelModalTitle">Cancel appointment</h2>
                        <small>Tell us briefly why you need to cancel.</small>
                    </span>
                </div>
                <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close cancellation modal">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            <form action="{{ route('telemed.book.cancel') }}" method="POST" data-appointment-cancel-form novalidate>
                @csrf
                <div class="modal-body dashboard-modal-body">
                    <label class="form-label" for="appointmentCancelReason">Cancellation reason</label>
                    <textarea
                        class="form-control"
                        id="appointmentCancelReason"
                        name="cancellation_reason"
                        rows="3"
                        maxlength="2000"
                        required
                        placeholder="Example: I need to reschedule because of a conflict."
                        data-appointment-cancel-reason
                    ></textarea>
                    <input type="hidden" name="cancel_id" value="" data-appointment-cancel-id>
                </div>

                <footer class="dashboard-modal-footer">
                    <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Keep appointment</button>
                    <button type="submit" class="dashboard-modal-button danger">
                        <i class="bi bi-x-circle" aria-hidden="true"></i> Confirm cancellation
                    </button>
                </footer>
            </form>
        </div>
    </div>
</div>
