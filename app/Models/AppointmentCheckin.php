<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kiosk QR check-in audit trail.
 *
 * One row is recorded for every kiosk scan (valid or not). The row is created
 * in "processing" state while the HOMIS registration runs and finished as
 * "completed" or "failed". The homis_encounter_code column is the cross-system
 * idempotency key: once a check-in is completed, the encounter code it used is
 * reused for every repeat of the same appointment so HOMIS never receives a
 * second encounter.
 */
class AppointmentCheckin extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'appointment_id',
        'token_digest',
        'kiosk_name',
        'ip_address',
        'scan_id',
        'result',
        'reason',
        'patient_name',
        'hospital_number',
        'homis_encounter_code',
        'message',
        'session_hash',
        'scanned_at',
        'confirmed_at',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'result' => 'pending',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scanned_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
