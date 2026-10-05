const onDashboardReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
};

onDashboardReady(() => {
    const consentModalElement = document.getElementById('careConsentModal');
    const activeAppointmentModalElement = document.getElementById('activeAppointmentModal');
    const cancelledModalElement = document.getElementById('cancelledAppointmentModal');
    const bookingModalElement = document.getElementById('bookingModal');
    const bookingQrModalElement = document.getElementById('bookingQrModal');
    const bookingQrEnlargeModalElement = document.getElementById('bookingQrEnlargeModal');
    const appointmentCancelModalElement = document.getElementById('appointmentCancelModal');
    const consentServiceInput = document.querySelector('[data-consent-service-id]');
    const consentServiceModeInput = document.querySelector('[data-consent-service-mode]');
    const consentServiceLabel = document.querySelector('[data-consent-service-label]');
    const consentServiceName = document.querySelector('[data-consent-service-name]');
    const consentServiceModeLabel = document.querySelector('[data-consent-service-mode-label]');
    const consentServiceModeText = document.querySelector('[data-consent-service-mode-text]');
    const consentForm = document.querySelector('[data-consent-form]');
    const consentSubmit = document.querySelector('[data-consent-submit]');
    const consentError = document.querySelector('[data-consent-error]');

    const getModal = (element) => bootstrap.Modal.getOrCreateInstance(element);

    const showModalAfterCurrent = (currentElement, targetElement) => {
        if (!targetElement) {
            return;
        }

        const showTarget = () => getModal(targetElement).show();

        if (currentElement && !currentElement.contains(targetElement)) {
            const modalInstance = bootstrap.Modal.getInstance(currentElement)
                || bootstrap.Offcanvas.getInstance(currentElement);

            if (modalInstance) {
                const eventName = currentElement.classList.contains('offcanvas') ? 'hidden.bs.offcanvas' : 'hidden.bs.modal';
                currentElement.addEventListener(eventName, showTarget, { once: true });
                modalInstance.hide();
                return;
            }
        }

        showTarget();
    };

    const populateActiveAppointment = (appointment) => {
        if (!activeAppointmentModalElement || !appointment) return;

        const setText = (selector, value) => {
            const element = activeAppointmentModalElement.querySelector(selector);
            if (element) element.textContent = value || '—';
        };

        setText('[data-active-appointment-date]', appointment.date);
        setText('[data-active-appointment-time]', appointment.time_slot);
        setText('[data-active-appointment-service]', appointment.service_name || 'Telemedicine consultation');
        setText('[data-active-appointment-status]', appointment.status || 'Booked');

        const cancelForm = activeAppointmentModalElement.querySelector('[data-active-appointment-cancel]');
        const cancelId = activeAppointmentModalElement.querySelector('[data-active-appointment-cancel-id]');
        if (cancelForm && cancelId) {
            cancelId.value = appointment.id || '';
            cancelForm.hidden = !appointment.id;
        }
    };

    const syncActiveAppointmentBlur = () => {
        const shouldBlur = activeAppointmentModalElement?.classList.contains('show') ?? false;
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
            backdrop.classList.toggle('dashboard-modal-backdrop-blur', shouldBlur);
        });
    };

    activeAppointmentModalElement?.addEventListener('show.bs.modal', syncActiveAppointmentBlur);
    activeAppointmentModalElement?.addEventListener('shown.bs.modal', syncActiveAppointmentBlur);
    activeAppointmentModalElement?.addEventListener('hidden.bs.modal', syncActiveAppointmentBlur);

    if (activeAppointmentModalElement?.dataset.autoOpen === 'true') {
        getModal(activeAppointmentModalElement).show();
    }

    const setSidebarActive = (action) => {
        document.querySelectorAll('[data-sidebar-action]').forEach((button) => {
            const isActive = button.dataset.sidebarAction === action;
            button.classList.toggle('active', isActive);

            if (isActive) {
                button.setAttribute('aria-current', 'page');
            } else {
                button.removeAttribute('aria-current');
            }
        });
    };

    document.querySelectorAll('[data-sidebar-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.dataset.sidebarAction;
            const target = button.dataset.sidebarTarget
                ? document.getElementById(button.dataset.sidebarTarget)
                : null;

            setSidebarActive(action);

            if (action === 'home') {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            if (action === 'history' && target) {
                loadHistoryModal();
            }

            showModalAfterCurrent(button.closest('.offcanvas.show'), target);
        });
    });

    document.querySelectorAll('[data-open-notifications]').forEach((button) => {
        button.addEventListener('click', () => {
            setSidebarActive('notifications');
            showModalAfterCurrent(button.closest('.modal.show'), document.getElementById('notificationsModal'));
        });
    });

    /* Dropdown menu items open modals instead of navigating to pages. */
    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.dataset.openModal;
            const modalElement = document.getElementById(modalId);
            if (!modalElement) return;

            const dropdown = button.closest('.dropdown');
            if (dropdown) {
                const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown.querySelector('[data-bs-toggle="dropdown"]'));
                if (dropdownInstance) dropdownInstance.hide();
            }

            showModalAfterCurrent(button.closest('.modal.show'), modalElement);

            if (modalId === 'recordsModal') loadRecordsModal();
            if (modalId === 'prescriptionsModal') loadPrescriptionsModal();
            if (modalId === 'proceduresModal') loadProceduresModal();
            if (modalId === 'historyModal') loadHistoryModal();
        });
    });

    const loadModalData = async (url, listSelector, loadingSelector, emptySelector, renderFn) => {
        const list = document.querySelector(listSelector);
        const loading = document.querySelector(loadingSelector);
        const empty = document.querySelector(emptySelector);
        if (!list || !loading || !empty) return;

        loading.hidden = false;
        list.hidden = true;
        empty.hidden = true;

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Failed to load');
            const payload = await response.json();
            const items = renderFn(payload);
            if (items === '') {
                empty.hidden = false;
            } else {
                list.innerHTML = items;
                list.hidden = false;
            }
        } catch (error) {
            empty.hidden = false;
        } finally {
            loading.hidden = true;
        }
    };

    const loadHistoryModal = () => {
        const loading = document.querySelector('[data-history-loading]');
        const empty = document.querySelector('[data-history-empty]');
        const list = document.querySelector('[data-history-list]');
        const tbody = document.querySelector('[data-history-tbody]');
        if (!loading || !empty || !list || !tbody) return;

        loading.hidden = false;
        list.hidden = true;
        empty.hidden = true;

        fetch('/telemed/history', { headers: { Accept: 'application/json' } })
            .then((response) => {
                if (!response.ok) throw new Error('Failed to load');
                return response.json();
            })
            .then((payload) => {
                const items = payload.history || [];
                if (items.length === 0) {
                    empty.hidden = false;
                } else {
                    tbody.innerHTML = items.map((item) => `
                        <tr>
                            <td>${item.date || '—'}</td>
                            <td>${item.time_slot || '—'}</td>
                            <td>${item.service_name || 'Consultation'}</td>
                            <td>${item.mode || '—'}</td>
                            <td><span class="badge bg-${item.status === 'Completed' ? 'success' : item.status === 'Cancelled' ? 'danger' : 'secondary'}">${item.status || '—'}</span></td>
                        </tr>
                    `).join('');
                    list.hidden = false;
                }
            })
            .catch(() => {
                empty.hidden = false;
            })
            .finally(() => {
                loading.hidden = true;
            });
    };

    const loadRecordsModal = () => {
        loadModalData(
            '/patients/data/records',
            '[data-records-list]',
            '[data-records-loading]',
            '[data-records-empty]',
            (payload) => {
                if (!payload.records?.length) return '';
                return payload.records.map((record) => `
                    <article class="modal-data-item">
                        <span class="modal-data-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                        <div>
                            <strong>${record.title}</strong>
                            <span>${record.type} · ${record.date}</span>
                            ${record.summary ? `<p>${record.summary}</p>` : ''}
                        </div>
                    </article>
                `).join('');
            }
        );
    };

    const loadPrescriptionsModal = () => {
        loadModalData(
            '/patients/data/prescriptions',
            '[data-prescriptions-list]',
            '[data-prescriptions-loading]',
            '[data-prescriptions-empty]',
            (payload) => {
                if (!payload.prescriptions?.length) return '';
                return payload.prescriptions.map((rx) => `
                    <article class="modal-data-item">
                        <span class="modal-data-icon" aria-hidden="true"><i class="bi bi-capsule-pill"></i></span>
                        <div>
                            <strong>${rx.medicine}</strong>
                            <span>${rx.dosage} · ${rx.frequency}</span>
                            <span>Prescribed by ${rx.prescribed_by} · ${rx.date}</span>
                        </div>
                    </article>
                `).join('');
            }
        );
    };

    const loadProceduresModal = () => {
        loadModalData(
            '/patients/data/procedures',
            '[data-procedures-list]',
            '[data-procedures-loading]',
            '[data-procedures-empty]',
            (payload) => {
                if (!payload.procedures?.length) return '';
                return payload.procedures.map((proc) => `
                    <article class="modal-data-item">
                        <span class="modal-data-icon" aria-hidden="true"><i class="bi bi-activity"></i></span>
                        <div>
                            <strong>${proc.name || proc.procedure || 'Procedure'}</strong>
                            <span>${proc.date || ''} ${proc.status ? '· ' + proc.status : ''}</span>
                        </div>
                    </article>
                `).join('');
            }
        );
    };

    /* Edit profile form submission */
    const editProfileForm = document.querySelector('[data-edit-profile-form]');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const errorElement = editProfileForm.querySelector('[data-edit-profile-error]');
            const submitButton = editProfileForm.querySelector('[data-edit-profile-submit]');
            if (errorElement) errorElement.hidden = true;
            if (submitButton) submitButton.disabled = true;

            try {
                const response = await fetch(editProfileForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(editProfileForm),
                });
                const payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.message || 'Could not save profile.');
                }
                window.location.reload();
            } catch (error) {
                if (errorElement) {
                    errorElement.textContent = error.message;
                    errorElement.hidden = false;
                }
            } finally {
                if (submitButton) submitButton.disabled = false;
            }
        });
    }

    /* Live notification bell — poll for unread count every 5 seconds.
       Repointed at the shared /notifications feed so it never overwrites the
       real-time badge written by resources/js/notifications.js. */
    const notificationBell = document.querySelector('[data-open-notifications]');
    const notificationBadge = notificationBell?.querySelector('.patient-header-badge');

    const updateNotificationBadge = (count) => {
        if (!notificationBadge) return;
        if (count > 0) {
            notificationBadge.textContent = count > 9 ? '9+' : String(count);
            notificationBadge.hidden = false;
        } else {
            notificationBadge.hidden = true;
        }
    };

    const pollNotifications = async () => {
        try {
            const response = await fetch('/notifications?lite=1', {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;
            const payload = await response.json();
            updateNotificationBadge(payload.unread || 0);
        } catch (error) {
            // Silently fail — notification polling is non-critical.
        }
    };

    if (notificationBell) {
        setInterval(pollNotifications, 5000);
    }

    const prepareConsent = (trigger) => {
        const serviceId = trigger.dataset.serviceId || '';
        const serviceName = trigger.dataset.serviceName || 'Telemedicine consultation';
        const serviceMode = trigger.dataset.serviceMode === 'FACE' ? 'FACE' : 'TELE';
        const serviceModeLabel = serviceMode === 'FACE' ? 'Face-to-Face' : 'Telemedicine';

        if (consentServiceInput) consentServiceInput.value = serviceId;
        if (consentServiceModeInput) consentServiceModeInput.value = serviceMode;
        if (consentServiceName) consentServiceName.textContent = serviceName;
        if (consentServiceModeText) consentServiceModeText.textContent = serviceModeLabel;
        if (consentServiceModeLabel) {
            consentServiceModeLabel.classList.toggle('face', serviceMode === 'FACE');
            consentServiceModeLabel.classList.toggle('tele', serviceMode === 'TELE');
            const icon = consentServiceModeLabel.querySelector('i');
            if (icon) icon.className = `bi ${serviceMode === 'FACE' ? 'bi-person-check-fill' : 'bi-camera-video-fill'}`;
        }
        if (consentServiceLabel) consentServiceLabel.hidden = serviceId === '';
        if (consentError) consentError.hidden = true;
    };

    document.querySelectorAll('[data-open-consent]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            prepareConsent(trigger);
            showModalAfterCurrent(trigger.closest('.modal.show'), consentModalElement);
        });
    });

    const bookingForm = document.querySelector('[data-booking-form]');
    const bookingService = document.querySelector('[data-booking-service]');
    const calendarGrid = document.querySelector('[data-calendar-grid]');
    const calendarMonthLabel = document.querySelector('[data-calendar-month-label]');
    const calendarPrevious = document.querySelector('[data-calendar-previous]');
    const calendarNext = document.querySelector('[data-calendar-next]');
    const bookingDateInput = document.querySelector('[data-booking-date]');
    const bookingTimeInput = document.querySelector('[data-booking-time]');
    const bookingSlots = document.querySelector('[data-booking-slots]');
    const selectedDateLabel = document.querySelector('[data-selected-date-label]');
    const bookingError = document.querySelector('[data-booking-error]');
    const bookingSubmit = document.querySelector('[data-booking-submit]');
    const bookingIntake = document.querySelector('[data-booking-intake]');
    const bookingSchedule = document.querySelector('[data-booking-schedule]');
    const bookingReasons = Array.from(document.querySelectorAll('[data-booking-reason]'));
    const bookingSymptoms = Array.from(document.querySelectorAll('[data-booking-symptom]'));
    const bookingComplaintDetails = document.querySelector('[data-booking-complaint-details]');
    const bookingSymptomCount = document.querySelector('[data-booking-symptom-count]');
    const bookingDetailCount = document.querySelector('[data-booking-detail-count]');
    const bookingIntakeError = document.querySelector('[data-booking-intake-error]');

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const maximumBookingDate = new Date(today.getFullYear(), today.getMonth() + 12, 1);

    const bookingState = {
        serviceId: bookingService?.value || '',
        serviceName: '',
        viewDate: new Date(today.getFullYear(), today.getMonth(), 1),
        selectedDate: '',
        selectedTime: '',
        days: new Map(),
        calendarRequest: 0,
        slotsRequest: 0,
    };

    const pad = (value) => String(value).padStart(2, '0');
    const formatLocalDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const monthKey = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}`;
    const showBookingError = (message) => {
        if (!bookingError) return;
        bookingError.textContent = message;
        bookingError.hidden = false;
    };

    const clearBookingError = () => {
        if (!bookingError) return;
        bookingError.textContent = '';
        bookingError.hidden = true;
    };

    const showBookingIntakeError = (message) => {
        if (!bookingIntakeError) return;
        bookingIntakeError.textContent = message;
        bookingIntakeError.hidden = false;
    };

    const clearBookingIntakeError = () => {
        if (!bookingIntakeError) return;
        bookingIntakeError.textContent = '';
        bookingIntakeError.hidden = true;
    };

    const selectedSymptomCount = () => bookingSymptoms.filter((input) => input.checked).length;

    const clinicalIntakeComplete = () => {
        const selectedReason = bookingReasons.find((input) => input.checked);
        return Boolean(selectedReason);
    };

    const updateClinicalIntakeState = () => {
        const symptomCount = selectedSymptomCount();
        const isComplete = clinicalIntakeComplete();
        const hasReason = bookingReasons.some((input) => input.checked);
        const selectedReason = bookingReasons.find((input) => input.checked);
        const isNone = selectedReason?.value === 'none_of_the_above';

        if (bookingSymptomCount) {
            bookingSymptomCount.textContent = `${symptomCount} / 3 selected`;
            bookingSymptomCount.classList.toggle('limit-reached', symptomCount === 3);
        }
        if (bookingDetailCount && bookingComplaintDetails) {
            bookingDetailCount.textContent = `${bookingComplaintDetails.value.length} / 2000`;
        }
        if (bookingIntake) {
            bookingIntake.classList.toggle('complete', isComplete);
        }

        const symptomSection = document.querySelector('[data-symptom-section]');
        if (symptomSection) {
            symptomSection.hidden = !isNone;
        }

        const requestActions = document.querySelector('[data-request-actions]');
        if (requestActions) {
            requestActions.hidden = !hasReason;
        }

        updateBookingSubmit();
    };

    const updateBookingSubmit = () => {
        if (!bookingSubmit) return;
        bookingSubmit.disabled = !clinicalIntakeComplete();
    };

    const setSelectedDateLabel = () => {
        if (!selectedDateLabel) return;
        const label = selectedDateLabel.querySelector('strong');

        if (!bookingState.selectedDate) {
            if (label) label.textContent = 'Choose an available date';
            return;
        }

        const date = new Date(`${bookingState.selectedDate}T00:00:00`);
        if (label) {
            label.textContent = date.toLocaleDateString(undefined, {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            });
        }
    };

    const clearDateAndTime = () => {
        bookingState.selectedDate = '';
        bookingState.selectedTime = '';
        bookingState.days = new Map();

        if (bookingDateInput) bookingDateInput.value = '';
        if (bookingTimeInput) bookingTimeInput.value = '';
        if (bookingSlots) {
            bookingSlots.innerHTML = '<div class="booking-slots-empty"><i class="bi bi-clock"></i> Select an available date to view time slots.</div>';
        }

        setSelectedDateLabel();
        updateBookingSubmit();
    };

    const setBookingService = (serviceId) => {
        if (!bookingService) return;
        bookingService.value = serviceId || '';
        bookingState.serviceId = bookingService.value;
        bookingState.serviceName = bookingService.options[bookingService.selectedIndex]?.textContent || '';
        clearBookingError();
        clearDateAndTime();
    };

    const renderCalendar = () => {
        if (!calendarGrid) return;

        calendarGrid.replaceChildren();
        const year = bookingState.viewDate.getFullYear();
        const month = bookingState.viewDate.getMonth();
        const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
        const gridStart = new Date(year, month, 1 - firstWeekday);
        const todayKey = formatLocalDate(today);

        for (let index = 0; index < 42; index += 1) {
            const date = new Date(gridStart);
            date.setDate(gridStart.getDate() + index);
            const dateKey = formatLocalDate(date);
            const button = document.createElement('button');
            const info = bookingState.days.get(dateKey);
            const outsideMonth = date.getMonth() !== month;

            button.type = 'button';
            button.className = 'booking-calendar-day';
            button.textContent = String(date.getDate());

            if (outsideMonth) {
                button.classList.add('outside');
                button.disabled = true;
            } else if (info) {
                button.title = info.reason;
                button.dataset.date = dateKey;

                if (dateKey === todayKey) button.classList.add('today');
                if (info.fully_booked) button.classList.add('fully-booked');
                if (info.available) button.classList.add('available');
                if (!info.available && !info.fully_booked) button.classList.add('unavailable');
                if (dateKey === bookingState.selectedDate) button.classList.add('selected');
                button.disabled = !info.available;
            } else {
                button.classList.add('unavailable');
                button.disabled = true;
            }

            calendarGrid.append(button);
        }

        if (calendarMonthLabel) {
            calendarMonthLabel.textContent = bookingState.viewDate.toLocaleDateString(undefined, {
                month: 'long',
                year: 'numeric',
            });
        }
        if (calendarPrevious) {
            calendarPrevious.disabled = bookingState.viewDate <= new Date(today.getFullYear(), today.getMonth(), 1);
        }
        if (calendarNext) {
            calendarNext.disabled = bookingState.viewDate >= maximumBookingDate;
        }
    };

    const loadCalendar = async () => {
        if (!clinicalIntakeComplete()) {
            if (calendarGrid) {
                calendarGrid.innerHTML = '<div class="booking-calendar-empty">Complete the complaint details to view available dates.</div>';
            }
            return;
        }

        if (!calendarGrid || !bookingState.serviceId) {
            if (calendarGrid) {
                calendarGrid.innerHTML = '<div class="booking-calendar-empty">Choose a service to view available dates.</div>';
            }
            return;
        }

        const requestId = ++bookingState.calendarRequest;
        clearBookingError();
        calendarGrid.innerHTML = '<div class="booking-calendar-loading"><span class="spinner-border spinner-border-sm"></span> Loading availability...</div>';

        try {
            const response = await fetch(`/telemed/calendar?service_id=${encodeURIComponent(bookingState.serviceId)}&month=${monthKey(bookingState.viewDate)}`, {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok) throw new Error(payload.message || 'Unable to load the calendar.');
            if (requestId !== bookingState.calendarRequest) return;

            bookingState.days = new Map(payload.days.map((day) => [day.date, day]));
            renderCalendar();
        } catch (error) {
            if (requestId !== bookingState.calendarRequest) return;
            calendarGrid.innerHTML = '<div class="booking-calendar-error">Calendar could not be loaded. Please try again.</div>';
            showBookingError(error.message);
        }
    };

    const loadTimeSlots = async (date) => {
        if (!clinicalIntakeComplete() || !bookingSlots || !bookingState.serviceId || !date) return;
        const requestId = ++bookingState.slotsRequest;
        bookingSlots.innerHTML = '<div class="booking-slots-loading"><span class="spinner-border spinner-border-sm"></span> Checking time slots...</div>';

        try {
            const response = await fetch(`/telemed/timeslots?service_id=${encodeURIComponent(bookingState.serviceId)}&date=${encodeURIComponent(date)}`, {
                headers: { Accept: 'application/json' },
            });
            const slots = await response.json();
            if (requestId !== bookingState.slotsRequest) return;

            bookingSlots.replaceChildren();
            const availableSlots = slots.filter((slot) => !slot.blocked && slot.remaining > 0);

            if (availableSlots.length === 0) {
                bookingSlots.innerHTML = '<div class="booking-slots-fully-booked"><i class="bi bi-calendar-x"></i><strong>This date is fully booked.</strong><span>Please choose another available date.</span></div>';
                renderCalendar();
                return;
            }

            slots.forEach((slot) => {
                const button = document.createElement('button');
                const time = document.createElement('strong');
                const detail = document.createElement('small');
                button.type = 'button';
                button.className = 'booking-slot-option';
                button.disabled = Boolean(slot.blocked) || slot.remaining <= 0;
                time.textContent = slot.time_slot;
                detail.textContent = button.disabled
                    ? (slot.reason || 'No slots remaining')
                    : `${slot.remaining} slot${slot.remaining === 1 ? '' : 's'} left`;
                button.append(time, detail);

                if (bookingState.selectedTime === slot.time_slot) {
                    button.classList.add('selected');
                }

                button.addEventListener('click', () => {
                    bookingState.selectedTime = slot.time_slot;
                    if (bookingTimeInput) bookingTimeInput.value = slot.time_slot;
                    bookingSlots.querySelectorAll('.booking-slot-option').forEach((option) => {
                        option.classList.toggle('selected', option === button);
                    });
                    clearBookingError();
                    updateBookingSubmit();
                });

                bookingSlots.append(button);
            });
        } catch (error) {
            if (requestId !== bookingState.slotsRequest) return;
            bookingSlots.innerHTML = '<div class="booking-slots-fully-booked"><i class="bi bi-exclamation-circle"></i><strong>Time slots could not be loaded.</strong><span>Please try again.</span></div>';
            showBookingError(error.message);
        }
    };

    const handleClinicalIntakeChange = (shouldClearError = true) => {
        const isComplete = clinicalIntakeComplete();

        if (!isComplete) {
            clearDateAndTime();
        }

        updateClinicalIntakeState();
        if (shouldClearError) clearBookingIntakeError();

        if (isComplete && bookingState.serviceId && bookingState.days.size === 0) {
            loadCalendar();
        }
    };

    bookingReasons.forEach((input) => input.addEventListener('change', () => {
        handleClinicalIntakeChange();

        // Mobile view: "None of the above" reveals the symptom and complaint
        // choices below — bring them into view so the patient immediately sees
        // the next step instead of a screen that appears not to react.
        const revealedSection = document.querySelector('[data-symptom-section]');
        const shouldScrollToChoices = input.value === 'none_of_the_above'
            && revealedSection
            && !revealedSection.hidden
            && window.matchMedia('(max-width: 991.98px)').matches;

        if (shouldScrollToChoices) {
            revealedSection.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'start',
                inline: 'nearest',
            });
        }
    }));

    bookingSymptoms.forEach((input) => {
        input.addEventListener('change', () => {
            const exceededLimit = input.checked && selectedSymptomCount() > 3;
            if (exceededLimit) {
                input.checked = false;
                showBookingIntakeError('Please select no more than 3 symptoms.');
            }

            handleClinicalIntakeChange(!exceededLimit);

            // Mobile view: after the first symptom is chosen in step 2, bring
            // step 3 (Details about your Complaint) into view so the patient
            // continues straight to filling it in.
            const complaintStep = bookingComplaintDetails?.closest('fieldset');
            const shouldScrollToDetails = input.checked
                && selectedSymptomCount() === 1
                && complaintStep
                && window.matchMedia('(max-width: 991.98px)').matches;

            if (shouldScrollToDetails) {
                complaintStep.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                    block: 'start',
                    inline: 'nearest',
                });
            }
        });
    });

    bookingComplaintDetails?.addEventListener('input', () => handleClinicalIntakeChange());

    updateClinicalIntakeState();
    if (clinicalIntakeComplete() && bookingState.serviceId) {
        loadCalendar();
    }

    if (calendarGrid) {
        calendarGrid.addEventListener('click', (event) => {
            const button = event.target.closest('[data-date]');
            if (!button || button.disabled) return;

            bookingState.selectedDate = button.dataset.date;
            bookingState.selectedTime = '';
            if (bookingDateInput) bookingDateInput.value = bookingState.selectedDate;
            if (bookingTimeInput) bookingTimeInput.value = '';
            setSelectedDateLabel();
            renderCalendar();
            updateBookingSubmit();
            loadTimeSlots(bookingState.selectedDate);
        });
    }

    if (bookingService) {
        bookingService.addEventListener('change', () => {
            bookingState.serviceId = bookingService.value;
            bookingState.serviceName = bookingService.options[bookingService.selectedIndex]?.textContent || '';
            clearBookingError();
            clearDateAndTime();
            loadCalendar();
        });
    }

    const moveCalendar = (offset) => {
        bookingState.viewDate = new Date(
            bookingState.viewDate.getFullYear(),
            bookingState.viewDate.getMonth() + offset,
            1,
        );
        bookingState.selectedDate = '';
        bookingState.selectedTime = '';
        if (bookingDateInput) bookingDateInput.value = '';
        if (bookingTimeInput) bookingTimeInput.value = '';
        if (bookingSlots) {
            bookingSlots.innerHTML = '<div class="booking-slots-empty"><i class="bi bi-clock"></i> Select an available date to view time slots.</div>';
        }
        setSelectedDateLabel();
        updateBookingSubmit();
        loadCalendar();
    };

    calendarPrevious?.addEventListener('click', () => moveCalendar(-1));
    calendarNext?.addEventListener('click', () => moveCalendar(1));

    const showConsentError = (message) => {
        if (!consentError) return;
        consentError.textContent = message;
        consentError.hidden = false;
    };

    if (consentForm && consentSubmit) {
        consentForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            consentSubmit.disabled = true;
            const label = consentSubmit.querySelector('span');
            if (label) label.textContent = 'Recording consent...';
            if (consentError) consentError.hidden = true;

            try {
                const response = await fetch(consentForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(consentForm),
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Consent could not be recorded.');

                const serviceId = payload.service_id || consentServiceInput?.value || '';
                setBookingService(serviceId);

                // Only divert to the "already have an appointment" notice when
                // the consultation type the user picked is already taken.
                // Otherwise the booking modal opens so the patient can still
                // request the consultation type they do not have yet.
                const limits = payload.booking_limits ?? { face: false, tele: false };
                const serviceMode = consentServiceModeInput?.value === 'FACE' ? 'FACE' : 'TELE';
                const requestedModeTaken = serviceMode === 'FACE' ? limits.face : limits.tele;

                if (payload.active_appointment) {
                    populateActiveAppointment(payload.active_appointment);
                }

                const nextModalElement = requestedModeTaken
                    ? activeAppointmentModalElement
                    : bookingModalElement;

                if (consentModalElement && nextModalElement) {
                    consentModalElement.addEventListener('hidden.bs.modal', () => {
                        getModal(nextModalElement).show();
                    }, { once: true });
                    getModal(consentModalElement).hide();
                } else if (nextModalElement) {
                    getModal(nextModalElement).show();
                }
            } catch (error) {
                showConsentError(error.message);
            } finally {
                consentSubmit.disabled = false;
                if (label) label.textContent = 'I AGREE & CONTINUE';
            }
        });
    }

    if (bookingModalElement) {
        bookingModalElement.addEventListener('show.bs.modal', () => {
            setBookingService(bookingService?.value || '');
            loadCalendar();
        });
    }

    if (bookingForm && bookingSubmit) {
        const originalSubmitMarkup = bookingSubmit.innerHTML;

        // Each consultation type is limited on its own: a pending face-to-face
        // request blocks another face-to-face request but not a telemedicine one.
        const isFaceLimited = bookingForm.dataset.limitFace === 'true';
        const isTeleLimited = bookingForm.dataset.limitTele === 'true';
        const limitedMessage = (mode) => {
            const label = mode === 'FACE' ? 'face-to-face' : 'telemedicine';
            return `You already have a pending or active ${label} request. Please wait for the triage team to process it before submitting another ${label} request.`;
        };

        bookingForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearBookingError();
            clearBookingIntakeError();

            const selectedReason = bookingReasons.find((input) => input.checked);
            const reasonValue = selectedReason?.value || '';
            const isNone = reasonValue === 'none_of_the_above';

            // Specific consultation type → face-to-face request (no symptoms needed).
            if (reasonValue !== '' && !isNone) {
                if (isFaceLimited) {
                    showBookingIntakeError(limitedMessage('FACE'));
                    return;
                }
                bookingSubmit.disabled = true;
                bookingSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';
                try {
                    const response = await fetch(bookingForm.action, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(bookingForm),
                    });
                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error((payload.errors ? Object.values(payload.errors).flat().join(' ') : payload.message) || 'Your request could not be submitted.');
                    }
                    window.location.assign(payload.appointments_url || '/telemed/my-appointments');
                } catch (error) {
                    showBookingIntakeError(error.message);
                    bookingSubmit.innerHTML = originalSubmitMarkup;
                    bookingSubmit.disabled = false;
                }
                return;
            }

            // None of the above → telemedicine request with symptoms.
            if (!selectedReason) {
                showBookingIntakeError('Please choose what you need to consult about.');
                return;
            }

            if (isTeleLimited) {
                showBookingIntakeError(limitedMessage('TELE'));
                return;
            }

            const symptomCount = selectedSymptomCount();
            const details = bookingComplaintDetails?.value.trim() ?? '';

            if (symptomCount < 1 || symptomCount > 3 || details.length < 3) {
                showBookingIntakeError('Please select 1–3 symptoms and provide details about your complaint.');
                updateClinicalIntakeState();
                return;
            }

            bookingSubmit.disabled = true;
            bookingSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';

            try {
                const response = await fetch(bookingForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(bookingForm),
                });
                const payload = await response.json();
                if (!response.ok) {
                    const validationMessage = payload.errors
                        ? Object.values(payload.errors).flat().join(' ')
                        : payload.message;
                    throw new Error(validationMessage || 'Your request could not be submitted.');
                }

                window.location.assign(payload.appointments_url || '/telemed/my-appointments');
            } catch (error) {
                showBookingIntakeError(error.message);
                bookingSubmit.innerHTML = originalSubmitMarkup;
                bookingSubmit.disabled = false;
            }
        });
    }

    document.querySelector('[data-booking-qr-expand]')?.addEventListener('click', () => {
        const source = bookingQrModalElement?.querySelector('[data-booking-qr-image]');
        const enlarged = bookingQrEnlargeModalElement?.querySelector('[data-booking-qr-enlarged-image]');
        if (!source || !enlarged || !bookingQrEnlargeModalElement) return;

        enlarged.src = source.src;
        getModal(bookingQrEnlargeModalElement).show();
    });

    document.querySelectorAll('[data-qr-expand]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const modalElement = document.querySelector(trigger.dataset.qrExpand);
            const enlargedImage = modalElement?.querySelector('[data-qr-enlarged-image]');
            if (!modalElement || !enlargedImage) return;

            enlargedImage.src = trigger.dataset.qrSrc;
            enlargedImage.alt = trigger.dataset.qrAlt || 'Enlarged appointment QR code';
            getModal(modalElement).show();
        });
    });

    document.querySelectorAll('[data-open-cancelled-appointment]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const service = cancelledModalElement?.querySelector('[data-cancelled-service]');
            const date = cancelledModalElement?.querySelector('[data-cancelled-date]');
            const time = cancelledModalElement?.querySelector('[data-cancelled-time]');

            if (service) service.textContent = trigger.dataset.cancelledService || 'Consultation';
            if (date) date.textContent = trigger.dataset.cancelledDate || '—';
            if (time) time.textContent = trigger.dataset.cancelledTime || '—';

            showModalAfterCurrent(trigger.closest('.modal.show'), cancelledModalElement);
        });
    });

    document.querySelectorAll('[data-open-appointment-cancel]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const cancelId = trigger.dataset.cancelId;
            const cancelInput = appointmentCancelModalElement?.querySelector('[data-appointment-cancel-id]');
            const cancelReason = appointmentCancelModalElement?.querySelector('[data-appointment-cancel-reason]');

            if (!cancelId || !cancelInput) return;

            cancelInput.value = cancelId;
            if (cancelReason) cancelReason.value = '';

            showModalAfterCurrent(trigger.closest('.modal.show'), appointmentCancelModalElement);
        });
    });

    const searchForm = document.querySelector('[data-dashboard-search-form]');
    const searchInput = document.querySelector('[data-dashboard-search]');
    const searchableItems = Array.from(document.querySelectorAll('[data-dashboard-searchable]'));
    const searchEmptyState = document.querySelector('[data-dashboard-search-empty]');

    if (searchForm && searchInput && searchEmptyState) {
        const filterDashboard = () => {
            const term = searchInput.value.trim().toLowerCase();
            let visibleItems = 0;

            searchableItems.forEach((item) => {
                const matches = term === '' || (item.dataset.searchText || '').includes(term);
                item.hidden = !matches;
                visibleItems += matches ? 1 : 0;
            });

            searchEmptyState.hidden = term === '' || visibleItems > 0;
        };

        searchInput.addEventListener('input', filterDashboard);
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                searchInput.value = '';
                filterDashboard();
                searchInput.blur();
            }
        });
        searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            filterDashboard();
        });
    }
});
