<?php

namespace App\Models;

use App\Observers\AppointmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(AppointmentObserver::class)]
class Appointment extends Model
{
    public const ACTIVE_STATUSES = [
        'Booked',
        'Pending',
        'Confirmed',
        'Approved',
    ];

    public const PATIENT_CANCELLABLE_STATUSES = [
        'Booked',
        'Pending',
        'Confirmed',
        'Approved',
    ];

    /**
     * Who cancelled the appointment that is being written right now.
     *
     * The observer fires the real-time notification without knowing which
     * portal asked for the cancellation, so the cancelling controller tags this
     * in-memory model first (the patient portal tags it `patient`, which keeps
     * the cancellation out of the triage queue and in the doctor's bell).
     *
     * It is deliberately not persisted: it only describes the current update.
     */
    public ?string $cancellationInitiatedBy = null;

    protected $fillable = [
        'patient_id',
        'service_id',
        'complaint',
        'consultation_reason',
        'symptoms',
        'complaint_details',
        'date',
        'time_slot',
        'status',
        'qr_code_path',
        'qr_code_token',
        'reminder_sent',
        'mode',
        'request_mode',
        'meeting_link',
        'room_opened',
        'opened_by',
        'room_opened_by',
        'cancellation_reason',
        'staff_id',
        'triager_status',
        'triager_action',
        'triager_remarks',
        'processed_by',
        'processed_at',
    ];

    protected $hidden = [
        'qr_code_token',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'symptoms' => 'array',
            'reminder_sent' => 'boolean',
            'room_opened' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Triage workflow statuses for patient appointment requests.
     */
    public const TRIAGE_ACTIONS = [
        'Approved',
        'Denied',
        'Suspend',
        'For health center consult',
        'for BUCAS center',
    ];

    public static function hasActiveStatus(?string $status): bool
    {
        return in_array(
            strtolower(trim((string) $status)),
            array_map('strtolower', self::ACTIVE_STATUSES),
            true,
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * IMPORTANT: `service_id` is used against BOTH the `services` table
     * and the `services_tele` table, disambiguated only by the `mode`
     * column, which has no DB constraint tying it to the right table.
     * Once we know the actual string values stored in `mode`, add
     * ->where('mode', '...') to these two relations so they can't
     * resolve to the wrong table's row.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function serviceTele(): BelongsTo
    {
        return $this->belongsTo(ServiceTele::class, 'service_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'opened_by');
    }
}
