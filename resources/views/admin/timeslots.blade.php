@extends('layouts.admin')

@section('title', $config['title'])

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')

    {{-- Desktop/PC only: the trust row in the banner below is hidden on phones. --}}
    <style>
        @media (max-width: 767.98px) {
            .admin-telemedicine-trust[aria-label="Time slot features"] {
                display: none !important;
            }
        }
    </style>

    <div class="admin-dashboard-content admin-doctor-content admin-timeslots-page"
         data-timeslots-page
         data-service-type="{{ $serviceType }}"
         data-store-url="{{ route("admin.timeslots.{$serviceType}.store") }}"
         data-data-url="{{ route("admin.timeslots.{$serviceType}.data") }}"
         data-slots-url="{{ route("admin.timeslots.{$serviceType}.slots") }}"
         data-update-url="{{ route("admin.timeslots.{$serviceType}.update", ['timeslot' => '__ID__']) }}"
         data-destroy-url="{{ route("admin.timeslots.{$serviceType}.destroy", ['timeslot' => '__ID__']) }}"
         data-back-url="{{ $serviceType === 'telemedicine' ? route('admin.services.telemedicine') : route('admin.services.face-to-face') }}">

        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="timeslotsTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi {{ $config['banner_icon'] }}"></i>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="timeslotsTitle">{{ $config['title'] }}</h1>
                    <p class="admin-telemedicine-welcome">Timeslot Management</p>
                    <p class="admin-telemedicine-description">{{ $config['description'] }}</p>
                    <div class="admin-telemedicine-trust" aria-label="Time slot features">
                        <span><i class="bi bi-clock-fill" aria-hidden="true"></i> Time Slot Management</span>
                        <b aria-hidden="true">•</b>
                        <span>Scheduling</span>
                        <b aria-hidden="true">•</b>
                        <span>Capacity Limits</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ADD UNAVAILABLE TIMESLOT FORM --}}
        <section class="admin-panel" aria-labelledby="addTimeslotTitle">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-plus-circle-fill" aria-hidden="true"></i>
                    <h2 id="addTimeslotTitle">Add Unavailable Timeslot</h2>
                </div>
            </header>

            <div class="admin-doctor-form">
                <form id="addTimeslotForm" class="row g-3 align-items-end">
                    @csrf

                    <div class="col-md-3">
                        <label for="timeslotService" class="form-label">Service <span class="text-danger">*</span></label>
                        <select class="form-select" id="timeslotService" name="service_id" required>
                            <option value="">Select Service</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="timeslotDate" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="timeslotDate" name="unavailable_date" required>
                    </div>

                    <div class="col-md-2">
                        <label for="timeslotSlot" class="form-label">Time Slot <span class="text-danger">*</span></label>
                        <select class="form-select" id="timeslotSlot" name="timeslot_id" required disabled>
                            <option value="">Select Service First</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="timeslotReason" class="form-label">Reason</label>
                        <input type="text" class="form-control" id="timeslotReason" name="reason"
                               placeholder="Reason (optional)" maxlength="255">
                    </div>

                    <div class="col-md-2">
                        <button type="submit" id="addTimeslotBtn" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            <span>Add</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- UNAVAILABLE TIMESLOTS TABLE --}}
        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="timeslotsListTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                    <h2 id="timeslotsListTitle">Unavailable Timeslots</h2>
                </div>
            </header>

            <div class="admin-doctor-table-wrap" data-doctor-table-wrap>
                <table class="admin-doctor-table admin-timeslot-table">
                    <caption class="visually-hidden">{{ $config['title'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Service</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time Slot</th>
                            <th scope="col">Reason</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="timeslotsTableBody">
                        {{-- Rows will be populated via AJAX --}}
                    </tbody>
                </table>

                {{-- Empty state --}}
                <div id="emptyState" class="admin-doctor-empty d-none">
                    <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                    <strong>No unavailable timeslots.</strong>
                    <span>Add one using the form above.</span>
                </div>
            </div>
        </section>

        {{-- BACK TO SERVICES BUTTON --}}
        <div class="admin-doctor-form-actions">
            <a href="#" id="backToServicesBtn" class="admin-secondary-button">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>Back to Services</span>
            </a>
        </div>
    </div>

    {{-- EDIT TIMESLOT MODAL
         Same banner header, icon mark and section card as the other admin modals;
         all field ids/names and the novalidate submit flow are unchanged. --}}
    <div class="modal fade admin-doctor-modal admin-service-modal" id="editTimeslotModal" tabindex="-1"
         aria-labelledby="editTimeslotModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-pencil-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="editTimeslotModalTitle">Edit unavailable timeslot</h2>
                            <p class="admin-telemedicine-description">Update the date, time slot or reason for this unavailable period.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <form id="editTimeslotForm" class="admin-doctor-form" novalidate>
                        @csrf
                        <input type="hidden" id="editTimeslotId">

                        <div class="admin-panel">
                            <div class="admin-panel-title">
                                <i class="bi bi-calendar2-x-fill" aria-hidden="true"></i>
                                <h3>Unavailable period</h3>
                            </div>
                            <div class="form-field">
                                <label for="editService">Service</label>
                                <input type="text" class="form-control" id="editService" readonly>
                            </div>
                            <div class="form-field">
                                <label for="editDate">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="editDate" name="unavailable_date" required>
                            </div>
                            <div class="form-field">
                                <label for="editSlot">Time Slot <span class="text-danger">*</span></label>
                                <select class="form-select" id="editSlot" name="timeslot_id" required></select>
                            </div>
                            <div class="form-field">
                                <label for="editReason">Reason</label>
                                <input type="text" class="form-control" id="editReason" name="reason" maxlength="255">
                            </div>
                        </div>

                        <div class="admin-doctor-form-actions">
                            <button type="submit" class="admin-primary-button" id="editTimeslotSave">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                <span>Save</span>
                            </button>
                            <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- DELETE TIMESLOT MODAL
         Mirror of the Services Delete Service modal: same header banner, icon
         treatment, static backdrop, Delete / Cancel buttons. The selected
         timeslot's details are filled from the row's data attributes, and
         confirming runs the existing AJAX destroy flow. --}}
    <div class="modal fade admin-doctor-modal admin-service-modal" id="deleteTimeslotModal" tabindex="-1"
         aria-labelledby="deleteTimeslotModalTitle" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-trash3-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="deleteTimeslotModalTitle">Delete Timeslot</h2>
                            <p class="admin-telemedicine-description">This removes the unavailable time slot from the schedule.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <p class="admin-service-delete-question">Delete this timeslot?</p>
                    <p class="admin-service-delete-name" data-delete-name></p>
                    <p class="text-danger small">This action cannot be undone.</p>
                    <div class="admin-doctor-form-actions admin-service-form-actions">
                        <button type="button" class="admin-danger-button" data-delete-confirm>
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                            <span>Delete</span>
                        </button>
                        <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* =============================================================
           Standard Admin modal system — source of truth: the Add Patient
           modal (#createPatientModal, sized by pages/patients.css). These
           rules only restate that modal's measurements for the other
           modals on this page. Markup, behaviour and content are untouched.
           ============================================================= */

        /* One width for every Admin modal: the Add Patient dialog
           (width: calc(100% - 30px); max-width: 640px). Phones keep the
           shared calc(100% - 16px) rule from admin-css/responsive.css. */
        .admin-doctor-modal .modal-dialog {
            max-width: 640px;
        }

        /* Content spacing follows the reference (12px row gap, 4px label gap). */
        .admin-doctor-modal .admin-doctor-form {
            gap: 12px;
        }

        .admin-doctor-modal .admin-doctor-form-grid {
            gap: 12px;
        }

        .admin-doctor-modal .admin-doctor-form .form-field {
            gap: 4px;
        }

        /* Field sizing follows the reference (12px labels, 13px controls). */
        .admin-doctor-modal .admin-doctor-form .form-field > label,
        .admin-doctor-modal .admin-doctor-form .form-field > label span {
            font-size: 12px;
        }

        .admin-doctor-modal .admin-doctor-form .form-control,
        .admin-doctor-modal .admin-doctor-form .form-select {
            min-height: 0;
            padding: 0.375rem 0.5rem;
            font-size: 13px;
        }

        /* Action area mirrors the reference footer: centered, 8px apart,
           with no separator rule above the buttons. The row keeps its
           existing top spacing (margin from pages/holidays.css + padding
           from doctor.css): these rows sit inside forms, so they do not get
           the modal-body padding that separates the reference footer. */
        .admin-doctor-modal .admin-doctor-form-actions {
            justify-content: center;
            gap: 8px;
            border-top: none;
        }

        .admin-doctor-modal .modal-footer {
            justify-content: center;
            border-top: none;
            padding-top: 0;
            gap: 8px;
        }

        /* Button type matches the reference: 13px inside every modal. */
        .admin-doctor-modal .admin-primary-button,
        .admin-doctor-modal .admin-secondary-button,
        .admin-doctor-modal .admin-danger-button {
            font-size: 13px;
        }

        /* Service-styled modals (.admin-service-modal) carry doubled-up rules
           in pages/services.css, so the standard is restated here with the
           same (or higher) specificity. The slot editor and hint copy are
           content-specific and stay untouched. */
        .admin-doctor-modal.admin-service-modal .modal-dialog {
            max-width: 640px;
        }

        .admin-doctor-modal.admin-service-modal .modal-body {
            padding: 20px;
        }

        .admin-doctor-modal.admin-service-modal .admin-service-form.admin-doctor-form .form-field {
            gap: 4px;
        }

        .admin-doctor-modal.admin-service-modal .admin-service-form.admin-doctor-form .form-control,
        .admin-doctor-modal.admin-service-modal .admin-service-form.admin-doctor-form .form-select {
            min-height: 0;
            padding: 0.375rem 0.5rem;
        }

        .admin-doctor-modal.admin-service-modal .admin-doctor-form-actions,
        .admin-doctor-modal.admin-service-modal .admin-service-form-actions {
            justify-content: center;
            gap: 8px;
            padding-top: 0;
            border-top: none;
        }

        .admin-doctor-modal.admin-service-modal .admin-primary-button,
        .admin-doctor-modal.admin-service-modal .admin-secondary-button,
        .admin-doctor-modal.admin-service-modal .admin-danger-button {
            font-size: 13px;
        }

        /* Phones: body padding and action buttons follow the reference. */
        @media (max-width: 767.98px) {
            .admin-doctor-modal.admin-service-modal .modal-body {
                padding: 14px;
            }

            .admin-doctor-modal .admin-doctor-form-actions {
                flex-wrap: wrap;
            }

            .admin-doctor-modal .admin-doctor-form-actions > * {
                flex: 0 0 auto;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const pageEl = document.querySelector('[data-timeslots-page]');
            if (!pageEl) return;

            const storeUrl = pageEl.dataset.storeUrl;
            const dataUrl = pageEl.dataset.dataUrl;
            const slotsUrl = pageEl.dataset.slotsUrl;
            const updateUrlTemplate = pageEl.dataset.updateUrl;
            const destroyUrlTemplate = pageEl.dataset.destroyUrl;
            const backUrl = pageEl.dataset.backUrl;

            const form = document.getElementById('addTimeslotForm');
            const addBtn = document.getElementById('addTimeslotBtn');
            const serviceSelect = document.getElementById('timeslotService');
            const slotSelect = document.getElementById('timeslotSlot');
            const tableBody = document.getElementById('timeslotsTableBody');
            const emptyState = document.getElementById('emptyState');
            const backBtn = document.getElementById('backToServicesBtn');

            const editModalEl = document.getElementById('editTimeslotModal');
            const editForm = document.getElementById('editTimeslotForm');
            const editSave = document.getElementById('editTimeslotSave');
            const editId = document.getElementById('editTimeslotId');
            const editService = document.getElementById('editService');
            const editDate = document.getElementById('editDate');
            const editSlot = document.getElementById('editSlot');
            const editReason = document.getElementById('editReason');

            const jsonHeaders = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            };

            let rowsById = {};

            const csrfToken = () =>
                document.querySelector('meta[name="csrf-token"]')?.content
                || form.querySelector('[name="_token"]').value;

            if (backBtn) {
                backBtn.href = backUrl;
            }

            loadTimeslots();

            serviceSelect.addEventListener('change', () => {
                loadSlots(serviceSelect.value, slotSelect);
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearErrors(form);

                const formData = new FormData(form);

                addBtn.disabled = true;
                addBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

                try {
                    const response = await fetch(storeUrl, {
                        method: 'POST',
                        headers: { ...jsonHeaders, 'X-CSRF-TOKEN': csrfToken() },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    showToast('success', data.message);
                    form.reset();
                    loadSlots('', slotSelect);
                    loadTimeslots();
                } catch (error) {
                    if (error.errors) {
                        showValidationErrors(form, error.errors);
                    } else {
                        showToast('error', error.message || 'Failed to add timeslot. Please try again.');
                    }
                } finally {
                    addBtn.disabled = false;
                    addBtn.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add</span>';
                }
            });

            async function loadTimeslots() {
                try {
                    const response = await fetch(dataUrl, { headers: jsonHeaders });
                    const data = await response.json();
                    renderTimeslots(data.timeslots || []);
                } catch (error) {
                    console.error('Failed to load timeslots:', error);
                    showToast('error', 'Failed to load unavailable timeslots.');
                }
            }

            async function loadSlots(serviceId, selectEl, selectedId = null) {
                selectEl.innerHTML = '';

                if (!serviceId) {
                    selectEl.add(new Option('Select Service First', ''));
                    selectEl.disabled = true;
                    return;
                }

                selectEl.add(new Option('Loading...', ''));
                selectEl.disabled = true;

                try {
                    const response = await fetch(`${slotsUrl}?service_id=${encodeURIComponent(serviceId)}`, { headers: jsonHeaders });
                    const data = await response.json();

                    selectEl.innerHTML = '';
                    selectEl.add(new Option('Select Time Slot', ''));
                    (data.slots || []).forEach(slot => selectEl.add(new Option(slot.label, slot.id)));

                    if (selectedId) {
                        selectEl.value = String(selectedId);
                    }

                    selectEl.disabled = false;
                } catch (error) {
                    console.error('Failed to load time slots:', error);
                    selectEl.innerHTML = '';
                    selectEl.add(new Option('Failed to load', ''));
                    showToast('error', 'Failed to load time slots.');
                }
            }

            function renderTimeslots(timeslots) {
                tableBody.innerHTML = '';
                rowsById = {};

                if (timeslots.length === 0) {
                    emptyState.classList.remove('d-none');
                    return;
                }

                emptyState.classList.add('d-none');

                timeslots.forEach(item => {
                    rowsById[item.id] = item;

                    const row = document.createElement('tr');
                    row.dataset.timeslotId = item.id;
                    row.innerHTML = `
                        <td>${escapeHtml(item.id)}</td>
                        <td>${escapeHtml(item.service_name)}</td>
                        <td>${escapeHtml(item.date)}</td>
                        <td>${escapeHtml(item.time_label)}</td>
                        <td>${escapeHtml(item.reason || '')}</td>
                        <td>
                            <div class="admin-doctor-actions admin-service-actions">
                                <button type="button" class="edit-timeslot-btn"
                                        data-timeslot-id="${item.id}" aria-label="Edit unavailable timeslot">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                    <span>Edit</span>
                                </button>
                                <button type="button" class="delete-timeslot-btn"
                                        data-timeslot-id="${item.id}" aria-label="Delete unavailable timeslot">
                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            }

            tableBody.addEventListener('click', (e) => {
                const editBtn = e.target.closest('.edit-timeslot-btn');
                const deleteBtn = e.target.closest('.delete-timeslot-btn');

                if (editBtn) {
                    openEditModal(editBtn.dataset.timeslotId);
                } else if (deleteBtn) {
                    handleDelete(deleteBtn);
                }
            });

            const editModal = bootstrap.Modal.getOrCreateInstance(editModalEl);
            const deleteModalEl = document.getElementById('deleteTimeslotModal');
            const deleteModal = deleteModalEl ? bootstrap.Modal.getOrCreateInstance(deleteModalEl) : null;
            const deleteNameEl = deleteModalEl ? deleteModalEl.querySelector('[data-delete-name]') : null;
            const deleteConfirmBtn = deleteModalEl ? deleteModalEl.querySelector('[data-delete-confirm]') : null;
            let pendingDeleteId = null;
            let isDeleting = false;

            if (deleteModalEl && deleteConfirmBtn) {
                deleteConfirmBtn.addEventListener('click', () => {
                    if (!pendingDeleteId || isDeleting) {
                        return;
                    }
                    handleDeleteConfirmed(pendingDeleteId);
                });

                deleteModalEl.addEventListener('hidePrevented.bs.modal', () => {
                    const content = deleteModalEl.querySelector('.modal-content');
                    if (!content) return;
                    content.classList.remove('admin-service-modal-blocked');
                    void content.offsetWidth;
                    content.classList.add('admin-service-modal-blocked');
                    content.addEventListener('animationend', (event) => {
                        if (event.animationName === 'adminServiceModalBlocked') {
                            content.classList.remove('admin-service-modal-blocked');
                        }
                    }, { once: true });
                });
            }

            async function openEditModal(id) {
                const item = rowsById[id];
                if (!item) return;

                clearErrors(editForm);
                editId.value = item.id;
                editService.value = item.service_name;
                editDate.value = item.date;
                editReason.value = item.reason || '';

                await loadSlots(item.service_id, editSlot, item.timeslot_id);
                editModal.show();
            }

            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearErrors(editForm);

                const formData = new FormData(editForm);
                formData.append('_method', 'PUT');

                const updateUrl = updateUrlTemplate.replace('__ID__', editId.value);

                editSave.disabled = true;

                try {
                    const response = await fetch(updateUrl, {
                        method: 'POST',
                        headers: { ...jsonHeaders, 'X-CSRF-TOKEN': csrfToken() },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    editModal.hide();
                    showToast('success', data.message);
                    loadTimeslots();
                } catch (error) {
                    if (error.errors) {
                        showValidationErrors(editForm, error.errors);
                    } else {
                        showToast('error', error.message || 'Failed to update timeslot.');
                    }
                } finally {
                    editSave.disabled = false;
                }
            });

            async function handleDelete(btn) {
                const item = rowsById[btn.dataset.timeslotId];
                if (!item || !deleteModal) return;

                pendingDeleteId = item.id;
                if (deleteNameEl) {
                    deleteNameEl.textContent = `${item.service_name || ''} — ${item.date || ''} (${item.time_label || ''})`;
                }
                deleteModal.show();
            }

            async function handleDeleteConfirmed(id) {
                const item = rowsById[id];
                const destroyUrl = destroyUrlTemplate.replace('__ID__', id);

                isDeleting = true;
                if (deleteConfirmBtn) {
                    deleteConfirmBtn.disabled = true;
                    deleteConfirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Deleting...</span>';
                }

                try {
                    const response = await fetch(destroyUrl, {
                        method: 'DELETE',
                        headers: { ...jsonHeaders, 'X-CSRF-TOKEN': csrfToken() },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    if (deleteModal) deleteModal.hide();
                    showToast('success', data.message);
                    loadTimeslots();
                } catch (error) {
                    if (deleteModal) deleteModal.hide();
                    showToast('error', error.message || 'Failed to delete timeslot.');
                } finally {
                    isDeleting = false;
                    pendingDeleteId = null;
                    if (deleteConfirmBtn) {
                        deleteConfirmBtn.disabled = false;
                        deleteConfirmBtn.innerHTML = '<i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span>';
                    }
                }
            }

            function clearErrors(formEl) {
                formEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                formEl.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            }

            function showValidationErrors(formEl, errors) {
                clearErrors(formEl);

                Object.entries(errors).forEach(([field, messages]) => {
                    const input = formEl.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback d-block';
                        feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                        input.parentNode.appendChild(feedback);
                    }
                });

                const firstInvalid = formEl.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }

            function showToast(type, message) {
                document.querySelectorAll('.admin-toast').forEach(t => t.remove());

                const toast = document.createElement('div');
                toast.className = `admin-toast alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
                toast.style.cssText = 'top: 1rem; right: 1rem; z-index: 9999; min-width: 300px;';
                toast.setAttribute('role', 'alert');

                const text = document.createElement('span');
                text.textContent = message;
                toast.appendChild(text);

                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'btn-close';
                close.setAttribute('data-bs-dismiss', 'alert');
                close.setAttribute('aria-label', 'Close');
                toast.appendChild(close);

                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 150);
                }, 3000);
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text ?? '';
                return div.innerHTML;
            }
        });
    </script>
@endpush