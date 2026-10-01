<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTimeslotTele extends Model
{
    protected $table = 'service_timeslots_tele';

    public $timestamps = false;

    protected $fillable = [
        'service_id',
        'time_slot',
        'slots',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceTele::class, 'service_id');
    }
}
