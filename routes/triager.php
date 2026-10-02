<?php

use App\Http\Controllers\Triager\TriagerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Triager routes
|--------------------------------------------------------------------------
| Staff-facing triage dashboard where pending patient appointment requests
| are processed for both face-to-face and telemedicine consultations.
*/

Route::prefix('triager')->name('triager.')->group(function (): void {
    Route::middleware(['auth:admin', 'triager.role'])->group(function (): void {
        Route::get('/', [TriagerController::class, 'dashboard'])->name('dashboard');
        Route::post('/requests/{appointment}/start', [TriagerController::class, 'startProcessing'])->name('requests.start');
        Route::post('/requests/{appointment}/update', [TriagerController::class, 'updateRequest'])->name('requests.update');
        Route::post('/requests/{appointment}/schedule/telemed', [TriagerController::class, 'scheduleTelemed'])->name('requests.schedule.telemed');
        Route::post('/requests/{appointment}/schedule/face', [TriagerController::class, 'scheduleFace'])->name('requests.schedule.face');
        Route::get('/processed/print', [TriagerController::class, 'printProcessed'])->name('processed.print');
        Route::get('/calendar/telemed', [TriagerController::class, 'telemedCalendar'])->name('calendar.telemed');
        Route::get('/calendar/face', [TriagerController::class, 'faceCalendar'])->name('calendar.face');
        Route::get('/timeslots/telemed', [TriagerController::class, 'telemedTimeslots'])->name('timeslots.telemed');
        Route::get('/timeslots/face', [TriagerController::class, 'faceTimeslots'])->name('timeslots.face');
    });
});
