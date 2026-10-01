<?php

use App\Http\Controllers\Doctor\DoctorAuthController;
use App\Http\Controllers\Doctor\DoctorDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('doctor')->name('doctor.')->group(function (): void {
    Route::get('/login', [DoctorAuthController::class, 'show'])->name('login');
    Route::post('/login', [DoctorAuthController::class, 'login'])->name('login.attempt');

    Route::middleware('doctor.auth')->group(function (): void {
        Route::get('/', [DoctorDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/appointments', [DoctorDashboardController::class, 'appointments'])->name('appointments');
        Route::get('/patients', [DoctorDashboardController::class, 'patients'])->name('patients');
        Route::get('/notifications', [DoctorDashboardController::class, 'notifications'])->name('notifications');
        Route::get('/profile', [DoctorDashboardController::class, 'profile'])->name('profile');
        Route::put('/profile', [DoctorDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [DoctorDashboardController::class, 'updatePassword'])->name('profile.password');
        Route::post('/appointments/{appointment}/open-room', [DoctorDashboardController::class, 'openRoom'])->name('appointments.open-room');
        Route::get('/appointments/{appointment}/room', [DoctorDashboardController::class, 'roomStatus'])->name('appointments.room');
        Route::post('/logout', [DoctorAuthController::class, 'logout'])->name('logout');
    });
});
