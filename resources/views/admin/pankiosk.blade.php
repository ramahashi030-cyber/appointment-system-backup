@extends('layouts.admin')

@section('title', 'Kiosk')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content admin-doctor-content admin-kiosk-page">

        <section
            class="admin-telemedicine-banner admin-doctor-banner"
            aria-labelledby="kioskTitle"
        >
            <div
                class="admin-telemedicine-glow"
                aria-hidden="true"
            ></div>

            <div class="admin-telemedicine-content">

                <div
                    class="admin-telemedicine-mark"
                    aria-hidden="true"
                >
                    <i class="bi bi-qr-code-scan"></i>
                </div>

                <div class="admin-telemedicine-copy">

                    <h1 id="kioskTitle">
                        Kiosk
                    </h1>

                    <p class="admin-telemedicine-welcome">
                        Kiosk Management
                    </p>

                    <p class="admin-telemedicine-description">
                        Verify patient QR codes at the QMMC-OPD kiosk.
                    </p>

                </div>
            </div>
        </section>

        <section
            class="admin-panel"
            aria-labelledby="kioskModuleTitle"
        >
            <header class="admin-panel-header">

                <div class="admin-panel-title">

                    <i
                        class="bi bi-qr-code-scan"
                        aria-hidden="true"
                    ></i>

                    <h2 id="kioskModuleTitle">
                        Kiosk Module
                    </h2>

                </div>

            </header>

            <div class="admin-doctor-form">
                <p>
                    Kiosk module placeholder.
                </p>
            </div>
        </section>

    </div>
@endsection