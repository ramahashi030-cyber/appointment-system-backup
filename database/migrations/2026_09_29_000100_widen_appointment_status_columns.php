<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Fixes: SQLSTATE[01000] 1265 Data truncated for column 'status'.
 *
 * Only touches a column when it is an ENUM or a VARCHAR shorter than needed.
 * It PRESERVES the column's current nullability and current default, and it
 * never updates or deletes rows. Columns that are already wide enough are skipped.
 *
 * Preview the exact SQL without running it:
 *     php artisan migrate --pretend
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = collect(Schema::getColumns('appointments'))->keyBy('name');

        // column => minimum VARCHAR length needed
        $targets = [
            'status' => 30,
            'triager_status' => 20,
            'triager_action' => 60,
        ];

        foreach ($targets as $name => $length) {
            $column = $columns->get($name);

            if ($column === null) {
                continue; // column does not exist, nothing to widen
            }

            $isEnum = ($column['type_name'] ?? '') === 'enum';
            $currentLength = preg_match('/^varchar\((\d+)\)/i', (string) ($column['type'] ?? ''), $match)
                ? (int) $match[1]
                : null;
            $isTooShort = $currentLength !== null && $currentLength < $length;

            if (! $isEnum && ! $isTooShort) {
                continue; // already fine, leave untouched
            }

            $nullSql = ($column['nullable'] ?? true) ? 'NULL' : 'NOT NULL';

            // Keep the existing default exactly as it is (MariaDB returns it quoted).
            $default = $column['default'] ?? null;
            $defaultSql = '';

            if ($default !== null && $default !== '') {
                $default = trim((string) $default, "'");
                $defaultSql = 'DEFAULT '.DB::getPdo()->quote($default);
            }

            DB::statement("ALTER TABLE `appointments` MODIFY `{$name}` VARCHAR({$length}) {$nullSql} {$defaultSql}");
        }
    }

    public function down(): void
    {
        // Not reversible: the original ENUM definitions are unknown.
    }
};
