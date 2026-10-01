<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Procedure extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'procedure_number',
        'encounter_code',
        'procedure',
        'quantity',
        'cost_center',
        'result_available',
        'result_url',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'result_available' => 'boolean',
            'date' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
