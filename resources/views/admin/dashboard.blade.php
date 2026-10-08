@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    @php
        $admin = auth('admin')->user();
        $adminName = $admin !== null
            ? trim($admin->firstname.' '.$admin->lastname)
            : 'Administrator';
        // Audit timestamps are stored in UTC; render them in the app display timezone (Asia/Manila).
        $auditTimezone = config('app.display_timezone');
    @endphp

    {{-- Mobile-only: the "Administration benefits" trust row is hidden on phones;
         desktop and tablet stay outside this query and render it unchanged. --}}
    <style>
        @media (max-width: 767.98px) {
            .admin-telemedicine-trust[aria-label="Administration benefits"] {
                display: none !important;
            }
        }
    </style>

    <div class="admin-dashboard-content">
        <section class="admin-telemedicine-banner" aria-label="QMMC telemedicine consultation">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true" style="color: #f4f8ff; background: #0b6fe8;">
                    <i class="bi bi-heart-fill"></i>
                    <span><i class="bi bi-camera-video-fill"></i></span>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1>Administrative Dashboard</h1>
                    <p class="admin-telemedicine-welcome">Welcome, {{ $adminName }}!</p>
                    <p class="admin-telemedicine-description">Doctor / Healthcare Provider and Patient Management.</p>
                    <div class="admin-telemedicine-trust" aria-label="Administration benefits">
                        <span><i class="bi bi-shield-check" aria-hidden="true"></i> Secure</span>
                        <b aria-hidden="true">•</b>
                        <span>Efficient</span>
                        <b aria-hidden="true">•</b>
                        <span>Quality Management</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="admin-stat-grid">
            @foreach ($statCards as $card)
                <article class="admin-stat-card">
                    <span class="admin-stat-icon {{ $card['tone'] }}" aria-hidden="true">
                        <i class="bi {{ $card['icon'] }}"></i>
                    </span>
                    <span class="admin-stat-copy">
                        <strong>{{ number_format($card['value']) }}</strong>
                        <span>{{ $card['label'] }}</span>
                    </span>
                </article>
            @endforeach
        </div>

        <div class="admin-dashboard-grid">
            <div class="admin-dashboard-primary admin-staff-activity-section">
                <section class="admin-panel admin-table-panel">
                    <header class="admin-panel-header">
                        <div class="admin-panel-title">
                            <i class="bi bi-journal-text" aria-hidden="true"></i>
                            <h2>Staff Activity</h2>
                        </div>
                        <a class="admin-panel-link" href="{{ route('admin.audit-logs') }}">View all</a>
                    </header>
                    <div class="admin-doctor-table-wrap" style="overflow: visible; max-height: none;">
                        <table class="admin-doctor-table">
                            <caption class="visually-hidden">Staff activity audit logs</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Date & time</th>
                                    <th scope="col">Username</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Module</th>
                                    <th scope="col">Record ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($staffActivity as $log)
                                    <tr>
                                        <td>
                                            <span class="admin-doctor-primary-text">{{ $log['created_at']?->copy()->timezone($auditTimezone)->format('M d, Y') ?? 'N/A' }}</span>
                                            <small class="admin-doctor-secondary-text">{{ $log['created_at']?->copy()->timezone($auditTimezone)->format('h:i:s A') ?? '' }}</small>
                                        </td>
                                        <td><span class="admin-doctor-primary-text">{{ $log['username'] }}</span></td>
                                        <td>{{ $log['user_role'] }}</td>
                                        <td><span class="admin-status-pill">{{ $log['action'] }}</span></td>
                                        <td>{{ $log['module'] }}</td>
                                        <td>{{ $log['record_id'] ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="admin-doctor-empty">
                                                <i class="bi bi-journal-x" aria-hidden="true"></i>
                                                <strong>No staff activity recorded</strong>
                                                <span>Activity will appear here as staff perform actions in the admin panel.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="admin-dashboard-secondary">
                <section class="admin-panel admin-quick-panel">
                    <header class="admin-panel-header">
                        <div class="admin-panel-title">
                            <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i>
                            <h2>Quick Access</h2>
                        </div>
                    </header>
                    <div class="admin-quick-list">
                        @foreach ($quickLinks as $link)
                            <a class="admin-quick-link" href="{{ route($link['route']) }}">
                                <span class="admin-quick-icon {{ $link['tone'] }}" aria-hidden="true">
                                    <i class="bi {{ $link['icon'] }}"></i>
                                </span>
                                <span class="admin-quick-copy">
                                    <strong>{{ $link['label'] }}</strong>
                                    <small>{{ $link['description'] }}</small>
                                </span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection