@extends('layouts.admin')

@section('title', $config['title'])

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@php
    /*
     * Real rows for the edit modal, keyed by service id. Rendered from the
     * loaded models so the modal never shows placeholder values.
     */
    $servicePayload = $services->mapWithKeys(fn ($service): array => [
        $service->id => [
            'id' => $service->id,
            'service_name' => (string) $service->service_name,
            'homis_code' => (string) ($service->homis_code ?? ''),
            'availability_day' => $service->availableDays(),
            'timeslots' => $service->timeslots
                ->sortBy(fn ($slot): string => (string) $slot->time_slot)
                ->map(fn ($slot): array => [
                    'time_slot' => (string) $slot->time_slot,
                    'slots' => (int) $slot->slots,
                ])
                ->values()
                ->all(),
        ],
    ])->all();

    $totalSlots = $services->sum(fn ($service): int => $service->timeslots->sum('slots'));
    $totalTimeslots = $services->sum(fn ($service): int => $service->timeslots->count());

    /*
     * Which modal a rejected submission came back to. Both forms submit a hidden
     * `_form` marker precisely so this does not have to be guessed.
     */
    $failedForm = old('_form');

    /*
     * When an edit is rejected the admin's own input has to come back, not the
     * stored row, or their changes silently vanish. Seeded once, so subsequent
     * opens go back to reading the live row.
     */
    $editSeed = null;

    if ($failedForm === 'edit' && old('_service_id')) {
        $editSeed = [
            'id' => (int) old('_service_id'),
            'service_name' => (string) old('service_name', ''),
            'homis_code' => (string) old('homis_code', ''),
            'availability_day' => array_values((array) old('availability_day', [])),
            'timeslots' => collect((array) old('timeslots', []))
                ->map(fn ($slot): array => [
                    'time_slot' => (string) ($slot['time_slot'] ?? ''),
                    'slots' => (string) ($slot['slots'] ?? ''),
                ])
                ->values()
                ->all(),
        ];
    }

    /*
     * One blank timeslot row: the add form never opens empty, and this is also
     * exactly what Cancel restores the add form to.
     */
    $blankSlots = [['time_slot' => '', 'slots' => '']];

    /* Route name prefixes — used by JS to build update/destroy URLs. */
    $editRouteName = $config['route'] . '.update';
    $destroyRouteName = $config['route'] . '.destroy';
@endphp

