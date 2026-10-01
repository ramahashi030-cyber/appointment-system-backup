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
            if (! Schema::hasColumn('appointments', 'triager_status')) {
                $table->string('triager_status', 30)->default('Pending')->after('status');
            }

            if (! Schema::hasColumn('appointments', 'triager_action')) {
                $table->string('triager_action', 50)->nullable()->after('triager_status');
            }

            if (! Schema::hasColumn('appointments', 'triager_remarks')) {
                $table->text('triager_remarks')->nullable()->after('triager_action');
            }

            if (! Schema::hasColumn('appointments', 'processed_by')) {
                $table->unsignedBigInteger('processed_by')->nullable()->after('triager_remarks');
            }

            if (! Schema::hasColumn('appointments', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('processed_by');
            }

            if (! Schema::hasColumn('appointments', 'request_mode')) {
                $table->string('request_mode', 10)->nullable()->after('mode');
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
                'triager_status',
                'triager_action',
                'triager_remarks',
                'processed_by',
                'processed_at',
                'request_mode',
            ], fn (string $column): bool => Schema::hasColumn('appointments', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
