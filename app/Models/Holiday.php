<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Face-to-face holidays.
 *
 * This model uses a dedicated table (`holidays`) — no `service_type` column exists.
 * The HolidayController selects this model explicitly for the "face-to-face" type.
 * A no-op `forServiceType` scope is defined to safely absorb any stale global scope
 * registrations that might reference a non-existent `service_type` column.
 */
class Holiday extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'holiday_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
        ];
    }

    /**
     * No-op scope — the controller already resolves the correct model/table by type.
     * Defined to prevent "Unknown column 'service_type'" errors if a stale global
     * scope registration attempts to filter by a non-existent column.
     */
    public function scopeForServiceType(Builder $query, string $type): Builder
    {
        return $query;
    }
}