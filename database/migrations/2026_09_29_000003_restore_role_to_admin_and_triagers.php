<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin') || ! Schema::hasTable('staff')) {
            return;
        }

        if (! Schema::hasColumn('admin', 'role')) {
            Schema::table('admin', function ($table): void {
                $table->string('role', 20)->default('admin')->after('lastname');
            });
        }

        DB::table('admin')->where('role', '')->update(['role' => 'admin']);

        $triagers = DB::table('staff')
            ->where('is_doctor', false)
            ->whereIn('username', ['Triager', 'triager1'])
            ->get();

        foreach ($triagers as $triager) {
            $exists = DB::table('admin')
                ->where('username', $triager->username)
                ->exists();

            if (! $exists) {
                DB::table('admin')->insert([
                    'firstname' => $triager->FirstName,
                    'lastname' => $triager->LastName,
                    'role' => 'triager',
                    'username' => $triager->username,
                    'password' => $triager->password,
                    'email' => $triager->email,
                    'contact_no' => $triager->contactno,
                    'created_at' => $triager->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin')) {
            return;
        }

        DB::table('admin')->where('role', 'triager')->delete();

        if (Schema::hasColumn('admin', 'role')) {
            Schema::table('admin', function ($table): void {
                $table->dropColumn('role');
            });
        }
    }
};
