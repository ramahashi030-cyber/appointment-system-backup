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

        DB::transaction(function (): void {
            $triagers = DB::table('admin')
                ->where('role', 'triager')
                ->orderBy('id')
                ->get();

            foreach ($triagers as $triager) {
                $existing = DB::table('staff')
                    ->whereRaw('LOWER(username) = ?', [strtolower((string) $triager->username)])
                    ->first();

                if ($existing !== null) {
                    DB::table('staff')->where('id', $existing->id)->update([
                        'FirstName' => $triager->firstname,
                        'LastName' => $triager->lastname,
                        'contactno' => $triager->contact_no,
                        'email' => $triager->email,
                        'is_doctor' => false,
                        'is_active' => true,
                        'is_verified' => true,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('staff')->insert([
                        'username' => $triager->username,
                        'password' => $triager->password,
                        'FirstName' => $triager->firstname,
                        'LastName' => $triager->lastname,
                        'contactno' => $triager->contact_no,
                        'email' => $triager->email,
                        'is_doctor' => false,
                        'is_active' => true,
                        'is_verified' => true,
                        'created_at' => $triager->created_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('admin')->where('role', 'triager')->delete();
        });

        if (Schema::hasColumn('admin', 'role')) {
            Schema::table('admin', function ($table): void {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin') || ! Schema::hasTable('staff')) {
            return;
        }

        if (! Schema::hasColumn('admin', 'role')) {
            Schema::table('admin', function ($table): void {
                $table->string('role', 20)->default('admin')->after('lastname');
            });
        }

        DB::table('staff')
            ->where('is_doctor', false)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotIn('username', ['joma', 'user2', 'dfcmpgmi_tele', 'dfcmpgmi_f2f', 'aldrins', 'ped is do', 'aldrin.gwapo@qmmc.local', 'test'])
            ->orderBy('id')
            ->get()
            ->each(function (object $staff): void {
                $exists = DB::table('admin')
                    ->where('username', $staff->username)
                    ->exists();

                if (! $exists) {
                    DB::table('admin')->insert([
                        'firstname' => $staff->FirstName,
                        'lastname' => $staff->LastName,
                        'role' => 'triager',
                        'username' => $staff->username,
                        'password' => $staff->password,
                        'email' => $staff->email,
                        'contact_no' => $staff->contactno,
                        'created_at' => $staff->created_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }
};
