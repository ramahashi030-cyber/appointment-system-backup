<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\KioskController;
use App\Http\Controllers\Admin\KioskHistoryController;
use App\Http\Controllers\Admin\NewAdminController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RecordController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\TimeslotController;
use App\Http\Controllers\Admin\TriagerAccountController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
| Admin-only navigation lives in its own route file so the admin area can
| evolve independently from the patient portal.
*/

Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function (): void {
    // Authenticated admin/triager routes (both can access)
    Route::middleware('auth:admin')->group(function (): void {
        // Logout is available to all authenticated admin/triager users
        Route::post('/logout', [LoginController::class, 'adminLogout'])->name('logout');

        // Admin-only routes (require admin role)
        Route::middleware('admin.role')->group(function (): void {
            Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
            Route::get('/patients', [PatientController::class, 'index'])->name('patients');
            Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
            Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
            Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
            Route::patch('/patients/{patient}/status', [PatientController::class, 'toggleStatus'])->name('patients.status');
            Route::post('/patients/{patient}/reset-password', [PatientController::class, 'resetPassword'])->name('patients.reset-password');
            Route::get('/patients/{patient}/appointments', [PatientController::class, 'appointments'])->name('patients.appointments');
            Route::get('/patients/{patient}/history', [PatientController::class, 'history'])->name('patients.history');
            Route::get('/patients/{patient}/records', [PatientController::class, 'records'])->name('patients.records');
            Route::get('/patients/{patient}/visits', [PatientController::class, 'visits'])->name('patients.visits');
            Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
            Route::get('/doctors-staff', [DoctorController::class, 'index'])->name('doctors');
            Route::get('/doctors/create', [DoctorController::class, 'create'])->name('doctors.create');
            Route::post('/doctors', [DoctorController::class, 'store'])->name('doctors.store');
            Route::post('/doctors/{staff}/appointments/assign', [DoctorController::class, 'assignAppointment'])->name('doctors.appointments.assign');
            Route::get('/doctors/{staff}/appointments', [DoctorController::class, 'appointments'])->name('doctors.appointments');
            Route::get('/doctors/{staff}/history', [DoctorController::class, 'history'])->name('doctors.history');
            Route::get('/doctors/{staff}/edit', [DoctorController::class, 'edit'])->name('doctors.edit');
            Route::put('/doctors/{staff}', [DoctorController::class, 'update'])->name('doctors.update');
            Route::patch('/doctors/{staff}/status', [DoctorController::class, 'toggleStatus'])->name('doctors.status');
            Route::get('/doctors/{staff}', [DoctorController::class, 'show'])->name('doctors.show');
            Route::get('/triagers', [TriagerAccountController::class, 'index'])->name('triagers');
            Route::post('/triagers', [TriagerAccountController::class, 'store'])->name('triagers.store');
            Route::put('/triagers/{admin}', [TriagerAccountController::class, 'update'])->name('triagers.update');
            Route::delete('/triagers/{admin}', [TriagerAccountController::class, 'destroy'])->name('triagers.destroy');

            Route::get('/kiosk', [KioskController::class, 'show'])->name('kiosk');
            Route::post('/kiosk/verify', [KioskController::class, 'verify'])->name('kiosk.verify');
            Route::post('/kiosk/confirm', [KioskController::class, 'confirm'])->name('kiosk.confirm');
            Route::get('/kiosk-history', [KioskHistoryController::class, 'index'])->name('kiosk-history');

            Route::prefix('/services')->name('services.')->group(function (): void {
                $types = [
                    'face-to-face' => 'face',
                    'telemedicine' => 'tele',
                ];

                foreach ($types as $segment => $type) {
                    Route::get("/{$segment}", [ServiceController::class, 'index'])->defaults('type', $type)->name($segment);
                    Route::post("/{$segment}", [ServiceController::class, 'store'])->defaults('type', $type)->name("{$segment}.store");
                    Route::put("/{$segment}/{service}", [ServiceController::class, 'update'])->defaults('type', $type)->name("{$segment}.update");
                    Route::delete("/{$segment}/{service}", [ServiceController::class, 'destroy'])->defaults('type', $type)->name("{$segment}.destroy");
                }
            });

            Route::prefix('/holidays')->name('holidays.')->group(function (): void {
                $types = ['telemedicine', 'face-to-face'];

                foreach ($types as $segment) {
                    Route::get("/{$segment}", [HolidayController::class, 'index'])->defaults('type', $segment)->name($segment);
                    Route::get("/{$segment}/data", [HolidayController::class, 'data'])->defaults('type', $segment)->name("{$segment}.data");
                    Route::post("/{$segment}", [HolidayController::class, 'store'])->defaults('type', $segment)->name("{$segment}.store");
                    Route::delete("/{$segment}/{holiday}", [HolidayController::class, 'destroy'])->defaults('type', $segment)->name("{$segment}.destroy");
                }
            });

            Route::prefix('/timeslots')->name('timeslots.')->group(function (): void {
                $types = ['face-to-face', 'telemedicine'];

                foreach ($types as $segment) {
                    Route::get("/{$segment}", [TimeslotController::class, 'index'])->defaults('type', $segment)->name($segment);
                    Route::get("/{$segment}/data", [TimeslotController::class, 'data'])->defaults('type', $segment)->name("{$segment}.data");
                    Route::get("/{$segment}/slots", [TimeslotController::class, 'slots'])->defaults('type', $segment)->name("{$segment}.slots");
                    Route::post("/{$segment}", [TimeslotController::class, 'store'])->defaults('type', $segment)->name("{$segment}.store");
                    Route::put("/{$segment}/{timeslot}", [TimeslotController::class, 'update'])->whereNumber('timeslot')->defaults('type', $segment)->name("{$segment}.update");
                    Route::delete("/{$segment}/{timeslot}", [TimeslotController::class, 'destroy'])->whereNumber('timeslot')->defaults('type', $segment)->name("{$segment}.destroy");
                }
            });

            Route::get('/appointments', [AppointmentController::class, 'legacy'])->name('appointments');

            Route::prefix('/appointments')->name('appointments.')->group(function (): void {
                foreach (['face-to-face', 'telemedicine'] as $segment) {
                    Route::get("/{$segment}", [AppointmentController::class, 'index'])->defaults('type', $segment)->name($segment);
                    Route::post("/{$segment}", [AppointmentController::class, 'store'])->defaults('type', $segment)->name("{$segment}.store");
                }
            });

            Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
            Route::get('/appointments/{id}', [AppointmentController::class, 'show'])->whereNumber('id')->name('appointments.show');
            Route::put('/appointments/{id}', [AppointmentController::class, 'update'])->whereNumber('id')->name('appointments.update');
            Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy'])->whereNumber('id')->name('appointments.destroy');
            Route::post('/appointments/{id}/{action}', [AppointmentController::class, 'action'])->name('appointments.action');
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
            Route::get('/sms', [SmsController::class, 'index'])->name('sms');
            Route::post('/sms/send', [SmsController::class, 'send'])->name('sms.send');
            Route::delete('/sms/messages', [SmsController::class, 'destroyMessages'])->name('sms.messages.destroy');
            Route::get('/records', [RecordController::class, 'index'])->name('records');
            Route::post('/records', [RecordController::class, 'store'])->name('records.store');
            Route::delete('/records/{record}', [RecordController::class, 'destroy'])->name('records.destroy');
            Route::get('/reports', [ReportController::class, 'index'])->name('reports');
            Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
            Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
            Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('profile.password');

            // New Admin modal (profile menu in the top bar): creates another admin account.
            Route::post('/admins', [NewAdminController::class, 'store'])->middleware('throttle:10,1')->name('admins.store');
        });
    });
});