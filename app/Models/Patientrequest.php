<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Named PatientRequest instead of Request on purpose — `Request` is
 * Laravel's own HTTP request class (Illuminate\Http\Request), which
 * every controller method type-hints. A model literally called
 * `Request` would force awkward fully-qualified references everywhere.
 *
 * NOTE: the `Complaints` column is capitalized in the DB (unlike every
 * other column here, which is snake_case). MySQL column name matching
 * is case-sensitive on Linux, so this must be referenced as exactly
 * `Complaints` — don't "fix" the casing without a migration + code sweep.
 */
class PatientRequest extends Model
{
    protected $table = 'request'; // singular in the DB

    protected $fillable = [
        'patient_id',
        'Complaints',
        'status',
        'approved_at',
        'approved_by',
        'remarks',
        'started',
        'processed_by',
        'consult_type',
        'symptoms',
        'complaint_details',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'processed_by');
    }
}
