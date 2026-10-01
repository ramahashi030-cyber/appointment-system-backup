<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Patient\PatientPortalController;
use App\Http\Controllers\Patient\RecordsController;
use App\Http\Controllers\Patient\SessionCheckController;
use App\Http\Controllers\Patient\TelemedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patient routes
|--------------------------------------------------------------------------
| Patient registration, portal, records, and telemedicine routes are kept
| in this file so they can evolve independently from the admin area.
*/

Route::prefix('patient')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/register/verify', [RegisterController::class, 'showVerify'])->name('register.verify');
    Route::post('/register/verify', [RegisterController::class, 'verify'])->name('register.verify.attempt');
    Route::post('/register/resend', [RegisterController::class, 'resend'])->name('register.resend');
    Route::get('/register/update-password', [RegisterController::class, 'showUpdatePassword'])->name('password.force');
});

Route::get('/register/update-password', [RegisterController::class, 'showUpdatePassword'])
    ->name('password.force.legacy');
Route::post('/register/update-password', [RegisterController::class, 'updatePassword'])
    ->name('password.force.store');

// Logged-in patients whose account is deactivated are signed out on their next request.
Route::middleware('patient.active')->group(function (): void {
    Route::get('/patients/session-check', SessionCheckController::class)->name('patient.session-check');

    Route::prefix('patients')->group(function (): void {
        Route::get('/notifications', [PatientPortalController::class, 'notifications'])->name('patient.notifications');
        Route::get('/prescriptions', [PatientPortalController::class, 'prescriptions'])->name('patient.prescriptions');
        Route::get('/procedures', [PatientPortalController::class, 'procedures'])->name('patient.procedures');
        Route::get('/profile', [PatientPortalController::class, 'profile'])->name('patient.profile');
        Route::put('/profile', [PatientPortalController::class, 'updateProfile'])->name('patient.profile.update');
        Route::get('/data/prescriptions', [PatientPortalController::class, 'dataPrescriptions'])->name('patient.data.prescriptions');
        Route::get('/data/procedures', [PatientPortalController::class, 'dataProcedures'])->name('patient.data.procedures');
        Route::get('/data/records', [PatientPortalController::class, 'dataRecords'])->name('patient.data.records');
    });

    Route::get('/records', [RecordsController::class, 'index'])->name('records.index');
    Route::post('/records', [RecordsController::class, 'store'])->name('records.store');
    Route::put('/records/{id}', [RecordsController::class, 'update'])->name('records.update');
    Route::delete('/records/{id}', [RecordsController::class, 'destroy'])->name('records.destroy');

    Route::prefix('telemed')->group(function (): void {
        Route::get('/', [TelemedController::class, 'home'])->name('telemed.home');
        Route::post('consent', [TelemedController::class, 'consent'])->name('telemed.consent');
        Route::get('book', [TelemedController::class, 'showBook'])->name('telemed.book');
        Route::get('calendar', [TelemedController::class, 'calendar'])->name('telemed.calendar');
        Route::post('book', [TelemedController::class, 'store'])->name('telemed.book.store');
        Route::post('book/cancel', [TelemedController::class, 'cancel'])->name('telemed.book.cancel');
        Route::get('timeslots', [TelemedController::class, 'timeslots'])->name('telemed.timeslots');
        Route::get('appointments/{appointment}/qr', [TelemedController::class, 'qr'])->name('telemed.appointment.qr');
        Route::get('appointments/{appointment}/join', [TelemedController::class, 'join'])->name('telemed.appointment.join');
        Route::post('appointments/{appointment}/open-room', [TelemedController::class, 'openRoom'])->name('telemed.appointment.open-room');
        Route::get('appointments/{appointment}/room', [TelemedController::class, 'roomStatus'])->name('telemed.appointment.room');
        Route::get('my-appointments', [TelemedController::class, 'myAppointments'])->name('telemed.mine');
        Route::get('history', [TelemedController::class, 'history'])->name('telemed.history');
        Route::get('notifications/poll', [TelemedController::class, 'pollNotifications'])->name('telemed.notifications.poll');
    });
});
