<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class StaffDoctorSchema
{
    public static function migrateDoctorsIntoStaff(): void
    {
        self::ensureStaffTable();
        self::markExistingProvidersAsDoctors();

        if (! Schema::hasTable('doctors')) {
            return;
        }

        DB::transaction(function (): void {
            $doctors = DB::table('doctors')->orderBy('id')->get();
            $copiedCount = 0;

            foreach ($doctors as $doctor) {
                $staff = filled($doctor->email)
                    ? DB::table('staff')
                        ->whereRaw('LOWER(email) = ?', [strtolower($doctor->email)])
                        ->first()
                    : null;

                $isActive = strtolower((string) $doctor->status) === 'active';
                $site = in_array($staff->site ?? null, ['TELE', 'FACE', 'BOTH'], true)
                    ? $staff->site
                    : 'TELE';
                $values = [
                    'username' => filled($staff->username ?? null) ? $staff->username : $doctor->email,
                    'password' => $doctor->password,
                    'LastName' => $doctor->last_name,
                    'FirstName' => $doctor->first_name,
                    'MiddleName' => $doctor->middlename,
                    'contactno' => $staff->contactno ?? 'N/A',
                    'is_verified' => $isActive,
                    'site' => $site,
                    'email' => $doctor->email,
                    'is_active' => $isActive,
                    'is_doctor' => true,
                    'profile_pic' => $doctor->profile_pic,
                    'created_at' => $doctor->created_at,
                    'updated_at' => $doctor->updated_at,
                ];

                if ($staff !== null) {
                    DB::table('staff')->where('id', $staff->id)->update($values);
                } else {
                    DB::table('staff')->insert($values);
                }

                $copiedCount++;
            }

            if ($copiedCount !== $doctors->count()) {
                throw new \RuntimeException('Not all doctor accounts were copied into the staff table.');
            }
        });

        Schema::dropIfExists('doctors');
        self::markExistingProvidersAsDoctors();
    }

    public static function ensureStaffTable(): void
    {
        if (! Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table): void {
                $table->id();
                $table->string('username', 50)->nullable();
                $table->string('password')->nullable();
                $table->string('LastName', 100)->nullable();
                $table->string('FirstName', 100)->nullable();
                $table->string('MiddleName', 100)->nullable();
                $table->string('contactno', 20)->nullable();
                $table->string('otp', 6)->nullable();
                $table->boolean('is_verified')->default(false);
                $table->string('site', 20)->nullable();
                $table->string('employee_id')->nullable()->unique();
                $table->timestamps();
            });
        }

        $columns = [
            'username' => fn (Blueprint $table) => $table->string('username', 50)->nullable(),
            'password' => fn (Blueprint $table) => $table->string('password')->nullable(),
            'LastName' => fn (Blueprint $table) => $table->string('LastName', 100)->nullable(),
            'FirstName' => fn (Blueprint $table) => $table->string('FirstName', 100)->nullable(),
            'MiddleName' => fn (Blueprint $table) => $table->string('MiddleName', 100)->nullable(),
            'contactno' => fn (Blueprint $table) => $table->string('contactno', 20)->nullable(),
            'otp' => fn (Blueprint $table) => $table->string('otp', 6)->nullable(),
            'is_verified' => fn (Blueprint $table) => $table->boolean('is_verified')->default(false),
            'site' => fn (Blueprint $table) => $table->string('site', 20)->nullable(),
            'employee_id' => fn (Blueprint $table) => $table->string('employee_id')->nullable()->unique(),
            'email' => fn (Blueprint $table) => $table->string('email', 191)->nullable(),
            'consultation_type' => fn (Blueprint $table) => $table->string('consultation_type', 100)->nullable(),
            'is_active' => fn (Blueprint $table) => $table->boolean('is_active')->default(true),
            'availability' => fn (Blueprint $table) => $table->json('availability')->nullable(),
            'is_doctor' => fn (Blueprint $table) => $table->boolean('is_doctor')->default(false)->index(),
            'legacy_doctor_id' => fn (Blueprint $table) => $table->unsignedBigInteger('legacy_doctor_id')->nullable()->unique(),
            'profile_pic' => fn (Blueprint $table) => $table->string('profile_pic')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('staff', $column)) {
                Schema::table('staff', function (Blueprint $table) use ($definition): void {
                    $definition($table);
                });
            }
        }
    }

    public static function markExistingProvidersAsDoctors(): void
    {
        if (! Schema::hasTable('staff') || ! Schema::hasColumn('staff', 'is_doctor')) {
            return;
        }

        DB::table('staff')
            ->where(function ($query): void {
                $query->where(function ($providerQuery): void {
                    $providerQuery->whereNotNull('consultation_type')->where('consultation_type', '!=', '');
                })->orWhereIn('site', ['TELE', 'BOTH', 'Telemedicine']);
            })
            ->where(function ($query): void {
                $query->whereNull('is_doctor')->orWhere('is_doctor', false);
            })
            ->update(['is_doctor' => true]);
    }
}
