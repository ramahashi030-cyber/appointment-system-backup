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
    @endphp

    <div class="admin-dashboard-content">
        <section class="admin-telemedicine-banner" aria-label="QMMC telemedicine consultation">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
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
            <div class="admin-dashboard-primary">
                <section class="admin-panel admin-table-panel">
                    <header class="admin-panel-header">
                        <div class="admin-panel-title">
                            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                            <h2>Pending Appointment Requests</h2>
                        </div>
                        <a class="admin-panel-link" href="{{ route('admin.appointments') }}">View all</a>
                    </header>
                    <div class="admin-table-body">
                        @forelse ($pendingRequests as $requestRow)
                            <a href="{{ route('admin.appointments', ['search' => $requestRow['patient_name'], 'status' => 'Pending']) }}" class="admin-table-row pending-row">
                                <div class="admin-table-cell admin-person-cell">
                                    <span class="admin-avatar">{{ $requestRow['initials'] }}</span>
                                    <span class="admin-person-copy">
                                        <strong>{{ $requestRow['patient_name'] }}</strong>
                                    </span>
                                </div>
                                <div class="admin-table-cell">{{ $requestRow['service'] }}</div>
                                <div class="admin-table-cell">{{ $requestRow['requested'] }}</div>
                                <div class="admin-table-cell">
                                    <span class="admin-status-pill">{{ $requestRow['status'] }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="admin-empty-row">No pending appointment requests.</div>
                        @endforelse
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

