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
        .admin-dashboard-content.admin-holidays-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .admin-dashboard-content.admin-holidays-page > * {
            margin-top: 0;
            margin-bottom: 0;
        }

        /* Panels: light body instead of the dark navy */
        .admin-dashboard-content.admin-holidays-page .admin-panel {
            background: #fff;
        }

        /* Add Holiday form */
        .admin-dashboard-content.admin-holidays-page .admin-doctor-form {
            background: #fff;
            padding: 1.25rem;
        }

        .admin-dashboard-content.admin-holidays-page .admin-doctor-form .form-label {
            color: #1e293b;
            font-weight: 600;
        }

        .admin-dashboard-content.admin-holidays-page #addHolidayBtn {
            min-height: 42px;
        }

        /* Holiday table + empty state */
        .admin-dashboard-content.admin-holidays-page .admin-doctor-table-wrap {
            background: #fff;
        }

        .admin-dashboard-content.admin-holidays-page .admin-doctor-table tbody td {
            background: transparent;
            color: #1e293b;
        }

        .admin-dashboard-content.admin-holidays-page .admin-doctor-empty {
            background: #fff;
            color: #64748b;
        }

        .admin-dashboard-content.admin-holidays-page .admin-doctor-empty strong {
            color: #334155;
        }
    </style>

    <div class="admin-dashboard-content admin-doctor-content admin-holidays-page"
         data-holidays-page
         data-service-type="{{ $serviceType }}"
         data-store-url="{{ route("admin.holidays.{$serviceType}.store") }}"
         data-data-url="{{ route("admin.holidays.{$serviceType}.data") }}"
         data-destroy-url="{{ route("admin.holidays.{$serviceType}.destroy", ['holiday' => '__HOLIDAY_ID__']) }}"
         data-back-url="{{ $serviceType === 'telemedicine' ? route('admin.services.telemedicine') : route('admin.services.face-to-face') }}">

        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="holidaysTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi {{ $config['banner_icon'] }}"></i>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="holidaysTitle">{{ $config['title'] }}</h1>
                    <p class="admin-telemedicine-welcome">Holiday Management</p>
                    <p class="admin-telemedicine-description">{{ $config['description'] }}</p>
                </div>
            </div>
        </section>

        {{-- ADD HOLIDAY FORM --}}
        <section class="admin-panel" aria-labelledby="addHolidayTitle">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-plus-circle-fill" aria-hidden="true"></i>
                    <h2 id="addHolidayTitle">Add Holiday</h2>
                </div>
            </header>

            <div class="admin-doctor-form">
                <form id="addHolidayForm" class="row g-3 align-items-end">
                    @csrf

                    <div class="col-md-3">
                        <label for="holidayDate" class="form-label">Holiday Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="holidayDate" name="holiday_date" required>
                        @error('holiday_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="holidayDescription" class="form-label">Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="holidayDescription" name="description"
                               placeholder="e.g. Christmas Day" required maxlength="255">
                        @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <button type="submit" id="addHolidayBtn" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            <span>Add Holiday</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- HOLIDAY TABLE --}}
        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="holidaysListTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                    <h2 id="holidaysListTitle">Holiday List</h2>
                </div>
            </header>

            <div class="admin-doctor-table-wrap" data-doctor-table-wrap>
                <table class="admin-doctor-table admin-holiday-table">
                    <caption class="visually-hidden">{{ $config['title'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Description</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="holidaysTableBody">
                        {{-- Rows will be populated via AJAX --}}
                    </tbody>
                </table>

                {{-- Empty state --}}
                <div id="emptyState" class="admin-doctor-empty d-none">
                    <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                    <strong>No holidays set.</strong>
                    <span>Add a holiday using the form above.</span>
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
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const pageEl = document.querySelector('[data-holidays-page]');
            if (!pageEl) return;

            const serviceType = pageEl.dataset.serviceType;
            const storeUrl = pageEl.dataset.storeUrl;
            const dataUrl = pageEl.dataset.dataUrl;
            const destroyUrlTemplate = pageEl.dataset.destroyUrl;
            const backUrl = pageEl.dataset.backUrl;

            const form = document.getElementById('addHolidayForm');
            const addBtn = document.getElementById('addHolidayBtn');
            const tableBody = document.getElementById('holidaysTableBody');
            const emptyState = document.getElementById('emptyState');
            const backBtn = document.getElementById('backToServicesBtn');

            // Set back button URL
            if (backBtn) {
                backBtn.href = backUrl;
            }

            // Load holidays on page load
            loadHolidays();

            // Handle form submission
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(form);
                const csrfToken = formData.get('_token');

                // Disable button during request
                addBtn.disabled = true;
                addBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

                try {
                    const response = await fetch(storeUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    // Success
                    showToast('success', data.message);
                    form.reset();
                    loadHolidays();
                } catch (error) {
                    if (error.errors) {
                        // Validation errors
                        showValidationErrors(error.errors);
                    } else if (error.message) {
                        showToast('error', error.message);
                    } else {
                        showToast('error', 'Failed to add holiday. Please try again.');
                    }
                } finally {
                    addBtn.disabled = false;
                    addBtn.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add Holiday</span>';
                }
            });

            // Load holidays via AJAX
            async function loadHolidays() {
                try {
                    const response = await fetch(dataUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();
                    renderHolidays(data.holidays);
                } catch (error) {
                    console.error('Failed to load holidays:', error);
                    showToast('error', 'Failed to load holidays.');
                }
            }

            // Render holidays table
            function renderHolidays(holidays) {
                tableBody.innerHTML = '';

                if (holidays.length === 0) {
                    emptyState.classList.remove('d-none');
                    return;
                }

                emptyState.classList.add('d-none');

                holidays.forEach(holiday => {
                    const row = document.createElement('tr');
                    row.dataset.holidayId = holiday.id;
                    row.innerHTML = `
                        <td>${escapeHtml(holiday.date_formatted)}</td>
                        <td>${escapeHtml(holiday.description)}</td>
                        <td>
                            <div class="admin-doctor-actions admin-service-actions">
                                <button type="button"
                                        class="delete-holiday-btn"
                                        data-holiday-id="${holiday.id}"
                                        data-holiday-date="${escapeHtml(holiday.date_formatted)}"
                                        data-holiday-desc="${escapeHtml(holiday.description)}"
                                        aria-label="Delete holiday">
                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });

                // Attach delete handlers
                attachDeleteHandlers();
            }

            // Attach delete button handlers
            function attachDeleteHandlers() {
                document.querySelectorAll('.delete-holiday-btn').forEach(btn => {
                    btn.addEventListener('click', handleDelete);
                });
            }

            // Handle delete
            async function handleDelete(e) {
                const btn = e.currentTarget;
                const holidayId = btn.dataset.holidayId;
                const holidayDate = btn.dataset.holidayDate;
                const holidayDesc = btn.dataset.holidayDesc;

                if (!confirm(`Are you sure you want to delete "${holidayDesc}" on ${holidayDate}?`)) {
                    return;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const destroyUrl = destroyUrlTemplate.replace('__HOLIDAY_ID__', holidayId);

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                try {
                    const response = await fetch(destroyUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    showToast('success', data.message);
                    loadHolidays();
                } catch (error) {
                    showToast('error', error.message || 'Failed to delete holiday.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span>';
                }
            }

            // Show validation errors
            function showValidationErrors(errors) {
                // Clear previous errors
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                Object.entries(errors).forEach(([field, messages]) => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback d-block';
                        feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                        input.parentNode.appendChild(feedback);
                    }
                });

                // Focus first invalid field
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }

            // Toast notification
            function showToast(type, message) {
                // Remove existing toasts
                document.querySelectorAll('.admin-toast').forEach(t => t.remove());

                const toast = document.createElement('div');
                toast.className = `admin-toast alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
                toast.style.cssText = 'top: 1rem; right: 1rem; z-index: 9999; min-width: 300px;';
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(toast);

                // Auto dismiss after 3 seconds
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 150);
                }, 3000);
            }

            // Escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
@endpush