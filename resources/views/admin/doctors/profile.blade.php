@extends('layouts.admin')

@section('title', $doctor->full_name ?: 'Doctor Profile')

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
                <span class="admin-eyebrow">Provider profile</span>
                <h1>{{ $doctor->full_name ?: 'Unnamed provider' }}</h1>
                <p>Review provider access, practice details, and consultation activity.</p>
            </div>
            <div class="admin-heading-actions">
                <a class="admin-secondary-button" href="{{ route('admin.doctors') }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Back to roster
                </a>
                <a class="admin-primary-button" href="{{ route('admin.doctors.edit', $doctor) }}">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                    Edit profile
                </a>
            </div>
        </div>

        @include('admin.doctors._profile')
    </div>
@endsection
