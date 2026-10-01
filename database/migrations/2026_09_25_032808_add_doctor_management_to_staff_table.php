<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the fields used by the doctor and healthcare-provider roster.
     */
    public function up(): void
    {
        if (! Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table): void {
                $table->id();
                $table->string('username', 50)->nullable();
                $table->string('password')->nullable();
                $table->string('LastName', 100)->nullable();
                $table->string('FirstName', 100)->nullable();
                $table->string('MiddleName', 100)->nullable();
                $table->string('contactno', 20)->nullable();
                $table->string('otp', 6)->nullable();
                $table->boolean('is_verified')->default(false);
                $table->string('site', 20)->nullable();
                $table->string('employee_id')->nullable()->unique();
                $table->timestamps();
            });
        }

        Schema::table('staff', function (Blueprint $table): void {
            if (! Schema::hasColumn('staff', 'email')) {
                $table->string('email', 191)->nullable();
            }

            if (! Schema::hasColumn('staff', 'specialization')) {
                $table->string('specialization', 150)->nullable();
            }

            if (! Schema::hasColumn('staff', 'department')) {
                $table->string('department', 150)->nullable();
            }

            if (! Schema::hasColumn('staff', 'consultation_type')) {
                $table->string('consultation_type', 100)->nullable();
            }

            if (! Schema::hasColumn('staff', 'consultation_fee')) {
                $table->decimal('consultation_fee', 10, 2)->nullable();
            }

            if (! Schema::hasColumn('staff', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (! Schema::hasColumn('staff', 'availability')) {
                $table->json('availability')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table): void {
            $columns = [
                'email',
                'specialization',
                'department',
                'consultation_type',
                'consultation_fee',
                'is_active',
                'availability',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('staff', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
