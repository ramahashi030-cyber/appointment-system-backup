<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Telemedicine holidays.
 *
 * Uses the `holidays_tele` table — no `service_type` column exists.
 * The HolidayController selects this model explicitly for the "telemedicine" type.
 * A no-op `forServiceType` scope is defined for consistency with the Holiday model.
 */
class HolidayTele extends Model
{
    protected $table = 'holidays_tele';

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
     */
    public function scopeForServiceType(Builder $query, string $type): Builder
    {
        return $query;
    }
}
