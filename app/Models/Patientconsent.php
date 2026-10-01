<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientConsent extends Model
{
    protected $table = 'patient_consent'; // singular in the DB

    const CREATED_AT = 'consented_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'ip_address',
        'user_agent',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
