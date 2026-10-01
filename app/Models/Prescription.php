<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'prescription_number',
        'doctor_name',
        'medicine',
        'quantity',
        'instructions',
        'remarks',
        'encounter_code',
        'license_no',
        'file_path',
        'notes',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
