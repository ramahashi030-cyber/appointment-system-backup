{{-- Schedule modals — Create Appointment Module layout (telemed + face-to-face) --}}
<div class="modal fade" id="telemedScheduleModal" tabindex="-1" aria-labelledby="telemedScheduleTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content triager-book-modal">
            <div class="modal-header triager-book-header">
                <h5 class="modal-title" id="telemedScheduleTitle">
                    <i class="bi bi-calendar3-fill" aria-hidden="true"></i>
                    Create Appointment Module
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="telemedScheduleForm" method="POST">
                @csrf
                <div class="modal-body triager-book-body">
                    <div class="triager-book-banner">
                        <span class="triager-book-banner-icon"><i class="bi bi-camera-video-fill" aria-hidden="true"></i></span>
                        <div class="triager-book-banner-copy">
                            <h6>Telemedicine Appointment</h6>
                            <p data-schedule-patient-label></p>
                        </div>
                    </div>

                    <section class="triager-book-section" aria-labelledby="telemedBookStepOne">
                        <h6 class="triager-book-step" id="telemedBookStepOne">1. Book an Appointment</h6>

                        <div class="triager-book-card">
                            <div class="triager-book-grid triager-book-grid-three">
                                <div>
                                    <label class="triager-book-label" for="telemedAge">Age / Edad</label>
                                    <div class="triager-book-field">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        <input type="text" class="triager-book-control" id="telemedAge" data-schedule-field="age" value="—" readonly>
                                    </div>
                                </div>
                                <div>
                                    <label class="triager-book-label" for="telemedGender">Gender / Kasarian</label>
                                    <div class="triager-book-field">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        <select class="triager-book-control triager-book-select" id="telemedGender" data-schedule-field="gender">
                                            <option value="">—</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="triager-book-label" for="telemedComplaint">Chief Complaint / Dahilan ng Pagsumta</label>
                                    <div class="triager-book-field">
                                        <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                                        <input type="text" class="triager-book-control" id="telemedComplaint" data-schedule-field="complaint" value="—" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="triager-book-grid">
                                <div>
                                    <label class="triager-book-label" for="telemedService">Recommended Department</label>
                                    <div class="triager-book-field triager-book-field-accent">
                                        <i class="bi bi-building" aria-hidden="true"></i>
                                        <select class="triager-book-control triager-book-select" id="telemedService" name="service_id" data-schedule-field="service">
                                            <option value="">Select a service</option>
                                            @foreach ($teleServices as $service)
                                                <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="triager-book-label" for="telemedType">Select Type of Service</label>
                                    <div class="triager-book-field triager-book-field-accent">
                                        <i class="bi bi-stethoscope" aria-hidden="true"></i>
                                        <select class="triager-book-control triager-book-select" id="telemedType" tabindex="-1" disabled>
                                            <option>TELEMEDICINE</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="triager-book-section" data-schedule-section hidden aria-labelledby="telemedBookStepTwo">
                        <h6 class="triager-book-step" id="telemedBookStepTwo">2. Select Date &amp; Time</h6>

                        <div class="triager-book-legend">
                            <span class="triager-legend-item"><span class="triager-legend-dot available"></span>Available Time Slot</span>
                            <span class="triager-legend-item"><span class="triager-legend-dot unavailable"></span>Not Available</span>
                            <span class="triager-legend-item"><span class="triager-legend-dot holiday"></span>Holiday / No Slots</span>
                            <span class="triager-legend-item"><span class="triager-legend-dot partial"></span>Partial Holiday</span>
                        </div>

                        <div class="triager-book-schedule-grid">
                            <div class="triager-book-calendar">
                                <div class="triager-book-calendar-head">
                                    <button type="button" class="triager-book-month-btn" data-schedule-prev aria-label="Previous month">
                                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                    </button>
                                    <strong data-schedule-month-label>—</strong>
                                    <button type="button" class="triager-book-month-btn" data-schedule-next aria-label="Next month">
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="triager-book-weekdays">
                                    <span>Sun</span>
                                    <span>Mon</span>
                                    <span>Tue</span>
                                    <span>Wed</span>
                                    <span>Thu</span>
                                    <span>Fri</span>
                                    <span>Sat</span>
                                </div>
                                <div class="triager-book-days" data-schedule-calendar></div>
                            </div>

                            <div class="triager-book-slots">
                                <div class="triager-book-slots-head">
                                    <span class="triager-book-slots-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
                                    <span class="triager-book-slots-title">
                                        <strong>Available Time Slots</strong>
                                        <small data-schedule-selected-date>Select an available date</small>
                                    </span>
                                </div>
                                <div class="triager-book-slot-grid" data-schedule-slots>
                                    <p class="triager-book-slots-empty">Select an available date to view time slots.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="alert alert-danger" data-schedule-error hidden role="alert"></div>

                    <input type="hidden" id="telemedDate" name="date" data-schedule-field="date" value="">
                    <input type="hidden" id="telemedTimeSlot" name="time_slot" data-schedule-field="time" value="">
                </div>

                <div class="modal-footer triager-book-footer">
                    <button type="submit" class="triager-book-confirm">
                        <i class="bi bi-calendar-check" aria-hidden="true"></i>
                        Confirm Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Face-to-face schedule modal — Create Appointment Module layout --}}
