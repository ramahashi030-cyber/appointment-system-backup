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
        Schema::create('appointment_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->index();
            $table->string('token_digest', 64)->nullable();
            $table->string('kiosk_name', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->uuid('scan_id')->unique();
            $table->string('result', 20)->default('pending');
            $table->text('reason')->nullable();
            $table->string('patient_name', 191)->nullable();
            $table->string('hospital_number', 20)->nullable();
            $table->string('homis_encounter_code', 48)->nullable();
            $table->string('message', 191)->nullable();
            $table->string('session_hash', 64)->nullable()->index();
            $table->timestamp('scanned_at')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_checkins');
    }
};
