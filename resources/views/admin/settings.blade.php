@extends('layouts.admin')

@section('title', 'System Settings')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
<div class="admin-dashboard-content admin-doctor-content admin-settings-page">
    <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="settingsTitle">
        <div class="admin-telemedicine-glow" aria-hidden="true"></div>
        <div class="admin-telemedicine-content">
            <div class="admin-telemedicine-mark" aria-hidden="true">
                <i class="bi bi-gear-fill"></i>
                <span><i class="bi bi-sliders"></i></span>
            </div>
            <div class="admin-telemedicine-copy">
                <h1 id="settingsTitle">System Settings</h1>
                <p class="admin-telemedicine-welcome">Hospital Configuration</p>
                <p class="admin-telemedicine-description">Manage hospital information, appointment rules, notifications, and system behavior.</p>
                <div class="admin-telemedicine-trust" aria-label="Settings features">
                    <span><i class="bi bi-hospital" aria-hidden="true"></i> Hospital Info</span>
                    <b aria-hidden="true">•</b>
                    <span>Appointments</span>
                    <b aria-hidden="true">•</b>
                    <span>Notifications</span>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="settingsCategoriesTitle">
        <header class="admin-panel-header admin-doctor-roster-header">
            <div class="admin-panel-title">
                <i class="bi bi-sliders" aria-hidden="true"></i>
                <h2 id="settingsCategoriesTitle">Settings categories</h2>
            </div>
        </header>
        <div class="admin-doctor-filters" style="flex-wrap: wrap;">
            <button class="admin-secondary-button active" type="button" data-settings-tab="hospital"><i class="bi bi-hospital" aria-hidden="true"></i> Hospital Info</button>
            <button class="admin-secondary-button" type="button" data-settings-tab="system"><i class="bi bi-laptop" aria-hidden="true"></i> System</button>
            <button class="admin-secondary-button" type="button" data-settings-tab="appointment"><i class="bi bi-calendar-check" aria-hidden="true"></i> Appointments</button>
            <button class="admin-secondary-button" type="button" data-settings-tab="notification"><i class="bi bi-bell" aria-hidden="true"></i> Notifications</button>
            <button class="admin-secondary-button" type="button" data-settings-tab="consultation"><i class="bi bi-clipboard-pulse" aria-hidden="true"></i> Consultation</button>
            <button class="admin-secondary-button" type="button" data-settings-tab="maintenance"><i class="bi bi-tools" aria-hidden="true"></i> Maintenance</button>
        </div>
    </section>

    <div id="settings-notification" class="alert d-none" role="status" aria-live="polite"></div>

    <section class="admin-panel admin-doctor-roster-panel" data-settings-panel="hospital" aria-labelledby="hospitalTitle">
        <header class="admin-panel-header admin-doctor-roster-header">
            <div class="admin-panel-title">
                <i class="bi bi-hospital" aria-hidden="true"></i>
                <h2 id="hospitalTitle">Hospital Information</h2>
            </div>
        </header>
        <form class="admin-doctor-form" data-settings-form="hospital" method="POST" action="{{ route('admin.settings.store') }}">
            @csrf
            <input type="hidden" name="section" value="hospital">
            <div class="admin-doctor-form-grid">
                <div class="form-field">
                    <label for="hospital_name">Hospital name</label>
                    <input class="form-control" id="hospital_name" name="hospital_name" type="text" value="{{ old('hospital_name', $settings['hospital_name']) }}" required maxlength="191">
                </div>
                <div class="form-field">
                    <label for="hospital_phone">Phone</label>
                    <input class="form-control" id="hospital_phone" name="hospital_phone" type="tel" value="{{ old('hospital_phone', $settings['hospital_phone']) }}" maxlength="20">
                </div>
                <div class="form-field">
                    <label for="hospital_email">Email</label>
                    <input class="form-control" id="hospital_email" name="hospital_email" type="email" value="{{ old('hospital_email', $settings['hospital_email']) }}" maxlength="191">
                </div>
                <div class="form-field field-wide">
                    <label for="hospital_address">Address</label>
                    <textarea class="form-control" id="hospital_address" name="hospital_address" rows="3" maxlength="500">{{ old('hospital_address', $settings['hospital_address']) }}</textarea>
                </div>
            </div>
            <div class="admin-doctor-form-actions">
                <button class="admin-primary-button" type="submit"><i class="bi bi-check2"></i> Save hospital info</button>
            </div>
        </form>
    </section>

    <section class="admin-panel admin-doctor-roster-panel d-none" data-settings-panel="system" aria-labelledby="systemTitle">
        <header class="admin-panel-header admin-doctor-roster-header">
            <div class="admin-panel-title">
                <i class="bi bi-laptop" aria-hidden="true"></i>
                <h2 id="systemTitle">System Settings</h2>
            </div>
        </header>
        <form class="admin-doctor-form" data-settings-form="system" method="POST" action="{{ route('admin.settings.store') }}">
            @csrf
            <input type="hidden" name="section" value="system">
            <div class="admin-doctor-form-grid">
                <div class="form-field">
                    <label for="system_name">System name</label>
                    <input class="form-control" id="system_name" name="system_name" type="text" value="{{ old('system_name', $settings['system_name']) }}" required maxlength="191">
                </div>
            </div>
            <div class="admin-doctor-form-actions">
                <button class="admin-primary-button" type="submit"><i class="bi bi-check2"></i> Save system settings</button>
            </div>
        </form>
    </section>

    <section class="admin-panel admin-doctor-roster-panel d-none" data-settings-panel="appointment" aria-labelledby="appointmentTitle">
        <header class="admin-panel-header admin-doctor-roster-header">
            <div class="admin-panel-title">
                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                <h2 id="appointmentTitle">Appointment Rules</h2>
            </div>
        </header>
        <form class="admin-doctor-form" data-settings-form="appointment" method="POST" action="{{ route('admin.settings.store') }}">
            @csrf
            <input type="hidden" name="section" value="appointment">
            <div class="admin-doctor-form-grid">
                <div class="form-field">
                    <label for="appointment_duration">Appointment duration (minutes)</label>
                    <input class="form-control" id="appointment_duration" name="appointment_duration" type="number" value="{{ old('appointment_duration', $settings['appointment_duration']) }}" required min="5" max="240" step="5">
                </div>
                <div class="form-field">
                    <label for="cancellation_window_hours">Cancellation window (hours before appointment)</label>
                    <input class="form-control" id="cancellation_window_hours" name="cancellation_window_hours" type="number" value="{{ old('cancellation_window_hours', $settings['cancellation_window_hours']) }}" required min="0" max="168">
                </div>
                <div class="form-field">
                    <label for="max_advance_booking_days">Max advance booking (days)</label>
                    <input class="form-control" id="max_advance_booking_days" name="max_advance_booking_days" type="number" value="{{ old('max_advance_booking_days', $settings['max_advance_booking_days']) }}" required min="1" max="365">
                </div>
            </div>
            <div class="admin-doctor-form-actions">
                <button class="admin-primary-button" type="submit"><i class="bi bi-check2"></i> Save appointment rules</button>
            </div>
        </form>
    </section>

    <section class="admin-panel admin-doctor-roster-panel d-none" data-settings-panel="notification" aria-labelledby="notificationTitle">
        <header class="admin-panel-header admin-doctor-roster-header">
            <div class="admin-panel-title">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <h2 id="notificationTitle">Notification Settings</h2>
            </div>
        </header>
        <form class="admin-doctor-form" data-settings-form="notification" method="POST" action="{{ route('admin.settings.store') }}">
            @csrf
            <input type="hidden" name="section" value="notification">
            <div class="admin-doctor-form-grid">
                <div class="form-field admin-settings-toggle-field">
                    <div class="form-check form-switch">
                        <input type="hidden" name="notification_email_enabled" value="0">
                        <input class="form-check-input" id="notification_email_enabled" name="notification_email_enabled" type="checkbox" value="1" @checked(old('notification_email_enabled', $settings['notification_email_enabled']))>
                        <label class="form-check-label" for="notification_email_enabled">Email notifications</label>
                    </div>
                </div>
                <div class="form-field admin-settings-toggle-field">
                    <div class="form-check form-switch">
                        <input type="hidden" name="notification_sms_enabled" value="0">
                        <input class="form-check-input" id="notification_sms_enabled" name="notification_sms_enabled" type="checkbox" value="1" @checked(old('notification_sms_enabled', $settings['notification_sms_enabled']))>
                        <label class="form-check-label" for="notification_sms_enabled">SMS notifications</label>
                    </div>
                </div>
                <div class="form-field admin-settings-toggle-field">
                    <div class="form-check form-switch">
                        <input type="hidden" name="notification_appointment_reminder" value="0">
                        <input class="form-check-input" id="notification_appointment_reminder" name="notification_appointment_reminder" type="checkbox" value="1" @checked(old('notification_appointment_reminder', $settings['notification_appointment_reminder']))>
                        <label class="form-check-label" for="notification_appointment_reminder">Appointment reminders</label>
                    </div>
                </div>
                <div class="form-field">
                    <label for="notification_reminder_hours">Reminder lead time (hours before appointment)</label>
                    <input class="form-control" id="notification_reminder_hours" name="notification_reminder_hours" type="number" value="{{ old('notification_reminder_hours', $settings['notification_reminder_hours']) }}" required min="1" max="72">
                </div>
            </div>
            <div class="admin-doctor-form-actions">
                <button class="admin-primary-button" type="submit"><i class="bi bi-check2"></i> Save notification settings</button>
            </div>
        </form>
    </section>

    <section class="admin-panel admin-doctor-roster-panel d-none" data-settings-panel="consultation" aria-labelledby="consultationTitle">
        <header class="admin-panel-header admin-doctor-roster-header">
            <div class="admin-panel-title">
                <i class="bi bi-clipboard-pulse" aria-hidden="true"></i>
                <h2 id="consultationTitle">Consultation Settings</h2>
            </div>
        </header>
        <form class="admin-doctor-form" data-settings-form="consultation" method="POST" action="{{ route('admin.settings.store') }}">
            @csrf
            <input type="hidden" name="section" value="consultation">
            <div class="admin-doctor-form-grid">
                <div class="form-field admin-settings-toggle-field">
                    <div class="form-check form-switch">
                        <input type="hidden" name="consultation_requires_approval" value="0">
                        <input class="form-check-input" id="consultation_requires_approval" name="consultation_requires_approval" type="checkbox" value="1" @checked(old('consultation_requires_approval', $settings['consultation_requires_approval']))>
                        <label class="form-check-label" for="consultation_requires_approval">Require approval before scheduling</label>
                    </div>
                </div>
                <div class="form-field admin-settings-toggle-field">
                    <div class="form-check form-switch">
                        <input type="hidden" name="consultation_auto_assign" value="0">
                        <input class="form-check-input" id="consultation_auto_assign" name="consultation_auto_assign" type="checkbox" value="1" @checked(old('consultation_auto_assign', $settings['consultation_auto_assign']))>
                        <label class="form-check-label" for="consultation_auto_assign">Auto-assign to available provider</label>
                    </div>
                </div>
            </div>
            <div class="admin-doctor-form-actions">
                <button class="admin-primary-button" type="submit"><i class="bi bi-check2"></i> Save consultation settings</button>
            </div>
        </form>
    </section>

    <section class="admin-panel admin-doctor-roster-panel d-none" data-settings-panel="maintenance" aria-labelledby="maintenanceTitle">
        <header class="admin-panel-header admin-doctor-roster-header">
            <div class="admin-panel-title">
                <i class="bi bi-tools" aria-hidden="true"></i>
                <h2 id="maintenanceTitle">System Maintenance</h2>
            </div>
        </header>
        <form class="admin-doctor-form" data-settings-form="maintenance" method="POST" action="{{ route('admin.settings.store') }}">
            @csrf
            <input type="hidden" name="section" value="maintenance">
            <div class="admin-doctor-form-grid">
                <div class="form-field admin-settings-toggle-field">
                    <div class="form-check form-switch">
                        <input type="hidden" name="maintenance_mode" value="0">
                        <input class="form-check-input" id="maintenance_mode" name="maintenance_mode" type="checkbox" value="1" @checked(old('maintenance_mode', $settings['maintenance_mode']))>
                        <label class="form-check-label" for="maintenance_mode">Maintenance mode</label>
                    </div>
                    <small class="form-text">When enabled, only administrators can access the system.</small>
                </div>
                <div class="form-field">
                    <label for="maintenance_message">Maintenance message</label>
                    <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3" maxlength="500">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
                </div>
            </div>
            <div class="admin-doctor-form-actions">
                <button class="admin-primary-button" type="submit"><i class="bi bi-check2"></i> Save maintenance settings</button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function () {
    var $navItems = $('[data-settings-tab]');
    var $panels = $('[data-settings-panel]');
    var $notification = $('#settings-notification');
    $navItems.on('click', function () {
        var target = $(this).data('settings-tab');
        $navItems.removeClass('active');
        $(this).addClass('active');
        $panels.addClass('d-none');
        $('[data-settings-panel="' + target + '"]').removeClass('d-none');
        $notification.addClass('d-none').removeClass('alert-success alert-danger').empty();
    });
    $('[data-settings-form]').on('submit', function (event) {
        event.preventDefault();
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        if ($submitBtn.prop('disabled')) { return; }
        var originalBtnContent = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');
        $notification.addClass('d-none').removeClass('alert-success alert-danger').empty();
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                $notification.removeClass('d-none').addClass('alert-success').text(response.message || 'Settings saved successfully.');
                $submitBtn.prop('disabled', false).html(originalBtnContent);
            },
            error: function (xhr) {
                var message = 'Failed to save settings. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) { message = xhr.responseJSON.message; }
                else if (xhr.responseJSON && xhr.responseJSON.errors) { message = Object.values(xhr.responseJSON.errors).flat().join(' '); }
                $notification.removeClass('d-none').addClass('alert-danger').text(message);
                $submitBtn.prop('disabled', false).html(originalBtnContent);
            }
        });
    });
});
</script>
@endpush