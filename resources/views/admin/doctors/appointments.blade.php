@extends('layouts.admin')

@section('title', $pageTitle)

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content admin-doctor-content">
        <div class="admin-page-heading">
            <div>
                <span class="admin-eyebrow">{{ $doctor->full_name ?: 'Provider' }}</span>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $history ? 'Review completed consultations and outcomes.' : 'Review upcoming and past appointments assigned to this provider.' }}</p>
            </div>
            <a class="admin-secondary-button" href="{{ route('admin.doctors.show', $doctor) }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to profile
            </a>
        </div>

        <section class="admin-panel">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-calendar2-week" aria-hidden="true"></i>
                    <h2>{{ $appointments->total() }} {{ $appointments->total() === 1 ? 'record' : 'records' }}</h2>
                </div>
                <a class="admin-panel-link" href="{{ route('admin.doctors') }}">All providers</a>
            </header>
            <div class="admin-doctor-table-wrap">
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">{{ $pageTitle }} for {{ $doctor->full_name }}</caption>
                    <thead>
                        <tr><th scope="col">Patient</th><th scope="col">Service</th><th scope="col">Date &amp; time</th><th scope="col">Mode</th><th scope="col">Status</th></tr>
                    </thead>
                    <tbody>@include('admin.doctors._appointment-table', ['appointments' => $appointments])</tbody>
                </table>
            </div>
            @if ($appointments->hasPages())
                <div class="admin-doctor-pagination">{{ $appointments->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>
    </div>
@endsection
