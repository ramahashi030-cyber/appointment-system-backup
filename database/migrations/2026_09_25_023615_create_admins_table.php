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
        Schema::create('admin', function (Blueprint $table): void {
            $table->id();
            $table->string('firstname', 100);
            $table->string('lastname', 100);
            $table->string('username', 100)->unique();
            $table->string('password');
            $table->string('email', 191)->unique();
            $table->string('contact_no', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};
