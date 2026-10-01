<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        $addConsultationReason = ! Schema::hasColumn('appointments', 'consultation_reason');
        $addSymptoms = ! Schema::hasColumn('appointments', 'symptoms');
        $addComplaintDetails = ! Schema::hasColumn('appointments', 'complaint_details');
        $addQrCodeToken = ! Schema::hasColumn('appointments', 'qr_code_token');

        Schema::table('appointments', function (Blueprint $table) use ($addConsultationReason, $addSymptoms, $addComplaintDetails, $addQrCodeToken): void {
            if ($addConsultationReason) {
                $table->string('consultation_reason', 100)->nullable();
            }

            if ($addSymptoms) {
                $table->json('symptoms')->nullable();
            }

            if ($addComplaintDetails) {
                $table->text('complaint_details')->nullable();
            }

            if ($addQrCodeToken) {
                $table->char('qr_code_token', 64)->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $columns = array_filter([
                'consultation_reason',
                'symptoms',
                'complaint_details',
                'qr_code_token',
            ], fn (string $column): bool => Schema::hasColumn('appointments', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
