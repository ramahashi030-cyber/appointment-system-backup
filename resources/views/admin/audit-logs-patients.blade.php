@extends('layouts.admin')

@section('title', 'Patient Login Logs')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content admin-doctor-content">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="patientLogTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-person-lock"></i>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="patientLogTitle">Audit Logs</h1>
                    <p class="admin-telemedicine-welcome">Patient Login Monitor</p>
                    <p class="admin-telemedicine-description">See when patients logged in and out of their accounts.</p>
                </div>
            </div>
        </section>

        @include('partials.audit-log-tabs', ['active' => 'patients'])

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="patientLogRosterTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-person-lock" aria-hidden="true"></i>
                    <h2 id="patientLogRosterTitle">Patient logins</h2>
                </div>
                <span class="admin-muted-text" data-audit-result-count>{{ $logs->total() }} login{{ $logs->total() === 1 ? '' : 's' }}</span>
            </header>

            <form class="admin-doctor-filters" method="GET" action="{{ route('admin.audit-logs') }}" data-audit-filters>
                <input type="hidden" name="type" value="patients">
                <div class="admin-doctor-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="patientLogSearch">Search patient logins</label>
                    <input id="patientLogSearch" name="search" value="{{ $filters['search'] }}" placeholder="Search patient ID, name, or username...">
                </div>
                <input type="date" class="form-control" name="date" value="{{ $filters['date'] }}" aria-label="Filter by login date" style="width: auto;">
                <a class="admin-clear-filter" href="{{ route('admin.audit-logs', ['type' => 'patients']) }}">Clear</a>


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
                    <caption class="visually-hidden">Patient login logs</caption>
                    <thead>
                        <tr>
                            <th scope="col">Login date &amp; time</th>
                            <th scope="col">Logout date &amp; time</th>
                            <th scope="col">Patient ID</th>
                            <th scope="col">Patient name</th>
                            <th scope="col">Username</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $log->created_at?->format('M d, Y') ?? 'N/A' }}</span>
                                    <small class="admin-doctor-secondary-text">{{ $log->created_at?->format('h:i:s A') ?? '' }}</small>
                                </td>
                                <td>
                                    @if ($log->logout_at)
                                        <span class="admin-doctor-primary-text">{{ $log->logout_at->format('M d, Y') }}</span>
                                        <small class="admin-doctor-secondary-text">{{ $log->logout_at->format('h:i:s A') }}</small>
                                    @else
                                        <span class="admin-muted-text">&mdash;</span>
                                    @endif
                                </td>
                                <td>{{ $log->patient_id_display ?? 'N/A' }}</td>
                                <td><span class="admin-doctor-primary-text">{{ ($log->patient_name_display ?? '') !== '' ? $log->patient_name_display : 'N/A' }}</span></td>
                                <td>{{ $log->username }}</td>
                                <td>
                                    @if ($log->session_state === 'active')
                                        <span class="admin-status-pill active">Logged in</span>
                                    @elseif ($log->session_state === 'logged_out')
                                        <span class="admin-status-pill">Logged out</span>
                                    @else
                                        <span class="admin-status-pill pending" title="A newer login exists and no logout was recorded for this session">Session ended</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-journal-x" aria-hidden="true"></i>
                                        <strong>No patient logins found</strong>
                                        <span>Adjust the filters. Only logins made after this feature was deployed are recorded.</span>
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
            const $clear = $form.find('.admin-clear-filter');
            let timer = null;
            let request = null;

            const hasFilters = () => $form.find('input[name="search"]').val().trim() !== ''
                || $form.find('input[name="date"]').val() !== '';
            const syncClear = () => $clear.toggle(hasFilters());
            syncClear();

            const load = (url = null) => {
                const target = url ?? `${$form.attr('action')}?${$form.serialize()}`;

                request?.abort();
                $wrap.css('opacity', 0.6);
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
                        $wrap.css('opacity', '');

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

            $form.on('input', 'input[name="search"]', function () {
                syncClear();
                window.clearTimeout(timer);
                timer = window.setTimeout(() => load(), 300);
            });

            $form.on('change', 'input[name="date"]', function () {
                syncClear();
                load();
            });

            // stopPropagation keeps the layout's page-swap handler from also handling these links.
            $form.on('click', 'a.admin-clear-filter', function (event) {
                event.preventDefault();
                event.stopPropagation();
                $form.find('input[name="search"]').val('');
                $form.find('input[name="date"]').val('');
                syncClear();
                load();
            });

            $pagination.on('click', 'a', function (event) {
                event.preventDefault();
                event.stopPropagation();
                load(this.href);
            });
        });
    </script>
@endpush