@section('content')
    @php
        /*
         * The add form is server rendered, so when it was the form that got
         * rejected its own timeslot rows have to come back from old input.
         * Otherwise it starts from the single blank row.
         */
        $addSlotRows = $blankSlots;

        if ($failedForm === 'add') {
            $oldAddSlots = collect((array) old('timeslots', []))
                ->map(fn ($slot): array => [
                    'time_slot' => (string) ($slot['time_slot'] ?? ''),
                    'slots' => (string) ($slot['slots'] ?? ''),
                ])
                ->values()
                ->all();

            if ($oldAddSlots !== []) {
                $addSlotRows = $oldAddSlots;
            }
        }

        /* No day is pre-selected: the admin picks which days a service runs. */
        $addDefaultDays = [];

        /* Placeholder id swapped for the real one by script; resolves to a 404, never to store(). */
        $editActionTemplate = route($editRouteName, ['service' => '__ID__']);
        $destroyActionTemplate = route($destroyRouteName, ['service' => '__ID__']);
    @endphp

    {{-- 
        `admin-patients-page` is deliberately absent. That class narrows
        .admin-doctor-stat-grid to three columns, which pushed the Add Service
        button onto a row of its own. The base grid is already
        `repeat(3, 1fr) minmax(130px, auto)` — three stat cards plus the action
        button on a single line.
    --}}
    <div class="admin-dashboard-content admin-doctor-content admin-services-page"
         data-services-page
         data-service-type="{{ $config['mode'] }}">

        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="servicesTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi {{ $config['banner_icon'] }}"></i>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="servicesTitle">{{ $config['title'] }}</h1>
                    <p class="admin-telemedicine-welcome">Service Management</p>
                    <p class="admin-telemedicine-description">{{ $config['description'] }}</p>
                </div>
            </div>
        </section>

        <div class="admin-doctor-stat-grid">
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi {{ $config['icon'] }}" aria-hidden="true"></i></span>
                <span><small>Total services</small><strong>{{ number_format($services->count()) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon green"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                <span><small>Total timeslots</small><strong>{{ number_format($totalTimeslots) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon purple"><i class="bi bi-person-check-fill" aria-hidden="true"></i></span>
                <span><small>Total slots</small><strong>{{ number_format($totalSlots) }}</strong></span>
            </article>
            <button class="admin-doctor-add-button admin-doctor-stat-action" type="button"
                    data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add Service</span>
            </button>
        </div>

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="servicesRosterTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                    <h2 id="servicesRosterTitle">{{ $config['label'] }} services</h2>
                </div>
                <span class="admin-muted-text">{{ $services->count() }} service{{ $services->count() === 1 ? '' : 's' }}</span>
            </header>

            <div class="admin-doctor-table-wrap" data-doctor-table-wrap>
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">{{ $config['title'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-service-col-id">ID</th>
                            <th scope="col">Service</th>
                            <th scope="col">Available Days</th>
                            <th scope="col">Timeslot Slots</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $service)
                            @php
                                $serviceDays = $service->availableDays();
                                $serviceSlots = $service->timeslots->sortBy(fn ($slot): string => (string) $slot->time_slot);
                            @endphp
                            <tr>
                                <td><span class="admin-service-id">{{ $service->id }}</span></td>
                                <td>
                                    <div class="admin-service-name">
                                        <strong>{{ $service->service_name }}</strong>
                                        @if (filled($service->homis_code))
                                            <small>{{ $service->homis_code }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if (count($serviceDays) > 0)
                                        <div class="admin-service-days">
                                            @foreach ($serviceDays as $day)
                                                <span class="admin-service-day">{{ $day }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="admin-doctor-secondary-text">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($serviceSlots->isNotEmpty())
                                        <div class="admin-service-slots">
                                            @foreach ($serviceSlots as $slot)
                                                <span class="admin-service-slot">
                                                    <span class="admin-service-slot-time">{{ $slot->time_slot }}</span>
                                                    <span class="admin-service-slot-cap">{{ (int) $slot->slots }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                        <small class="admin-service-slot-total">{{ $serviceSlots->sum('slots') }} slots in total</small>
                                    @else
                                        <span class="admin-doctor-secondary-text">No timeslots</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="admin-doctor-actions admin-service-actions">
                                        <button type="button" data-service-edit="{{ $service->id }}">
                                            <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                            <span>Edit</span>
                                        </button>
                                        <button type="button" class="text-danger"
                                                data-service-delete="{{ $service->id }}"
                                                data-service-name="{{ $service->service_name }}"
                                                data-service-appointments="{{ (int) ($appointmentCounts[$service->id] ?? 0) }}">
                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="admin-doctor-empty">
                                        <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                                        <strong>No {{ strtolower($config['label']) }} services found</strong>
                                        <span>{{ $config['empty_hint'] }}</span>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#addServiceModal">Add Service</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- ADD SERVICE MODAL
         Static backdrop: an outside click or Escape does not close it, the
         script blinks the border instead. Only X and Cancel close it. --}}
    <div class="modal fade admin-doctor-modal admin-service-modal" id="addServiceModal" tabindex="-1"
         aria-labelledby="addServiceModalTitle" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-plus-lg"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="addServiceModalTitle">Add Service</h2>
                            <p class="admin-telemedicine-description">Create a {{ strtolower($config['label']) }} service with its available days and timeslots.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <form method="POST" id="addServiceForm" action="{{ route($config['route'] . '.store') }}">
                        @csrf
                        @include('admin.services._form', [
                            'prefix' => 'addService',
                            'formId' => 'addServiceForm',
                            'formAction' => route($config['route'] . '.store'),
                            'submitLabel' => 'Create service',
                            'defaultDays' => $addDefaultDays,
                            'slotRows' => $addSlotRows,
                        ])
                        <div class="admin-doctor-form-actions admin-service-form-actions">
                            <button type="submit" class="admin-primary-button">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                <span>Create service</span>
                            </button>
                            <button type="button" class="admin-secondary-button" data-service-cancel>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- EDIT SERVICE MODAL --}}
    <div class="modal fade admin-doctor-modal admin-service-modal" id="editServiceModal" tabindex="-1"
         aria-labelledby="editServiceModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-pencil-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="editServiceModalTitle">Edit Service</h2>
                            <p class="admin-telemedicine-description">Update the service name, available days and timeslot capacities.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <form method="POST" id="editServiceForm" action="{{ $editActionTemplate }}"
                          data-action-template="{{ $editActionTemplate }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_service_id" id="editServiceId" value="">
                        @include('admin.services._form', [
                            'prefix' => 'editService',
                            'formId' => 'editServiceForm',
                            'formAction' => $editActionTemplate,
                            'submitLabel' => 'Update',
                            'defaultDays' => [],
                            'slotRows' => [],
                        ])
                        <div class="admin-doctor-form-actions admin-service-form-actions">
                            <button type="submit" class="admin-primary-button">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                <span>Update</span>
                            </button>
                            <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- DELETE SERVICE MODAL --}}
    <div class="modal fade admin-doctor-modal admin-service-modal admin-service-modal-narrow" id="deleteServiceModal" tabindex="-1"
         aria-labelledby="deleteServiceModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-trash3-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="deleteServiceModalTitle">Delete Service</h2>
                            <p class="admin-telemedicine-description">This also removes its timeslots.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <form method="POST" id="deleteServiceForm" action="{{ $destroyActionTemplate }}"
                          data-action-template="{{ $destroyActionTemplate }}">
                        @csrf
                        @method('DELETE')
                        <p class="admin-service-delete-question">Delete this service?</p>
                        <p class="admin-service-delete-name" data-delete-name></p>
                        <p class="text-danger small" data-delete-warning hidden></p>
                        <div class="admin-doctor-form-actions admin-service-form-actions">
                            <button type="submit" class="admin-danger-button" data-delete-confirm>
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                <span>Delete</span>
                            </button>
                            <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        /*
         * Wrapped in an IIFE and guarded because the admin layout swaps pages by
         * AJAX and re-evaluates every inline script with $.globalEval: top-level
         * const declarations would collide on the second visit.
         */
        (function () {
            'use strict';

            const root = document.querySelector('[data-services-page]');

            if (!root || root.dataset.servicesReady === '1') {
                return;
            }

            root.dataset.servicesReady = '1';

            const services = {{ \Illuminate\Support\Js::from($servicePayload) }};
            const editSeed = {{ \Illuminate\Support\Js::from($editSeed) }};
            const failedForm = {{ \Illuminate\Support\Js::from($failedForm) }};
            const openCreate = {{ \Illuminate\Support\Js::from((bool) $createMode) }};
            const openEditId = {{ \Illuminate\Support\Js::from($editServiceId) }};
            const addDefaultDays = {{ \Illuminate\Support\Js::from($addDefaultDays) }};

            const addEl = document.getElementById('addServiceModal');
            const editEl = document.getElementById('editServiceModal');
            const deleteEl = document.getElementById('deleteServiceModal');
            const addForm = document.getElementById('addServiceForm');
            const editForm = document.getElementById('editServiceForm');
            const deleteForm = document.getElementById('deleteServiceForm');

            if (!addEl || !editEl || !deleteEl || !addForm || !editForm || !deleteForm) {
                return;
            }

            const addModal = bootstrap.Modal.getOrCreateInstance(addEl);
            const editModal = bootstrap.Modal.getOrCreateInstance(editEl);
            const deleteModal = bootstrap.Modal.getOrCreateInstance(deleteEl);

            const blankSlot = function () {
                return { time_slot: '', slots: '' };
            };

            const field = function (form, name) {
                return form.querySelector('[name="' + name + '"]');
            };

            /* ------------------------------------------------ timeslot rows */

            function createSlotEditor(form) {
                const editor = form.querySelector('[data-slot-editor]');
                const rowsEl = editor.querySelector('[data-slot-rows]');
                const summaryEl = editor.querySelector('[data-slot-summary]');

                function rows() {
                    return Array.from(rowsEl.querySelectorAll('[data-slot-row]'));
                }

                /* Keeps names contiguous (timeslots[0], [1], ...) so validation errors map back. */
                function refresh() {
                    const list = rows();
                    let total = 0;

                    list.forEach(function (row, index) {
                        const inputs = row.querySelectorAll('input');
                        inputs[0].name = 'timeslots[' + index + '][time_slot]';
                        inputs[1].name = 'timeslots[' + index + '][slots]';
                        total += parseInt(inputs[1].value, 10) || 0;

                        const remove = row.querySelector('[data-slot-remove]');
                        if (remove) {
                            remove.disabled = list.length === 1;
                        }
                    });

                    if (summaryEl) {
                        summaryEl.textContent = list.length + (list.length === 1 ? ' timeslot' : ' timeslots')
                            + ' \u00b7 ' + total + ' slots in total';
                    }
                }

                function makeRow(slot) {
                    const row = document.createElement('div');
                    row.className = 'admin-service-slot-editor-row';
                    row.setAttribute('data-slot-row', '');
                    row.innerHTML = '<input type="text" class="form-control" placeholder="08:00 - 10:00" required maxlength="50" aria-label="Time slot" autocomplete="off">'
                        + '<input type="number" class="form-control" placeholder="6" required min="0" max="999" step="1" aria-label="Slot capacity">'
                        + '<button type="button" class="admin-service-slot-remove" data-slot-remove aria-label="Remove time slot"><i class="bi bi-x-lg" aria-hidden="true"></i></button>';

                    const inputs = row.querySelectorAll('input');
                    inputs[0].value = slot && slot.time_slot != null ? slot.time_slot : '';
                    inputs[1].value = slot && slot.slots != null ? slot.slots : '';

                    return row;
                }

                function setRows(slots) {
                    rowsEl.replaceChildren();
                    (slots && slots.length ? slots : [blankSlot()]).forEach(function (slot) {
                        rowsEl.appendChild(makeRow(slot));
                    });
                    refresh();
                }

                editor.addEventListener('click', function (event) {
                    if (event.target.closest('[data-slot-add]')) {
                        const row = makeRow(blankSlot());
                        rowsEl.appendChild(row);
                        refresh();
                        row.querySelector('input').focus();

                        return;
                    }

                    const remove = event.target.closest('[data-slot-remove]');

                    if (remove && rows().length > 1) {
                        remove.closest('[data-slot-row]').remove();
                        refresh();
                    }
                });

                editor.addEventListener('input', refresh);

                refresh();

                return { setRows: setRows, refresh: refresh };
            }

            const addEditor = createSlotEditor(addForm);
            const editEditor = createSlotEditor(editForm);

            /* ------------------------------------------------------ helpers */

            function setDays(form, days) {
                form.querySelectorAll('input[name="availability_day[]"]').forEach(function (box) {
                    box.checked = days.indexOf(box.value) !== -1;
                });
            }

            function clearErrors(form) {
                form.querySelectorAll('.invalid-feedback').forEach(function (node) {
                    node.remove();
                });
            }

            /* Server rendered text-field errors have no .is-invalid sibling, so Bootstrap hides them. */
            function revealErrors(form) {
                form.querySelectorAll('.invalid-feedback').forEach(function (node) {
                    node.classList.add('d-block');
                });
            }

            function resetAddForm() {
                field(addForm, 'service_name').value = '';
                field(addForm, 'homis_code').value = '';
                setDays(addForm, addDefaultDays);
                addEditor.setRows([blankSlot()]);
                clearErrors(addForm);
            }

            function fillEdit(data) {
                const template = editForm.dataset.actionTemplate;

                editForm.setAttribute('action', template.replace('__ID__', encodeURIComponent(data.id)));
                document.getElementById('editServiceId').value = data.id;
                field(editForm, 'service_name').value = data.service_name || '';
                field(editForm, 'homis_code').value = data.homis_code || '';
                setDays(editForm, data.availability_day || []);
                editEditor.setRows(Array.isArray(data.timeslots) && data.timeslots.length ? data.timeslots : [blankSlot()]);
            }

            /* ------------------------------------------------- add modal */

            const addContent = addEl.querySelector('.modal-content');

            /* Outside click / Escape on the static modal: blink the border once, change nothing else. */
            addEl.addEventListener('hidePrevented.bs.modal', function () {
                addContent.classList.remove('admin-service-modal-blocked');
                void addContent.offsetWidth;
                addContent.classList.add('admin-service-modal-blocked');
            });

            addContent.addEventListener('animationend', function (event) {
                if (event.animationName === 'adminServiceModalBlocked') {
                    addContent.classList.remove('admin-service-modal-blocked');
                }
            });

            /* Cancel resets once the modal has finished closing; X (data-bs-dismiss) just closes. */
            addEl.querySelector('[data-service-cancel]').addEventListener('click', function () {
                addEl.addEventListener('hidden.bs.modal', resetAddForm, { once: true });
                addModal.hide();
            });

            /* ------------------------------------------------ edit modal */

            root.querySelectorAll('[data-service-edit]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const data = services[button.dataset.serviceEdit];

                    if (!data) {
                        return;
                    }

                    clearErrors(editForm);
                    fillEdit(data);
                    editModal.show();
                });
            });

            /* ---------------------------------------------- delete modal */

            const deleteName = deleteEl.querySelector('[data-delete-name]');
            const deleteWarning = deleteEl.querySelector('[data-delete-warning]');
            const deleteConfirm = deleteEl.querySelector('[data-delete-confirm]');

            root.querySelectorAll('[data-service-delete]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const count = parseInt(button.dataset.serviceAppointments, 10) || 0;
                    const template = deleteForm.dataset.actionTemplate;

                    deleteForm.setAttribute('action', template.replace('__ID__', encodeURIComponent(button.dataset.serviceDelete)));
                    deleteName.textContent = button.dataset.serviceName || '';

                    /* The controller refuses this delete as well; disabling it just says why up front. */
                    deleteWarning.hidden = count === 0;
                    deleteWarning.textContent = count === 0
                        ? ''
                        : count + ' appointment' + (count === 1 ? '' : 's')
                            + ' still reference this service. Reassign or cancel them first.';
                    deleteConfirm.disabled = count > 0;

                    deleteModal.show();
                });
            });

            /* --------------------------------- initial state after load */

            if (failedForm === 'edit' && editSeed) {
                resetAddForm();
                fillEdit(editSeed);
                revealErrors(editForm);
                editModal.show();
            } else if (failedForm === 'add') {
                clearErrors(editForm);
                addEditor.refresh();
                revealErrors(addForm);
                addModal.show();
            } else if (openEditId && services[openEditId]) {
                fillEdit(services[openEditId]);
                editModal.show();
            } else if (openCreate) {
                addModal.show();
            }
        })();
    </script>
@endpush