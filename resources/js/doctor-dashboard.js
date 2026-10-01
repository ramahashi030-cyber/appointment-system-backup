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
});
