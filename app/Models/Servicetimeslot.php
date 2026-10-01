<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTimeslot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_id',
        'time_slot',
        'slots',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
