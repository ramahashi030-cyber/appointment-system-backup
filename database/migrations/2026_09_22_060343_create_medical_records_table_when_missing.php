<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `medical_records` table already exists in the legacy MySQL database
 * (patient_appointment) that QALINGA1 and this app share — same shape as
 * QALINGA1/medical_records.php reads it. On a real install this is a no-op;
 * it only builds the table on sqlite so the Records page can be tested.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('medical_records')) {
            return;
        }

        Schema::create('medical_records', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('patient_id')->nullable();
            $table->string('record_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        // Never drop the shared legacy table from a rollback.
    }
};
