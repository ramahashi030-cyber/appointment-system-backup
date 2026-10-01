@extends('layouts.admin')

@section('title', $config['title'])

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    {{-- Page-scoped fixes: even spacing between panels, and light surfaces to match the patients page. --}}
    <style>
        .admin-dashboard-content.admin-timeslots-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .admin-dashboard-content.admin-timeslots-page > * {
            margin-top: 0;
            margin-bottom: 0;
        }

        .admin-dashboard-content.admin-timeslots-page .admin-panel {
            background: #fff;
        }

        .admin-dashboard-content.admin-timeslots-page .admin-doctor-form {
            background: #fff;
            padding: 1.25rem;
        }

        .admin-dashboard-content.admin-timeslots-page .admin-doctor-form .form-label {
            color: #1e293b;
            font-weight: 600;
        }

        .admin-dashboard-content.admin-timeslots-page #addTimeslotBtn {
            min-height: 38px;
        }

        .admin-dashboard-content.admin-timeslots-page .admin-doctor-table-wrap {
            background: #fff;
        }

        .admin-dashboard-content.admin-timeslots-page .admin-doctor-table tbody td {
            background: transparent;
            color: #1e293b;
        }

        .admin-dashboard-content.admin-timeslots-page .admin-doctor-empty {
            background: #fff;
            color: #64748b;
        }

        .admin-dashboard-content.admin-timeslots-page .admin-doctor-empty strong {
            color: #334155;
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

    {{-- EDIT MODAL --}}
    <div class="modal fade" id="editTimeslotModal" tabindex="-1" aria-labelledby="editTimeslotModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editTimeslotForm" novalidate>
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="editTimeslotModalTitle">Edit unavailable timeslot</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editTimeslotId">

                        <div class="mb-3">
                            <label for="editService" class="form-label">Service</label>
                            <input type="text" class="form-control" id="editService" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="editDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="editDate" name="unavailable_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSlot" class="form-label">Time Slot</label>
                            <select class="form-select" id="editSlot" name="timeslot_id" required></select>
                        </div>
                        <div class="mb-0">
                            <label for="editReason" class="form-label">Reason</label>
                            <input type="text" class="form-control" id="editReason" name="reason" maxlength="255">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editTimeslotSave">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

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

            // Rows currently shown, keyed by id (used to fill the edit modal)
            let rowsById = {};

            const csrfToken = () =>
                document.querySelector('meta[name="csrf-token"]')?.content
                || form.querySelector('[name="_token"]').value;

            if (backBtn) {
                backBtn.href = backUrl;
            }

            loadTimeslots();

            // Service changed: load its time slots
            serviceSelect.addEventListener('change', () => {
                loadSlots(serviceSelect.value, slotSelect);
            });

            // ---------- ADD ----------
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

            // ---------- LOAD ----------
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

            // ---------- RENDER ----------
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

            // Row buttons (event delegation, so re-rendering needs no re-binding)
            tableBody.addEventListener('click', (e) => {
                const editBtn = e.target.closest('.edit-timeslot-btn');
                const deleteBtn = e.target.closest('.delete-timeslot-btn');

                if (editBtn) {
                    openEditModal(editBtn.dataset.timeslotId);
                } else if (deleteBtn) {
                    handleDelete(deleteBtn);
                }
            });

            // ---------- EDIT ----------
            const editModal = bootstrap.Modal.getOrCreateInstance(editModalEl);

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

            // ---------- DELETE ----------
            async function handleDelete(btn) {
                const item = rowsById[btn.dataset.timeslotId];
                if (!item) return;

                if (!confirm(`Delete the unavailable timeslot for "${item.service_name}" on ${item.date} (${item.time_label})?`)) {
                    return;
                }

                const destroyUrl = destroyUrlTemplate.replace('__ID__', item.id);

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                try {
                    const response = await fetch(destroyUrl, {
                        method: 'DELETE',
                        headers: { ...jsonHeaders, 'X-CSRF-TOKEN': csrfToken() },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    showToast('success', data.message);
                    loadTimeslots();
                } catch (error) {
                    showToast('error', error.message || 'Failed to delete timeslot.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span>';
                }
            }

            // ---------- HELPERS ----------
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