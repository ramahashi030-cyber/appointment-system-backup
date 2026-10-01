<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The legacy MySQL database has `users`; a fresh database (sqlite in the
     * test suite) has neither, so only rename when there is something to
     * rename and no `staff` to collide with.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasTable('staff')) {
            return;
        }

        Schema::rename('users', 'staff');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('staff') || Schema::hasTable('users')) {
            return;
        }

        Schema::rename('staff', 'users');
    }
};
