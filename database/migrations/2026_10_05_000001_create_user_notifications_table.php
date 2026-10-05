<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real-time notification feed for patients, doctors and the triage team.
     *
     * Deliberately a separate table from the legacy `notifications` table used by
     * the patient notification box (`App\Models\PatientNotification`), which has a
     * fixed (patient_id, message, is_read) shape and predates Laravel's
     * notification infrastructure. This table uses Laravel's native
     * `database` notification columns so `DatabaseNotification` behaviours
     * (read / unread / markAsRead) work as expected.
     */
    public function up(): void
    {
        if (Schema::hasTable('user_notifications')) {
            return;
        }

        Schema::create('user_notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
