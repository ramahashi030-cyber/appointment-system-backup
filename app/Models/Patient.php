<?php

namespace App\Models;

use App\Models\Concerns\NotifiesUsers;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Patient accounts are stored in this dedicated authenticatable model.
 */
class Patient extends Authenticatable
{
    use Notifiable, NotifiesUsers;

    protected $fillable = [
        'first_name',
        'middlename',
        'last_name',
        'dob',
        'gender',
        'contact_number',
        'email',
        'address',
        'username',
        'password',
        'otp_code',
        'status',
        'hospital_number',
        'otp_generated_at',
        'profile_pic',
    ];

    protected $hidden = [
        'password',
        'otp_code',
    ];

    // Table has created_at but no updated_at column.
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'otp_generated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->first_name, $this->middlename, $this->last_name])));
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(PatientNotification::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(PatientConsent::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(PatientRequest::class);
    }
}
