<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The appointments table is created by a later migration in some install
        // orders, and the feature tests build it themselves. Altering a table
        // that is not there yet emits invalid SQL, so treat it as a no-op.
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->unsignedBigInteger('service_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->unsignedBigInteger('service_id')->nullable(false)->change();
        });
    }
};
