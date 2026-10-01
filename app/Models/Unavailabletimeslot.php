<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnavailableTimeslot extends Model
{
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

    // This is the one relation your DB actually enforces with a real FK.
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
