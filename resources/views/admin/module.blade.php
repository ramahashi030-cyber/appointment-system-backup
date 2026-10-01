@extends('layouts.admin')

@section('title', $moduleTitle)

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content">
        <div class="admin-page-heading">
            <div>
                <h1>{{ $moduleTitle }}</h1>
                <p>{{ $moduleDescription }}</p>
            </div>
            <a class="admin-back-link" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to dashboard
            </a>
        </div>

        <section class="admin-module-card">
            <span class="admin-module-icon"><i class="bi {{ $moduleIcon }}" aria-hidden="true"></i></span>
            <h2>{{ $moduleTitle }} module</h2>
            <p>{{ $moduleDescription }}</p>
            <a class="admin-primary-button" href="{{ route('admin.dashboard') }}">Return to overview</a>
        </section>
    </div>
@endsection
