<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class AppointmentSchema
{
    private static bool $isReady = false;

    public static function ensureCompatibleColumns(): void
    {
        if (self::$isReady || ! Schema::hasTable('appointments')) {
            return;
        }

        $missing = [
            'consultation_reason' => ! Schema::hasColumn('appointments', 'consultation_reason'),
            'symptoms' => ! Schema::hasColumn('appointments', 'symptoms'),
            'complaint_details' => ! Schema::hasColumn('appointments', 'complaint_details'),
            'qr_code_token' => ! Schema::hasColumn('appointments', 'qr_code_token'),
            'cancellation_reason' => ! Schema::hasColumn('appointments', 'cancellation_reason'),
            'room_opened_by' => ! Schema::hasColumn('appointments', 'room_opened_by'),
        ];

        if (! in_array(true, $missing, true)) {
            self::$isReady = true;

            return;
        }

        try {
            Schema::table('appointments', function (Blueprint $table) use ($missing): void {
                if ($missing['consultation_reason']) {
                    $table->string('consultation_reason', 100)->nullable();
                }

                if ($missing['symptoms']) {
                    $table->json('symptoms')->nullable();
                }

                if ($missing['complaint_details']) {
                    $table->text('complaint_details')->nullable();
                }

                if ($missing['qr_code_token']) {
                    $table->char('qr_code_token', 64)->nullable()->unique();
                }

                if ($missing['cancellation_reason']) {
                    $table->text('cancellation_reason')->nullable();
                }

                if ($missing['room_opened_by']) {
                    $table->string('room_opened_by', 20)->nullable();
                }
            });
        } catch (QueryException $exception) {
            if (! self::hasAllColumns()) {
                throw $exception;
            }
        }

        self::$isReady = true;
    }

    private static function hasAllColumns(): bool
    {
        return collect([
            'consultation_reason',
            'symptoms',
            'complaint_details',
            'qr_code_token',
        ])->every(fn (string $column): bool => Schema::hasColumn('appointments', $column));
    }

    /**
     * Whether `appointments.cancellation_reason` exists.
     *
     * Callers that write the column must gate on this so an un-migrated
     * database degrades to a status-only cancellation instead of a 500.
     */
    public static function hasCancellationReasonColumn(): bool
    {
        return Schema::hasTable('appointments')
            && Schema::hasColumn('appointments', 'cancellation_reason');
    }
}
