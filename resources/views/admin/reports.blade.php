@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content admin-doctor-content admin-reports-page">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="reportsTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-bar-chart-fill"></i>
                    <span><i class="bi bi-graph-up-arrow"></i></span>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="reportsTitle">Reports &amp; Analytics</h1>
                    <p class="admin-telemedicine-welcome">Hospital Performance Dashboard</p>
                    <p class="admin-telemedicine-description">Generate and export appointment, patient, and service usage reports.</p>
                    <div class="admin-telemedicine-trust" aria-label="Report features">
                        <span><i class="bi bi-graph-up" aria-hidden="true"></i> Analytics</span>
                        <b aria-hidden="true">•</b>
                        <span>Export</span>
                        <b aria-hidden="true">•</b>
                        <span>Filterable</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="admin-doctor-stat-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi bi-calendar2-check-fill" aria-hidden="true"></i></span>
                <span><small>Total appointments</small><strong id="statTotal">{{ number_format($stats['total']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon green"><i class="bi bi-check-circle-fill" aria-hidden="true"></i></span>
                <span><small>Approved</small><strong id="statApproved">{{ number_format($stats['approved']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon orange"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <span><small>Pending</small><strong id="statPending">{{ number_format($stats['pending']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon purple"><i class="bi bi-x-circle-fill" aria-hidden="true"></i></span>
                <span><small>Cancelled</small><strong id="statCancelled">{{ number_format($stats['cancelled']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon cyan"><i class="bi bi-camera-video-fill" aria-hidden="true"></i></span>
                <span><small>Telemedicine</small><strong id="statTelemedicine">{{ number_format($stats['telemedicine']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi bi-hospital" aria-hidden="true"></i></span>
                <span><small>Face-to-Face</small><strong id="statFaceToFace">{{ number_format($stats['face_to_face']) }}</strong></span>
            </article>
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon green"><i class="bi bi-heart-pulse-fill" aria-hidden="true"></i></span>
                <span><small>Family Medicine</small><strong id="statFamilyMedicine">{{ number_format($stats['family_medicine']) }}</strong></span>
            </article>
            <div style="display: flex; gap: 10px;">
                <button class="admin-doctor-add-button admin-doctor-stat-action" type="button" id="exportCsvBtn" style="flex: 1;">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Export CSV</span>
                </button>
            </div>
        </div>

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="reportFiltersTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                    <h2 id="reportFiltersTitle">Report filters</h2>
                </div>
            </header>

            <form class="admin-doctor-filters" method="GET" action="{{ route('admin.reports') }}" data-report-filters style="flex-wrap: wrap;">
                <div class="admin-doctor-search" style="flex: 2 1 200px;">
                    <i class="bi bi-calendar2-range" aria-hidden="true"></i>
                    <label class="visually-hidden" for="filterDateFrom">Date from</label>
                    <input type="date" id="filterDateFrom" name="date_from" value="{{ $filters['date_from'] }}" placeholder="From">
                </div>
                <div class="admin-doctor-search" style="flex: 2 1 200px;">
                    <i class="bi bi-calendar2-range" aria-hidden="true"></i>
                    <label class="visually-hidden" for="filterDateTo">Date to</label>
                    <input type="date" id="filterDateTo" name="date_to" value="{{ $filters['date_to'] }}" placeholder="To">
                </div>
                <select class="form-select" name="doctor" aria-label="Filter by doctor" style="flex: 1 1 160px;">
                    <option value="">All doctors</option>
                    @foreach ($doctors as $doc)
                        <option value="{{ $doc->id }}" @selected($filters['doctor'] == $doc->id)>{{ $doc->full_name }}</option>
                    @endforeach
                </select>
                <select class="form-select" name="type" aria-label="Filter by type" style="flex: 1 1 140px;">
                    <option value="">All types</option>
                    <option value="face" @selected($filters['type'] === 'face')>Face-to-Face</option>
                    <option value="telemedicine" @selected($filters['type'] === 'telemedicine')>Telemedicine</option>
                </select>
                <select class="form-select" name="status" aria-label="Filter by status" style="flex: 1 1 140px;">
                    <option value="">All statuses</option>
                    <option value="Booked" @selected($filters['status'] === 'Booked')>Booked</option>
                    <option value="Approved" @selected($filters['status'] === 'Approved')>Approved</option>
                    <option value="Cancelled" @selected($filters['status'] === 'Cancelled')>Cancelled</option>
                </select>
                <a class="admin-clear-filter" href="{{ route('admin.reports') }}">Clear</a>
            </form>
        </section>

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="doctorActivityTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                    <h2 id="doctorActivityTitle">Doctor activity report</h2>
                </div>
            </header>
            <div class="admin-doctor-table-wrap" data-report-doctor-wrap>
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">Doctor activity</caption>
                    <thead>
                        <tr>
                            <th scope="col">Doctor</th>
                            <th scope="col">Total appointments</th>
                            <th scope="col">Approved</th>
                            <th scope="col">Cancelled</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($doctorActivity as $activity)
                            <tr>
                                <td>
                                    <div class="admin-doctor-person">
                                        <span class="admin-avatar">{{ strtoupper(substr((string) ($activity->doctor_name ?: 'D'), 0, 1)) }}</span>
                                        <span>
                                            <strong>{{ $activity->doctor_name }}</strong>
                                        </span>
                                    </div>
                                </td>
                                <td><span class="admin-doctor-primary-text">{{ number_format($activity->total) }}</span></td>
                                <td><span class="admin-doctor-primary-text">{{ number_format($activity->approved) }}</span></td>
                                <td><span class="admin-doctor-primary-text">{{ number_format($activity->cancelled) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-person-x" aria-hidden="true"></i>
                                        <strong>No doctor activity found</strong>
                                        <span>Adjust the filters to see doctor activity.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="recentAppointmentsTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    <h2 id="recentAppointmentsTitle">Recent appointments</h2>
                </div>
                <span class="admin-muted-text">{{ $recentAppointments->count() }} appointment{{ $recentAppointments->count() === 1 ? '' : 's' }}</span>
            </header>
            <div class="admin-doctor-table-wrap" data-report-table-wrap>
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">Recent appointments</caption>
                    <thead>
                        <tr>
                            <th scope="col">Patient</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Service</th>
                            <th scope="col">Schedule</th>
                            <th scope="col">Type</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAppointments as $appointment)
                            <tr>
                                <td>
                                    <div class="admin-doctor-person">
                                        <span class="admin-avatar">{{ strtoupper(substr((string) ($appointment->patient->first_name ?: 'P'), 0, 1).substr((string) ($appointment->patient->last_name ?: 'A'), 0, 1)) }}</span>
                                        <span>
                                            <strong>{{ $appointment->patient->full_name ?? 'Unknown patient' }}</strong>
                                            <small>{{ $appointment->patient->email ?? 'No email' }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $appointment->staff->full_name ?? 'Unassigned' }}</span>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $appointment->service->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $appointment->date?->format('M d, Y') ?? 'No date' }}</span>
                                    <small class="admin-doctor-secondary-text">{{ $appointment->time_slot ?? 'No time' }}</small>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $appointment->mode ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="admin-status-pill {{ strtolower(str_replace(' ', '-', $appointment->status)) }}">
                                        {{ $appointment->status === 'Completed' ? 'Approved' : $appointment->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                                        <strong>No appointments found</strong>
                                        <span>Adjust the filters to see appointments.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(function() {
            const form = document.querySelector('[data-report-filters]');
            if (!form) {
                return;
            }

            let debounceTimer;
            let requestController;

            const updateFilters = async (url = null) => {
                const query = new URLSearchParams(new FormData(form)).toString();
                const targetUrl = url ?? `${form.action}${query ? `?${query}` : ''}`;

                requestController?.abort();
                requestController = new AbortController();

                try {
                    const response = await fetch(targetUrl, {
                        headers: { Accept: 'text/html' },
                        credentials: 'same-origin',
                        signal: requestController.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load reports.');
                    }

                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const nextTable = page.querySelector('[data-report-table-wrap]');
                    const nextStats = {
                        total: page.querySelector('#statTotal'),
                        approved: page.querySelector('#statApproved'),
                        pending: page.querySelector('#statPending'),
                        cancelled: page.querySelector('#statCancelled'),
                        telemedicine: page.querySelector('#statTelemedicine'),
                        faceToFace: page.querySelector('#statFaceToFace'),
                        familyMedicine: page.querySelector('#statFamilyMedicine'),
                    };

                    const tableWrap = document.querySelector('[data-report-table-wrap]');
                    if (tableWrap && nextTable) {
                        tableWrap.innerHTML = nextTable.innerHTML;
                    }

                    const nextDoctorTable = page.querySelector('[data-report-doctor-wrap]');
                    const doctorWrap = document.querySelector('[data-report-doctor-wrap]');
                    if (doctorWrap && nextDoctorTable) {
                        doctorWrap.innerHTML = nextDoctorTable.innerHTML;
                    }

                    Object.entries(nextStats).forEach(([key, el]) => {
                        const target = document.getElementById(`stat${key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, '$1')}`);
                        if (target && el) {
                            target.textContent = el.textContent;
                        }
                    });

                    window.history.replaceState({}, '', targetUrl);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        form.submit();
                    }
                } finally {
                    requestController = null;
                }
            };

            const scheduleFilterUpdate = () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(() => updateFilters(), 300);
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                updateFilters();
            });

            form.querySelectorAll('input, select').forEach((control) => {
                control.addEventListener('input', scheduleFilterUpdate);
                control.addEventListener('change', () => updateFilters());
            });

            $('#exportCsvBtn').on('click', function () {
                const query = new URLSearchParams(new FormData(form)).toString();
                const exportUrl = '{{ route('admin.reports.export') }}' + (query ? `?${query}` : '');
                window.location.href = exportUrl;
            });
        });
    </script>
@endpush