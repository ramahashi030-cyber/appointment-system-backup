<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `patients` table already exists in the legacy MySQL database
 * (patient_appointment) that QALINGA1 and this app share, so on a real
 * install this migration is a no-op.
 *
 * It only builds the table when it is missing — i.e. in the sqlite test
 * database — so the auth flow can be covered by feature tests.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patients')) {
            return;
        }

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('middlename')->nullable();
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('otp_code')->nullable();
            $table->string('status')->default('Pending');
            $table->string('hospital_number')->nullable();
            $table->timestamp('otp_generated_at')->nullable();
            $table->string('profile_pic')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        // Never drop the shared legacy table from a rollback.
    }
};
