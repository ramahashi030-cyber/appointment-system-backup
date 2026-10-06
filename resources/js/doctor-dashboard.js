import { applyRoomState } from './telemed-rooms';

const onDoctorReady = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
};

onDoctorReady(() => {
    const sidebar = document.querySelector('[data-doctor-sidebar]');
    const sidebarOverlay = document.querySelector('[data-doctor-sidebar-overlay]');
    const sidebarToggle = document.querySelector('[data-doctor-sidebar-toggle]');

    const setSidebar = (open) => {
        sidebar?.classList.toggle('open', open);
        sidebarOverlay?.classList.toggle('show', open);
        document.body.classList.toggle('doctor-sidebar-open', open);
    };

    sidebarToggle?.addEventListener('click', () => setSidebar(true));
    sidebarOverlay?.addEventListener('click', () => setSidebar(false));
    sidebar?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setSidebar(false));
    });

    const appointmentModalElement = document.getElementById('doctorAppointmentModal');
    const appointmentModal = appointmentModalElement
        ? bootstrap.Modal.getOrCreateInstance(appointmentModalElement)
        : null;

    const setModalText = (selector, value, fallback = '—') => {
        const element = appointmentModalElement?.querySelector(selector);
        if (element) element.textContent = value || fallback;
    };

    document.querySelectorAll('[data-doctor-appointment-view]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!appointmentModalElement || !appointmentModal) return;

            setModalText('[data-doctor-modal-patient]', button.dataset.patient);
            setModalText('[data-doctor-modal-service]', button.dataset.service);
            setModalText('[data-doctor-modal-date]', button.dataset.date);
            setModalText('[data-doctor-modal-time]', button.dataset.time);
            setModalText('[data-doctor-modal-status]', button.dataset.status);
            setModalText('[data-doctor-modal-reason]', button.dataset.reason);
            setModalText('[data-doctor-modal-symptoms]', button.dataset.symptoms);
            setModalText('[data-doctor-modal-details]', button.dataset.details);

            const startLink = appointmentModalElement.querySelector('[data-doctor-modal-start]');
            if (startLink) {
                startLink.href = button.dataset.meetingLink || '#';
                startLink.classList.toggle('disabled', !button.dataset.meetingLink);
                startLink.setAttribute('aria-disabled', button.dataset.meetingLink ? 'false' : 'true');
            }

            appointmentModal.show();
        });
    });

    const localFilter = document.querySelector('[data-doctor-local-filter]');
    const filterItems = Array.from(document.querySelectorAll('[data-doctor-filter-item]'));
    const filterEmpty = document.querySelector('[data-doctor-filter-empty]');

    localFilter?.addEventListener('input', () => {
        const term = localFilter.value.trim().toLowerCase();
        let visibleItems = 0;

        filterItems.forEach((item) => {
            const matches = term === '' || (item.dataset.searchText || '').includes(term);
            item.hidden = !matches;
            if (matches) visibleItems += 1;
        });

        if (filterEmpty) filterEmpty.hidden = term === '' || visibleItems > 0;
    });

    document.querySelectorAll('[data-doctor-auto-submit]').forEach((field) => {
        field.addEventListener('change', () => field.form?.submit());
    });

    document.querySelectorAll('[data-doctor-photo-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (!file) {
                return;
            }

            const modal = input.closest('.modal');
            const avatar = modal ? modal.querySelector('.doctor-edit-avatar') : null;
            if (!avatar) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                let preview = avatar.querySelector('img');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.alt = 'Profile picture preview';
                    avatar.replaceChildren(preview);
                }
                preview.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });
    });

    document.querySelectorAll('[data-doctor-copy-link]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.dataset.doctorCopyLink || '';
            if (!value) return;

            try {
                await navigator.clipboard.writeText(value);
                const original = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check2"></i> Copied';
                window.setTimeout(() => {
                    button.innerHTML = original;
                }, 1400);
            } catch {
                window.prompt('Copy this Jitsi link:', value);
            }
        });
    });

    /* ------------------------------------------------- create-room confirm -- */

    // "Create a Room" never posts straight away: creating the room books the
    // appointment and lets the patient join, so it always goes through the
    // confirmation modal first. Without JavaScript (or without the modal) the
    // form still posts on its own and the page reloads with the same result.
    const createRoomModalElement = document.getElementById('doctorCreateRoomModal');
    const createRoomModal = createRoomModalElement
        ? bootstrap.Modal.getOrCreateInstance(createRoomModalElement)
        : null;
    const createRoomConfirm = createRoomModalElement
        ? createRoomModalElement.querySelector('[data-doctor-create-room-confirm]')
        : null;
    const createRoomError = createRoomModalElement
        ? createRoomModalElement.querySelector('[data-doctor-create-room-error]')
        : null;
    let pendingCreateRoomForm = null;

    const showCreateRoomError = (message) => {
        if (!createRoomError) return;
        createRoomError.textContent = message || '';
        createRoomError.hidden = !message;
    };

    document.addEventListener('submit', (event) => {
        const form = event.target && event.target.closest
            ? event.target.closest('[data-doctor-create-room-form]')
            : null;

        if (!form || !createRoomModal) {
            return;
        }

        event.preventDefault();
        pendingCreateRoomForm = form;
        showCreateRoomError('');
        createRoomModal.show();
    });

    createRoomConfirm?.addEventListener('click', async () => {
        const form = pendingCreateRoomForm;

        if (!form) {
            return;
        }

        createRoomConfirm.disabled = true;
        showCreateRoomError('');

        try {
            const csrf = form.querySelector('[name="_token"]');

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf ? csrf.value : '',
                },
                credentials: 'same-origin',
                body: '{}',
            });

            let payload = null;

            try {
                payload = await response.json();
            } catch (error) {
                payload = null;
            }

            if (!response.ok) {
                showCreateRoomError(
                    (payload && payload.message)
                        || 'The room could not be created. Please try again.'
                );
                return;
            }

            createRoomModal.hide();

            const row = form.closest('[data-doctor-appointment-row]');

            if (row && payload) {
                // Swap "Create a Room" for "Join the Room" and the status for
                // Booked, without waiting for a page reload.
                applyRoomState(row, payload);
            }
        } catch (error) {
            showCreateRoomError(
                'The room could not be created. Check your connection and try again.'
            );
        } finally {
            createRoomConfirm.disabled = false;
        }
    });
});
