<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('staff') || Schema::hasColumn('staff', 'legacy_doctor_id')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_doctor_id')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('staff') || ! Schema::hasColumn('staff', 'legacy_doctor_id')) {
            return;
        }

        $indexes = [];

        foreach (Schema::getIndexes('staff') as $index) {
            if (in_array('legacy_doctor_id', $index['columns'], true)) {
                $indexes[] = $index['name'];
            }
        }

        foreach ($indexes as $index) {
            Schema::table('staff', function (Blueprint $table) use ($index): void {
                $table->dropIndex($index);
            });
        }

        Schema::table('staff', function (Blueprint $table): void {
            $table->dropColumn('legacy_doctor_id');
        });
    }
};
