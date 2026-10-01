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
        if (! Schema::hasTable('staff')) {
            return;
        }

        $columns = array_values(array_filter(
            ['specialization', 'department', 'consultation_fee'],
            fn (string $column): bool => Schema::hasColumn('staff', $column),
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) use ($columns): void {
            foreach ($columns as $column) {
                $table->dropColumn($column);
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

        $columns = array_values(array_filter(
            ['specialization', 'department', 'consultation_fee'],
            fn (string $column): bool => ! Schema::hasColumn('staff', $column),
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) use ($columns): void {
            if (in_array('specialization', $columns, true)) {
                $table->string('specialization', 150)->nullable();
            }

            if (in_array('department', $columns, true)) {
                $table->string('department', 150)->nullable();
            }

            if (in_array('consultation_fee', $columns, true)) {
                $table->decimal('consultation_fee', 10, 2)->nullable();
            }
        });
    }
};
