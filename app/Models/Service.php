<?php

namespace App\Models;

use App\Models\Concerns\InterpretsAvailabilityDays;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use InterpretsAvailabilityDays;

    // Table has created_at but no updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'service_name',
        'availability_day',
        'homis_code',
    ];

    public function timeslots(): HasMany
    {
        return $this->hasMany(ServiceTimeslot::class);
    }

    public function unavailableTimeslots(): HasMany
    {
        return $this->hasMany(UnavailableTimeslot::class);
    }

    // See the note on Appointment::service() re: the unscoped mode ambiguity.
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }
}