<div class="modal fade" id="faceScheduleModal" tabindex="-1" aria-labelledby="faceScheduleTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content triager-book-modal">
            <div class="modal-header triager-book-header">
                <h5 class="modal-title" id="faceScheduleTitle">
                    <i class="bi bi-calendar3-fill" aria-hidden="true"></i>
                    Create Appointment Module
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="faceScheduleForm" method="POST">
                @csrf
                <div class="modal-body triager-book-body">
                    <div class="triager-book-banner">
                        <span class="triager-book-banner-icon"><i class="bi bi-hospital" aria-hidden="true"></i></span>
                        <div class="triager-book-banner-copy">
                            <h6>Face-to-Face Appointment</h6>
                            <p data-schedule-patient-label></p>
                        </div>
                    </div>

                    <section class="triager-book-section" aria-labelledby="faceBookStepOne">
                        <h6 class="triager-book-step" id="faceBookStepOne">1. Book an Appointment</h6>

                        <div class="triager-book-card">
                            <div class="triager-book-grid triager-book-grid-three">
                                <div>
                                    <label class="triager-book-label" for="faceAge">Age / Edad</label>
                                    <div class="triager-book-field">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        <input type="text" class="triager-book-control" id="faceAge" data-schedule-field="age" value="—" readonly>
                                    </div>
                                </div>
                                <div>
                                    <label class="triager-book-label" for="faceGender">Gender / Kasarian</label>
                                    <div class="triager-book-field">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        <select class="triager-book-control triager-book-select" id="faceGender" data-schedule-field="gender">
                                            <option value="">—</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="triager-book-label" for="faceComplaint">Chief Complaint / Dahilan ng Pagsumta</label>
                                    <div class="triager-book-field">
                                        <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                                        <input type="text" class="triager-book-control" id="faceComplaint" data-schedule-field="complaint" value="—" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="triager-book-grid">
                                <div>
                                    <label class="triager-book-label" for="faceService">Recommended Department</label>
                                    <div class="triager-book-field triager-book-field-accent">
                                        <i class="bi bi-building" aria-hidden="true"></i>
                                        <select class="triager-book-control triager-book-select" id="faceService" name="service_id" data-schedule-field="service">
                                            <option value="">Select a service</option>
                                            @foreach ($faceServices as $service)
                                                <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="triager-book-label" for="faceType">Select Type of Service</label>
                                    <div class="triager-book-field triager-book-field-accent">
                                        <i class="bi bi-hospital" aria-hidden="true"></i>
                                        <select class="triager-book-control triager-book-select" id="faceType" tabindex="-1" disabled>
                                            <option>FACE TO FACE</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="triager-book-section" data-schedule-section hidden aria-labelledby="faceBookStepTwo">
                        <h6 class="triager-book-step" id="faceBookStepTwo">2. Select Date &amp; Time</h6>

                        <div class="triager-book-legend">
                            <span class="triager-legend-item"><span class="triager-legend-dot available"></span>Available Time Slot</span>
                            <span class="triager-legend-item"><span class="triager-legend-dot unavailable"></span>Not Available</span>
                            <span class="triager-legend-item"><span class="triager-legend-dot holiday"></span>Holiday / No Slots</span>
                            <span class="triager-legend-item"><span class="triager-legend-dot partial"></span>Partial Holiday</span>
                        </div>

                        <div class="triager-book-schedule-grid">
                            <div class="triager-book-calendar">
                                <div class="triager-book-calendar-head">
                                    <button type="button" class="triager-book-month-btn" data-schedule-prev aria-label="Previous month">
                                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                    </button>
                                    <strong data-schedule-month-label>—</strong>
                                    <button type="button" class="triager-book-month-btn" data-schedule-next aria-label="Next month">
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="triager-book-weekdays">
                                    <span>Sun</span>
                                    <span>Mon</span>
                                    <span>Tue</span>
                                    <span>Wed</span>
                                    <span>Thu</span>
                                    <span>Fri</span>
                                    <span>Sat</span>
                                </div>
                                <div class="triager-book-days" data-schedule-calendar></div>
                            </div>

                            <div class="triager-book-slots">
                                <div class="triager-book-slots-head">
                                    <span class="triager-book-slots-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
                                    <span class="triager-book-slots-title">
                                        <strong>Available Time Slots</strong>
                                        <small data-schedule-selected-date>Select an available date</small>
                                    </span>
                                </div>
                                <div class="triager-book-slot-grid" data-schedule-slots>
                                    <p class="triager-book-slots-empty">Select an available date to view time slots.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="alert alert-danger" data-schedule-error hidden role="alert"></div>

                    <input type="hidden" id="faceDate" name="date" data-schedule-field="date" value="">
                    <input type="hidden" id="faceTimeSlot" name="time_slot" data-schedule-field="time" value="">
                </div>

                <div class="modal-footer triager-book-footer">
                    <button type="submit" class="triager-book-confirm">
                        <i class="bi bi-calendar-check" aria-hidden="true"></i>
                        Confirm Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
