<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('username', 100);
            $table->string('user_role', 30);
            $table->string('action', 60);
            $table->string('module', 50);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->timestamp('created_at')->index();
            $table->index(['module', 'record_id']);
            $table->index(['user_role', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
