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

        Schema::table('admin', function (Blueprint $table): void {
            if (! Schema::hasColumn('admin', 'triage_channel')) {
                $table->string('triage_channel', 10)->default('both')->after('role');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin')) {
            return;
        }

        Schema::table('admin', function (Blueprint $table): void {
            if (Schema::hasColumn('admin', 'triage_channel')) {
                $table->dropColumn('triage_channel');
            }
        });
    }
};
