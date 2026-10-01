<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local mirror of HOMIS prescription lines (hrxo). HOMIS is a separate
 * SQL Server database reached through ODBC; this table only exists so the
 * portal and admin panel have somewhere to cache or import rows before the
 * live HOMIS sync is wired up. It is created only when missing so an
 * existing table (and its data) is never touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prescriptions')) {
            return;
        }

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('prescription_number')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('medicine')->nullable();
            $table->string('quantity')->nullable();
            $table->text('instructions')->nullable();
            $table->text('remarks')->nullable();
            $table->string('encounter_code')->nullable();
            $table->string('license_no')->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('date')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
