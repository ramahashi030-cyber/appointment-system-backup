<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    private const EDITABLE = ['Pending', 'Booked', 'Approved', 'Confirmed'];

    private const DELETABLE = ['Pending', 'Rejected', 'Cancelled'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $doctor = (string) $request->query('doctor', '');
        $date = (string) $request->query('date', '');

        $query = Appointment::query()
            ->with(['patient', 'staff', 'service', 'serviceTele'])
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('complaint', 'like', "%{$search}%")
                        ->orWhere('consultation_reason', 'like', "%{$search}%")
                        ->orWhere('time_slot', 'like', "%{$search}%")
                        ->orWhereHas('patient', function (Builder $patientQuery) use ($search): void {
                            $patientQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('staff', function (Builder $staffQuery) use ($search): void {
                            $staffQuery->where('FirstName', 'like', "%{$search}%")
                                ->orWhere('LastName', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn (Builder $builder): Builder => $builder->where('status', $status))
            ->when($doctor !== '', fn (Builder $builder): Builder => $builder->where('staff_id', $doctor))
            ->when($date !== '', fn (Builder $builder): Builder => $builder->whereDate('date', $date))
            ->orderByDesc('date')
            ->orderByDesc('time_slot');

        $appointments = $query->paginate(20)->appends([
            'search' => $search,
            'status' => $status,
            'doctor' => $doctor,
            'date' => $date,
        ]);

        $providerModal = null;
        $appointmentDetail = null;

        if ($request->has('view')) {
            $providerModal = 'view';
            $appointmentDetail = Appointment::with(['patient', 'staff', 'service', 'serviceTele'])->findOrFail($request->integer('view'));
        }

        // Read-only status counts (one grouped query).
        $statusCounts = Appointment::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($count, $statusName) => [strtolower((string) $statusName) => (int) $count]);

        $countFor = fn (array $statuses): int => (int) collect($statuses)
            ->sum(fn ($statusName) => $statusCounts->get($statusName, 0));

        return view('admin.appointments', [
            'appointments' => $appointments,
            'appointmentStats' => [
                'total' => Appointment::count(),
                'booked' => $countFor(['pending', 'booked']),
                'approved' => $countFor(['approved', 'confirmed', 'in progress', 'completed']),
                'cancelled' => $countFor(['cancelled']),
                'pending' => $countFor(['pending']),
                'completed' => $countFor(['completed']),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'doctor' => $doctor,
                'date' => $date,
            ],
            'doctors' => Staff::where('is_active', true)->orderBy('LastName')->orderBy('FirstName')->get(),
            'patients' => Patient::orderBy('last_name')->orderBy('first_name')->get(),
            'services' => Service::orderBy('service_name')->get(['id', 'service_name']),
            'servicesTele' => ServiceTele::orderBy('service_name')->get(['id', 'service_name']),
            'providerModal' => $providerModal,
            'appointmentDetail' => $appointmentDetail,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request): array
    {
        $serviceTable = $request->input('mode') === 'TELE' ? 'services_tele' : 'services';

        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'date' => ['required', 'date'],
            'time_slot' => ['required'],
            'mode' => ['required', 'in:FACE,TELE'],
            'service_id' => ['required', 'integer', Rule::exists($serviceTable, 'id')],
            'consultation_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function respond(Request $request, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()
            ->route('admin.appointments')
            ->with($status < 400 ? 'success' : 'error', $message);
    }

    /** GET: appointment fields used to fill the edit form. */
    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        return response()->json([
            'id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'staff_id' => $appointment->staff_id,
            'date' => $appointment->date?->format('Y-m-d'),
            'time_slot' => $appointment->time_slot,
            'mode' => $appointment->mode,
            'service_id' => $appointment->service_id,
            'consultation_reason' => $appointment->consultation_reason,
            'status' => $appointment->status,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate($this->rules($request));
        $validated['status'] = 'Pending';

        Appointment::create($validated);

        return $this->respond($request, 'Appointment created successfully.', 201);
    }

    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        if (! in_array($appointment->status, self::EDITABLE, true)) {
            return $this->respond($request, "Cannot edit an appointment with status '{$appointment->status}'.", 422);
        }

        $appointment->update($request->validate($this->rules($request)));

        return $this->respond($request, 'Appointment updated successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        if (! in_array($appointment->status, self::DELETABLE, true)) {
            return $this->respond($request, "Cannot delete an appointment with status '{$appointment->status}'.", 422);
        }

        $appointment->delete();

        return $this->respond($request, 'Appointment deleted successfully.');
    }

    public function action(Request $request, int $id, string $action): RedirectResponse|JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        // action => [allowed current statuses, new status]
        $transitions = [
            'approve' => [['Pending'], 'Approved'],
            'reject' => [['Pending'], 'Rejected'],
            'confirm' => [['Approved'], 'Confirmed'],
            'cancel' => [['Pending', 'Approved', 'Confirmed'], 'Cancelled'],
            'start' => [['Confirmed'], 'In Progress'],
            'complete' => [['In Progress'], 'Completed'],
        ];

        if (! isset($transitions[$action])) {
            return $this->respond($request, 'Invalid action.', 422);
        }

        [$fromStatuses, $toStatus] = $transitions[$action];

        if (! in_array($appointment->status, $fromStatuses, true)) {
            return $this->respond($request, "Cannot {$action} an appointment with status '{$appointment->status}'.", 422);
        }

        $appointment->update(['status' => $toStatus]);

        return $this->respond($request, "Appointment marked as {$toStatus}.");
    }
}
