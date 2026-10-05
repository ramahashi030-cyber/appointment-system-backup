<?php

namespace App\Models;

use App\Models\Concerns\NotifiesUsers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;

/**
 * Staff and doctor data model.
 *
 * The staff table is retained for staff and doctor records; administrator
 * authentication is handled by the separate Admin model.
 */
class Staff extends Authenticatable
{
    use NotifiesUsers, RoutesNotifications;

    protected $table = 'staff';

    protected $fillable = [
        'username',
        'employee_id',
        'password',
        'LastName',
        'FirstName',
        'MiddleName',
        'contactno',
        'otp',
        'is_verified',
        'site',
        'email',
        'consultation_type',
        'is_active',
        'availability',
        'is_doctor',
        'legacy_doctor_id',
        'profile_pic',
    ];

    protected $hidden = [
        'password',
        'otp',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'is_doctor' => 'boolean',
            'legacy_doctor_id' => 'integer',
            'availability' => 'array',
            'password' => 'hashed',
        ];
    }

    public function scopeDoctors(Builder $query): Builder
    {
        return $query->where('is_doctor', true);
    }

    public function scopeActiveDoctors(Builder $query): Builder
    {
        return $query->where('is_doctor', true)
            ->where('is_active', true);
    }

    public function specialty(): string
    {
        return 'Family Medicine';
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->FirstName,
            $this->MiddleName,
            $this->LastName,
        ])));
    }
}
