@extends('layouts.admin')

@section('title', $doctor ? 'Edit Doctor' : 'Add Doctor')

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
                <span class="admin-eyebrow">Directory</span>
                <h1>{{ $doctor ? 'Edit doctor' : 'Add doctor' }}</h1>
                <p>{{ $doctor ? 'Update provider information, access, and availability.' : 'Create a healthcare provider profile for the staff directory.' }}</p>
            </div>
            <a class="admin-secondary-button" href="{{ route('admin.doctors') }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to roster
            </a>
        </div>

        @include('admin.doctors._form', [
            'doctor' => $doctor,
            'formAction' => $doctor ? route('admin.doctors.update', $doctor) : route('admin.doctors.store'),
            'formMethod' => $doctor ? 'PUT' : 'POST',
            'submitLabel' => $doctor ? 'Save changes' : 'Add doctor',
            'cancelUrl' => route('admin.doctors'),
        ])
    </div>
@endsection
