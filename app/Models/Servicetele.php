<?php

namespace App\Models;

use App\Models\Concerns\InterpretsAvailabilityDays;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTele extends Model
{
    use InterpretsAvailabilityDays;

    protected $table = 'services_tele';

    const UPDATED_AT = null;

    protected $fillable = [
        'service_name',
        'availability_day',
        'homis_code',
    ];

    public function timeslots(): HasMany
    {
        return $this->hasMany(ServiceTimeslotTele::class, 'service_id');
    }

    public function unavailableTimeslots(): HasMany
    {
        return $this->hasMany(UnavailableTimeslotTele::class, 'service_id');
    }

    // See the note on Appointment::serviceTele() re: the unscoped mode ambiguity.
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }
}
