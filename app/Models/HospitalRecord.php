<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalRecord extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'source',
        'visit_type',
        'encounter_code',
        'visit_date',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
