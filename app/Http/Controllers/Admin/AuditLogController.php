<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        return $request->query('type') === 'patients'
            ? $this->patientLogins($request)
            : $this->staffActivity($request);
    }

    private function staffActivity(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');
        $module = (string) $request->query('module', '');
        $action = (string) $request->query('action', '');
        $date = (string) $request->query('date', '');

        $staff = fn (): Builder => AuditLog::query()->where('user_role', '!=', 'Patient');

        $logs = $staff()
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $q) use ($search): void {
                    $q->where('username', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $q->orWhere('record_id', (int) $search)
                            ->orWhere('user_id', (int) $search);
                    }
                });
            })
            ->when($role !== '', fn (Builder $b): Builder => $b->where('user_role', $role))
            ->when($module !== '', fn (Builder $b): Builder => $b->where('module', $module))
            ->when($action !== '', fn (Builder $b): Builder => $b->where('action', $action))
            ->when($date !== '', fn (Builder $b): Builder => $b->whereDate('created_at', $date))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends(['search' => $search, 'role' => $role, 'module' => $module, 'action' => $action, 'date' => $date]);

        $this->attachPatients($logs);

        return view('admin.audit-logs', [
            'logs' => $logs,
            'filters' => ['search' => $search, 'role' => $role, 'module' => $module, 'action' => $action, 'date' => $date],
            'tab' => $role !== '' ? $role : 'all',
            'roles' => $this->staffRoles(),
            'modules' => $staff()->distinct()->orderBy('module')->pluck('module'),
            'actions' => $staff()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    private function patientLogins(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $date = (string) $request->query('date', '');

        $logs = AuditLog::query()
            ->where('user_role', 'Patient')
            ->where('action', 'Login')
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

                $builder->where(function (Builder $q) use ($search, $terms): void {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhereIn('user_id', Patient::query()->select('id')->where(function (Builder $p) use ($terms): void {
                            foreach ($terms as $term) {
                                $p->where(function (Builder $n) use ($term): void {
                                    $n->where('first_name', 'like', "%{$term}%")
                                        ->orWhere('middlename', 'like', "%{$term}%")
                                        ->orWhere('last_name', 'like', "%{$term}%");
                                });
                            }
                        }));

                    if (ctype_digit($search)) {
                        $q->orWhere('user_id', (int) $search);
                    }
                });
            })
            ->when($date !== '', fn (Builder $b): Builder => $b->whereDate('created_at', $date))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends(['type' => 'patients', 'search' => $search, 'date' => $date]);

        $this->attachPatients($logs);
        $this->attachSessionState($logs);

        return view('admin.audit-logs-patients', [
            'logs' => $logs,
            'filters' => ['search' => $search, 'date' => $date],
            'tab' => 'patients',
        ]);
    }

    /**
     * For each Login row, find the patient's next Login/Logout event.
     * Logout next = logged out; Login next = session ended without logout;
     * nothing next = still logged in.
     */
    private function attachSessionState(LengthAwarePaginator $logs): void
    {
        $items = $logs->getCollection();

        if ($items->isEmpty()) {
            return;
        }

        $events = AuditLog::query()
            ->where('user_role', 'Patient')
            ->whereIn('action', ['Login', 'Logout'])
            ->whereIn('user_id', $items->pluck('user_id')->unique()->values())
            ->where('id', '>=', $items->min('id'))
            ->orderBy('id')
            ->get(['id', 'user_id', 'action', 'created_at'])
            ->groupBy('user_id');

        $items->each(function (AuditLog $log) use ($events): void {
            $next = $events->get($log->user_id)?->first(fn ($e) => $e->id > $log->id);

            $log->setAttribute('logout_at', $next?->action === 'Logout' ? $next->created_at : null);
            $log->setAttribute('session_state', match (true) {
                $next === null => 'active',
                $next->action === 'Logout' => 'logged_out',
                default => 'ended',
            });
        });
    }

    /**
     * Resolve the patient behind each log row for display only.
     * Appointments: via the appointment. Patients: the record itself.
     * Patient logins: the user ID. Everything else has no patient.
     */
    private function attachPatients(LengthAwarePaginator $logs): void
    {
        $items = $logs->getCollection();

        $appointmentIds = $items
            ->where('module', 'Appointments')
            ->pluck('record_id')
            ->filter()
            ->unique()
            ->values();

        $appointmentPatients = $appointmentIds->isEmpty()
            ? collect()
            : Appointment::whereIn('id', $appointmentIds)->pluck('patient_id', 'id');

        $patientIdFor = function (AuditLog $log) use ($appointmentPatients): ?int {
            $id = match (true) {
                $log->module === 'Appointments' => $appointmentPatients->get($log->record_id),
                $log->module === 'Patients' => $log->record_id,
                $log->user_role === 'Patient' => $log->user_id,
                default => null,
            };

            return $id ? (int) $id : null;
        };

        $patientIds = $items->map($patientIdFor)->filter()->unique()->values();

        $patients = $patientIds->isEmpty()
            ? collect()
            : Patient::whereIn('id', $patientIds)->get()->keyBy('id');

        $items->each(function (AuditLog $log) use ($patientIdFor, $patients): void {
            $patientId = $patientIdFor($log);
            $patient = $patientId ? $patients->get($patientId) : null;

            $log->setAttribute('patient_id_display', $patientId);
            $log->setAttribute('patient_name_display', $patient ? $this->patientName($patient) : null);
        });
    }

    private function patientName(Patient $patient): string
    {
        return trim(implode(' ', array_filter([
            $patient->first_name,
            $patient->middlename,
            $patient->last_name,
        ])));
    }

    /** @return Collection<int, string> */
    private function staffRoles(): Collection
    {
        return AuditLog::query()
            ->where('user_role', '!=', 'Patient')
            ->distinct()
            ->orderBy('user_role')
            ->pluck('user_role');
    }
}
