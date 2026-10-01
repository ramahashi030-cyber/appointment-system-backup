<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transitional table retained for migration history. The following merge
 * migration copies its records into staff and removes it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctors')) {
            return;
        }

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('middlename')->nullable();
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('specialty')->default('Family Medicine');
            $table->string('status')->default('Active')->index();
            $table->string('profile_pic')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
