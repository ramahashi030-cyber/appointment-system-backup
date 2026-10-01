<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin')) {
            return;
        }

        if (! Schema::hasColumn('admin', 'role')) {
            Schema::table('admin', function (Blueprint $table): void {
                $table->string('role', 20)->default('admin')->after('lastname');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin')) {
            return;
        }

        if (Schema::hasColumn('admin', 'role')) {
            Schema::table('admin', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }
    }
};
