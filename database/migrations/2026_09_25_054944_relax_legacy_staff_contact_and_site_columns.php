<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow migrated doctor records to use the richer staff profile fields.
     */
    public function up(): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `staff` MODIFY `contactno` VARCHAR(20) NULL');
            DB::statement('ALTER TABLE `staff` MODIFY `site` VARCHAR(20) NULL');

            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE staff ALTER COLUMN contactno DROP NOT NULL');
        DB::statement('ALTER TABLE staff ALTER COLUMN contactno TYPE VARCHAR(20)');
        DB::statement('ALTER TABLE staff ALTER COLUMN site TYPE VARCHAR(20)');
    }

    /**
     * Keep the relaxed columns when nullable doctor records already exist.
     */
    public function down(): void
    {
        if (! Schema::hasTable('staff') || Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (DB::table('staff')->whereNull('contactno')->doesntExist()) {
            DB::statement('ALTER TABLE `staff` MODIFY `contactno` VARCHAR(11) NOT NULL');
        }

        if (DB::table('staff')->whereNull('site')->doesntExist() && DB::table('staff')->where('site', 'Telemedicine')->doesntExist()) {
            DB::statement('ALTER TABLE `staff` MODIFY `site` VARCHAR(4) NOT NULL');
        }
    }
};
