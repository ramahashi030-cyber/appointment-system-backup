<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local mirror of HOMIS procedure orders (hdocord). HOMIS is a separate
 * SQL Server database reached through ODBC; this table only exists so the
 * portal and admin panel have somewhere to cache or import rows before the
 * live HOMIS sync is wired up. It is created only when missing so an
 * existing table (and its data) is never touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('procedures')) {
            return;
        }

        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('procedure_number')->nullable();
            $table->string('encounter_code')->nullable();
            $table->string('procedure')->nullable();
            $table->string('quantity')->nullable();
            $table->string('cost_center')->nullable();
            $table->boolean('result_available')->default(false);
            $table->string('result_url')->nullable();
            $table->dateTime('date')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
