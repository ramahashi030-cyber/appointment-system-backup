<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            if (! Schema::hasColumn('appointments', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('triager_remarks');
            }

            if (! Schema::hasColumn('appointments', 'room_opened_by')) {
                $table->string('room_opened_by', 20)->nullable()->after('opened_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $columns = array_filter([
                'cancellation_reason',
                'room_opened_by',
            ], fn (string $column): bool => Schema::hasColumn('appointments', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
