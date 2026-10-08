@extends('layouts.admin')

@section('title', 'Kiosk History')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    @php
        // Kiosk timestamps are stored in UTC; render them in the app display timezone (Asia/Manila).
        $kioskTimezone = config('app.display_timezone');
    @endphp

    <div class="admin-dashboard-content admin-doctor-content">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="kioskHistoryTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-qr-code-text"></i>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="kioskHistoryTitle">Kiosk History</h1>
                    <p class="admin-telemedicine-welcome">QR Check-in Audit Trail</p>
                    <p class="admin-telemedicine-description">Every scan at the OPD kiosk, including refused and failed attempts.</p>
                </div>
            </div>
        </section>

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="kioskHistoryListTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-qr-code-text" aria-hidden="true"></i>
                    <h2 id="kioskHistoryListTitle">Scan log</h2>
                </div>
                <span class="admin-muted-text">{{ $logs->total() }} entr{{ $logs->total() === 1 ? 'y' : 'ies' }}</span>
            </header>

            <form class="admin-doctor-filters" method="GET" action="{{ route('admin.kiosk-history') }}">
                <div class="admin-doctor-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="kioskHistorySearch">Search kiosk history</label>
                    <input id="kioskHistorySearch" name="search" value="{{ $filters['search'] }}" placeholder="Search patient name, hospital number, or kiosk...">
                </div>
                <select class="form-select" name="result" aria-label="Filter by result">
                    <option value="">All results</option>
                    @foreach ($results as $resultValue => $resultLabel)
                        <option value="{{ $resultValue }}" @selected($filters['result'] === $resultValue)>{{ $resultLabel }}</option>
                    @endforeach
                </select>
                <input type="date" class="form-control" name="date" value="{{ $filters['date'] }}" aria-label="Filter by date" style="width: auto;">
                @if ($filters['search'] !== '' || $filters['result'] !== '' || $filters['date'] !== '')
                    <a class="admin-clear-filter" href="{{ route('admin.kiosk-history') }}">Clear</a>
                @endif

                <div class="admin-doctor-pagination" style="margin-left: auto; border-top: 0; padding-top: 0; margin-top: 0;" @if (! $logs->hasPages()) hidden @endif>
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

            <div class="admin-doctor-table-wrap" style="overflow: visible; max-height: none;">
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">Kiosk scans</caption>
                    <thead>
                        <tr>
                            <th scope="col">Scanned at</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Hospital no.</th>
                            <th scope="col">Kiosk</th>
                            <th scope="col">Result</th>
                            <th scope="col">Encounter code</th>
                            <th scope="col">Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $log->scanned_at?->copy()->timezone($kioskTimezone)->format('M d, Y') ?? 'N/A' }}</span>
                                    <small class="admin-doctor-secondary-text">{{ $log->scanned_at?->copy()->timezone($kioskTimezone)->format('h:i:s A') ?? '' }}</small>
                                </td>
                                <td><span class="admin-doctor-primary-text">{{ $log->patient_name ?? 'N/A' }}</span></td>
                                <td>{{ $log->hospital_number ?? 'N/A' }}</td>
                                <td>{{ $log->kiosk_name ?? 'N/A' }}</td>
                                <td><span class="admin-status-pill">{{ ucfirst($log->result) }}</span></td>
                                <td>{{ $log->homis_encounter_code ?? 'N/A' }}</td>
                                <td>{{ $log->message ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-qr-code" aria-hidden="true"></i>
                                        <strong>No kiosk scans found</strong>
                                        <span>Adjust the filters, or scan a QR code at the kiosk to create an entry.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="admin-doctor-pagination" @if (! $logs->hasPages()) hidden @endif>
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
