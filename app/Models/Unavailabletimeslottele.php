<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnavailableTimeslotTele extends Model
{
    protected $table = 'unavailable_timeslots_tele';

    public $timestamps = false;

    protected $fillable = [
        'service_id',
        'date',
        'time_slot',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    // The other relation your DB actually enforces with a real FK.
    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceTele::class, 'service_id');
    }
}
