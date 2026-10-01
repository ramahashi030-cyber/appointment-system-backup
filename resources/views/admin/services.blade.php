@extends('layouts.admin')

@section('title', $config['title'])

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@php
    /*
     * Real rows for the edit modal, keyed by service id. Rendered from the
     * loaded models so the modal never shows placeholder values.
     */
    $servicePayload = $services->mapWithKeys(fn ($service): array => [
        $service->id => [
            'id' => $service->id,
            'service_name' => (string) $service->service_name,
            'homis_code' => (string) ($service->homis_code ?? ''),
            'availability_day' => $service->availableDays(),
            'timeslots' => $service->timeslots
                ->sortBy(fn ($slot): string => (string) $slot->time_slot)
                ->map(fn ($slot): array => [
                    'time_slot' => (string) $slot->time_slot,
                    'slots' => (int) $slot->slots,
                ])
                ->values()
                ->all(),
        ],
    ])->all();

    $totalSlots = $services->sum(fn ($service): int => $service->timeslots->sum('slots'));
    $totalTimeslots = $services->sum(fn ($service): int => $service->timeslots->count());

    /*
     * Which modal a rejected submission came back to. Both forms submit a hidden
     * `_form` marker precisely so this does not have to be guessed.
     */
    $failedForm = old('_form');

    /*
     * When an edit is rejected the admin's own input has to come back, not the
     * stored row, or their changes silently vanish. Seeded once, so subsequent
     * opens go back to reading the live row.
     */
    $editSeed = null;

    if ($failedForm === 'edit' && old('_service_id')) {
        $editSeed = [
            'id' => (int) old('_service_id'),
            'service_name' => (string) old('service_name', ''),
            'homis_code' => (string) old('homis_code', ''),
            'availability_day' => array_values((array) old('availability_day', [])),
            'timeslots' => collect((array) old('timeslots', []))
                ->map(fn ($slot): array => [
                    'time_slot' => (string) ($slot['time_slot'] ?? ''),
                    'slots' => (string) ($slot['slots'] ?? ''),
                ])
                ->values()
                ->all(),
        ];
    }

    /*
     * One blank timeslot row: the add form never opens empty, and this is also
     * exactly what Cancel restores the add form to.
     */
    $blankSlots = [['time_slot' => '', 'slots' => '']];

    /* Route name prefixes — used by JS to build update/destroy URLs. */
    $editRouteName = $config['route'] . '.update';
    $destroyRouteName = $config['route'] . '.destroy';
@endphp

@section('content')
    {{-- 
        `admin-patients-page` is deliberately absent. That class narrows
        .admin-doctor-stat-grid to three columns, which pushed the Add Service
        button onto a row of its own. The base grid is already
        `repeat(3, 1fr) minmax(130px, auto)` — three stat cards plus the action
        button on a single line.
    --}}
    <div class="admin-dashboard-content admin-doctor-content admin-services-page"
         data-services-page
         data-service-type="{{ $config['mode'] }}">

        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="servicesTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi {{ $config['banner_icon'] }}"></i>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="servicesTitle">{{ $config['title'] }}</h1>
                    <p class="admin-telemedicine-welcome">Service Management</p>
                    <p class="admin-telemedicine-description">{{ $config['description'] }}</p>
                </div>
            </div>
        </section>

        <div class="admin-doctor-stat-grid">
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi {{ $config['icon'] }}" aria-hidden="true"></i></span>
                <span><small>Total services</small><strong>{{ content