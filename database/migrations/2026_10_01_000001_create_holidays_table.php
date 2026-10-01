<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Intentionally a no-op.
 *
 * This screen does not need a table. Both holiday tables already exist in the
 * hospital database and predate this feature:
 *
 *   holidays       — Face to Face   (id, holiday_date, description)
 *   holidays_tele  — Telemedicine   (id, holiday_date, description)
 *
 * An earlier draft of this feature assumed a single `holidays` table carrying a
 * `service_type` discriminator. That was wrong twice over: `holidays` already
 * existed, so this migration could only ever fail with "table already exists",
 * and no such column was ever added to the live schema — which is what made the
 * holiday pages return a 500 (`Unknown column 'service_type'`).
 *
 * Holidays are now read and written per table, selected by the URL segment, in
 * App\Http\Controllers\Admin\HolidayController.
 *
 * Kept in place rather than deleted so it stays recorded in the migrations
 * table. If it has never run on this database, `php artisan migrate` will now
 * succeed and change nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No schema change required.
    }

    public function down(): void
    {
        // No schema change to reverse.
    }
};