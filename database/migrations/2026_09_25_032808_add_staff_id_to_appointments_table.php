<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link appointments to the staff member providing the consultation.
     */
    public function up(): void
    {
        if (! Schema::hasTable('appointments') || Schema::hasColumn('appointments', 'staff_id')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->unsignedBigInteger('staff_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasColumn('appointments', 'staff_id')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['staff_id']);
            $table->dropColumn('staff_id');
        });
    }
};
