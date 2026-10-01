@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content admin-doctor-content">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="auditLogTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="auditLogTitle">Audit Logs</h1>
                    <p class="admin-telemedicine-welcome">System Audit Trail</p>
                    <p class="admin-telemedicine-description">Every action by admins, triagers and doctors across the admin panel.</p>
                </div>
            </div>
        </section>

        @include('partials.audit-log-tabs', ['active' => 'staff'])

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="auditLogRosterTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-journal-text" aria-hidden="true"></i>
                    <h2 id="auditLogRosterTitle">Activity log</h2>
                </div>
                <span class="admin-muted-text" data-audit-result-count>{{ $logs->total() }} entr{{ $logs->total() === 1 ? 'y' : 'ies' }}</span>
            </header>

            <form class="admin-doctor-filters" method="GET" action="{{ route('admin.audit-logs') }}" data-audit-filters>
                <div class="admin-doctor-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="auditSearch">Search audit logs</label>
                    <input id="auditSearch" name="search" value="{{ $filters['search'] }}" placeholder="Search username, user ID, or record ID...">
                </div>
                <select class="form-select" name="module" aria-label="Filter by module">
                    <option value="">All modules</option>
                    @foreach ($modules as $moduleName)
                        <option value="{{ $moduleName }}" @selected($filters['module'] === $moduleName)>{{ $moduleName }}</option>
                    @endforeach
                </select>
                <select class="form-select" name="role" aria-label="Filter by role">
                    <option value="">All roles</option>
                    @foreach ($roles as $roleName)
                        <option value="{{ $roleName }}" @selected($filters['role'] === $roleName)>{{ $roleName }}</option>
                    @endforeach
                </select>
                <select class="form-select" name="action" aria-label="Filter by action">
                    <option value="">All actions</option>
                    @foreach ($actions as $actionName)
                        <option value="{{ $actionName }}" @selected($filters['action'] === $actionName)>{{ $actionName }}</option>
                    @endforeach
                </select>
                <input type="date" class="form-control" name="date" value="{{ $filters['date'] }}" aria-label="Filter by date" style="width: auto;">
                @if ($filters['search'] !== '' || $filters['module'] !== '' || $filters['role'] !== '' || $filters['action'] !== '' || $filters['date'] !== '')
                    <a class="admin-clear-filter" href="{{ route('admin.audit-logs') }}">Clear</a>
                @endif

                <div class="admin-doctor-pagination" data-audit-pagination style="margin-left: auto; border-top: 0; padding-top: 0; margin-top: 0;" @if (! $logs->hasPages()) hidden @endif>
                    @if ($logs->hasPages())
                        @if ($logs->onFirstPage())
                            <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&laquo;</span>
                        @else
                            <a class="admin-doctor-page-button" href="{{ $logs->previousPageUrl() }}" aria-label="Previous page">&laquo;</a>
                        @endif
                        <span class="admin-doctor-page-current" aria-current="page">{{ $logs->currentPage() }}</span>
                        @if ($logs->hasMorePages())
                            <a class="admin-doctor-page-button" href="{{ $logs->nextPageUrl() }}" aria-label="Next page">&raquo;</a>
                        @else
                            <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&raquo;</span>
                        @endif
                    @endif
                </div>

            </form>

            <div class="admin-doctor-table-wrap" data-audit-table-wrap style="overflow: visible; max-height: none;">
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">Audit logs</caption>
                    <thead>
                        <tr>
                            <th scope="col">Date &amp; time</th>
                            <th scope="col">User ID</th>
                            <th scope="col">Username</th>
                            <th scope="col">Role</th>
                            <th scope="col">Action</th>
                            <th scope="col">Module</th>
                            <th scope="col">Record ID</th>
                            <th scope="col">Patient ID</th>
                            <th scope="col">Patient name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $log->created_at?->format('M d, Y') ?? 'N/A' }}</span>
                                    <small class="admin-doctor-secondary-text">{{ $log->created_at?->format('h:i:s A') ?? '' }}</small>
                                </td>
                                <td>{{ $log->user_id }}</td>
                                <td><span class="admin-doctor-primary-text">{{ $log->username }}</span></td>
                                <td>{{ $log->user_role }}</td>
                                <td><span class="admin-status-pill">{{ $log->action }}</span></td>
                                <td>{{ $log->module }}</td>
                                <td>{{ $log->record_id ?? 'N/A' }}</td>
                                <td>{{ $log->patient_id_display ?? 'N/A' }}</td>
                                <td>{{ ($log->patient_name_display ?? '') !== '' ? $log->patient_name_display : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-journal-x" aria-hidden="true"></i>
                                        <strong>No audit entries found</strong>
                                        <span>Adjust the filters, or perform an action in the admin panel to create an entry.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-doctor-pagination" data-audit-pagination @if (! $logs->hasPages()) hidden @endif>
                @if ($logs->hasPages())
                    @if ($logs->onFirstPage())
                        <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&laquo;</span>
                    @else
                        <a class="admin-doctor-page-button" href="{{ $logs->previousPageUrl() }}" aria-label="Previous page">&laquo;</a>
                    @endif
                    <span class="admin-doctor-page-current" aria-current="page">{{ $logs->currentPage() }}</span>
                    @if ($logs->hasMorePages())
                        <a class="admin-doctor-page-button" href="{{ $logs->nextPageUrl() }}" aria-label="Next page">&raquo;</a>
                    @else
                        <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&raquo;</span>
                    @endif
                @endif
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const $form = $('[data-audit-filters]');
            if (!$form.length) {
                return;
            }

            const $wrap = $('[data-audit-table-wrap]');
            const $count = $('[data-audit-result-count]');
            const $pagination = $('[data-audit-pagination]');
            let timer = null;
            let request = null;

            const load = (url = null) => {
                const query = $form.serialize();
                const target = url ?? `${$form.attr('action')}${query ? `?${query}` : ''}`;

                request?.abort();
                request = $.ajax({ url: target, method: 'GET', dataType: 'html' })
                    .done(function (html) {
                        const page = new DOMParser().parseFromString(html, 'text/html');
                        const nextTable = page.querySelector('[data-audit-table-wrap]');
                        const nextCount = page.querySelector('[data-audit-result-count]');
                        const nextPagination = page.querySelector('[data-audit-pagination]');

                        if (nextTable) {
                            $wrap.html(nextTable.innerHTML);
                        }
                        if (nextCount) {
                            $count.text(nextCount.textContent);
                        }
                        $pagination.html(nextPagination?.innerHTML ?? '');
                        $pagination.prop('hidden', nextPagination?.hidden ?? true);

                        window.history.replaceState({}, '', target);
                    })
                    .fail(function (xhr, status) {
                        if (status !== 'abort') {
                            window.location.href = target;
                        }
                    });
            };

            $form.on('submit', function (event) {
                event.preventDefault();
                load();
            });

            $form.find('input, select').on('input', function () {
                window.clearTimeout(timer);
                timer = window.setTimeout(() => load(), 300);
            });
            $form.find('select, input[type="date"]').on('change', () => load());

            // stopPropagation keeps the layout's page-swap handler from also loading the page.
            const onPaginationClick = function (event) {
                const link = event.target.closest('[data-audit-pagination] a');
                if (!link) {
                    return;
                }
                event.preventDefault();
                event.stopImmediatePropagation();
                load(link.href);
                };
                if (window.__auditPaginationClick) {
                    window.removeEventListener('click', window.__auditPaginationClick, true);
                }
                window.__auditPaginationClick = onPaginationClick;
                window.addEventListener('click', onPaginationClick, true);
            });
    </script>
@endpush