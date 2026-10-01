<?php

namespace App\Observers;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /** Model class => module name shown in the audit log. */
    private const MODULES = [
        Patient::class => 'Patients',
        Staff::class => 'Doctors',
        Admin::class => 'Triagers',
    ];

    /** Columns that change without a user doing anything meaningful. */
    private const NOISE = ['updated_at', 'remember_token'];

    public function created(Model $model): void
    {
        AuditLog::record('Create', $this->module($model), $model->getKey());
    }

    public function updated(Model $model): void
    {
        $changed = array_values(array_diff(array_keys($model->getChanges()), self::NOISE));

        if ($changed === []) {
            return;
        }

        $action = 'Update';

        if (count($changed) === 1) {
            if ($changed[0] === 'password') {
                $action = 'Change Password';
            } elseif ($model instanceof Patient && $changed[0] === 'status') {
                $action = $model->status === 'Active' ? 'Activate' : 'Deactivate';
            } elseif ($model instanceof Staff && $changed[0] === 'is_active') {
                $action = $model->is_active ? 'Activate' : 'Deactivate';
            }
        }

        AuditLog::record($action, $this->module($model), $model->getKey());
    }

    public function deleted(Model $model): void
    {
        AuditLog::record('Delete', $this->module($model), $model->getKey());
    }

    private function module(Model $model): string
    {
        return self::MODULES[$model::class] ?? class_basename($model);
    }
}
