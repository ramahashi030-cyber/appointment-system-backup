<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patient authentication
|--------------------------------------------------------------------------
| Port of QALINGA1 login.php / login_process.php / register*.php /
| verify.php. The login screen itself is the app's landing page.
*/

Route::get('/', [LoginController::class, 'show'])
    ->name('auth.login');
Route::post('/login', [LoginController::class, 'login'])
    ->name('login.attempt');
Route::get('/logout', [LoginController::class, 'logout'])
    ->name('logout');

Route::get('/reset-password', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.reset.attempt');

/*
|--------------------------------------------------------------------------
| Domain route files
|--------------------------------------------------------------------------
| Keep admin and patient route registration independently maintainable.
*/
require __DIR__.'/admin.php';
require __DIR__.'/patient.php';
require __DIR__.'/doctor.php';
require __DIR__.'/triager.php';
