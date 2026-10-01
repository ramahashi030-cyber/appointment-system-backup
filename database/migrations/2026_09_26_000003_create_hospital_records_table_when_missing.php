<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local mirror of HOMIS visit history (herlog / hopdlog / hadmlog). HOMIS is
 * a separate SQL Server database reached through ODBC; this table only exists
 * so the portal and admin panel have somewhere to cache or import rows before
 * the live HOMIS sync is wired up. It is created only when missing so an
 * existing table (and its data) is never touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hospital_records')) {
            return;
        }

        Schema::create('hospital_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('source')->default('Local');
            $table->string('visit_type')->nullable();
            $table->string('encounter_code')->nullable();
            $table->dateTime('visit_date')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_records');
    }
};
