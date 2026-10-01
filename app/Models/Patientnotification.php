<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Named PatientNotification instead of Notification on purpose: Laravel's
 * built-in notification system (`php artisan notifications:table`) also
 * wants a table called `notifications`, but with a completely different
 * schema (uuid, type, notifiable_type/id, data, read_at). Your legacy
 * table is (patient_id, message, is_read, created_at) — same table name,
 * different shape. Keeping a distinct class name avoids a collision if
 * you ever add Laravel's native notifications later.
 */
class PatientNotification extends Model
{
    protected $table = 'notifications';

    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
