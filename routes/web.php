<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BroadcastAuthController;
use App\Http\Controllers\NotificationController;
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
| Real-time notifications
|--------------------------------------------------------------------------
| Additive to the existing workflow: the bell JSON endpoints and the private
| channel handshake used by Laravel Echo. Nothing here changes how
| appointments are created, triaged, scheduled or joined.
*/

Route::prefix('notifications')->name('notifications.')->group(function (): void {
    // Private channel handshake used by Laravel Echo (Reverb).
    // Kept under /notifications so it never collides with the framework's
    // generic /broadcasting/auth endpoint, which assumes a single auth guard
    // and cannot resolve this app's session-only patients and doctors.
    Route::post('/auth', BroadcastAuthController::class)->name('auth');

    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
    Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
});

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
