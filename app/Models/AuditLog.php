<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'username', 'user_role', 'action', 'module', 'record_id', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /**
     * Record an action by the signed-in admin, triager or doctor.
     * Never throws: a logging failure must not block hospital operations.
     */
    public static function record(string $action, string $module, ?int $recordId): void
    {
        try {
            if ($user = Auth::guard('admin')->user()) {
                $role = ucfirst(strtolower((string) ($user->role ?: 'Admin')));
            } elseif ($user = Auth::guard('staff')->user()) {
                $role = 'Doctor';
            } else {
                return; // patient portal, console or system action
            }

            static::create([
                'user_id' => $user->getKey(),
                'username' => (string) ($user->username ?? $user->Username ?? $user->full_name ?? 'unknown'),
                'user_role' => $role,
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Record a patient portal event ('Login' or 'Logout').
     * Never throws: a logging failure must not block patient access.
     */
    public static function recordPatient(string $action, int $patientId, string $username): void
    {
        try {
            static::create([
                'user_id' => $patientId,
                'username' => mb_substr($username, 0, 100),
                'user_role' => 'Patient',
                'action' => $action,
                'module' => 'Authentication',
                'record_id' => null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
