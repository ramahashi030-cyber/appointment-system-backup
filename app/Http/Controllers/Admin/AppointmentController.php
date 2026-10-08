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

    /**
     * Route `type` segment => the `appointments.mode` value and page text.
     */
    private const TYPES = [
        'face-to-face' => [
            'mode' => 'FACE',
            'title' => 'Appointments (Face to Face)',
            'description' => 'Manage face-to-face appointments.',
            'banner_icon' => 'bi-people-fill',
            'icon' => 'bi-people-fill',
        ],
        'telemedicine' => [
            'mode' => 'TELE',
            'title' => 'Appointments (Telemedicine)',
            'description' => 'Manage telemedicine appointments.',
            'banner_icon' => 'bi-camera-video-fill',
            'icon' => 'bi-camera-video-fill',
        ],
    ];

    private function typeConfig(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    /** Old /admin/appointments URL: redirect to the matching type page, keeping query filters. */
    public function legacy(Request $request): RedirectResponse
    {
        $mode = 'FACE';

        if ($request->filled('view')) {
            $mode = Appointment::whereKey($request->integer('view'))->value('mode') ?? 'FACE';
        }

        $segment = $mode === 'TELE' ? 'telemedicine' : 'face-to-face';

        return redirect()->route("admin.appointments.{$segment}", $request->query());
    }

    /** A filter date must look like YYYY-MM-DD (what <input type="date"> sends); anything else is ignored. */
    private function dateFilter(Request $request, string $key): string
    {
        $value = trim((string) $request->query($key, ''));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
    }

    public function index(Request $request, string $type): View
    {
        $config = $this->typeConfig($type);
        $mode = $config['mode'];

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $doctor = (string) $request->query('doctor', '');

        // Date range: From / To calendars. The old single `date` parameter
        // (bookmarks, links from other pages) still works as a one-day range.
        $dateFrom = $this->dateFilter($request, 'date_from');
        $dateTo = $this->dateFilter($request, 'date_to');
        $singleDate = $this->dateFilter($request, 'date');

        if ($singleDate !== '' && $dateFrom === '' && $dateTo === '') {
            $dateFrom = $singleDate;
            $dateTo = $singleDate;
        }

        // A reversed range would match nothing, so put the dates in order.
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = Appointment::query()
            ->where('mode', $mode)
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
            ->when($dateFrom !== '', fn (Builder $builder): Builder => $builder->whereDate('date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $builder): Builder => $builder->whereDate('date', '<=', $dateTo))
            ->orderByDesc('date')
            ->orderByDesc('time_slot');

        $appointments = $query->paginate(20)->appends([
            'search' => $search,
            'status' => $status,
            'doctor' => $doctor,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        $providerModal = null;
        $appointmentDetail = null;

        if ($request->has('view')) {
            $providerModal = 'view';
            $appointmentDetail = Appointment::where('mode', $mode)
                ->with(['patient', 'staff', 'service', 'serviceTele'])
                ->findOrFail($request->integer('view'));
        }

        // Read-only status counts for this type (one grouped query).
        $statusCounts = Appointment::query()
            ->where('mode', $mode)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($count, $statusName) => [strtolower((string) $statusName) => (int) $count]);

        $countFor = fn (array $statuses): int => (int) collect($statuses)
            ->sum(fn ($statusName) => $statusCounts->get($statusName, 0));

        return view('admin.appointments', [
            'config' => $config,
            'serviceType' => $type,
            'mode' => $mode,
            'appointments' => $appointments,
            'appointmentStats' => [
                'total' => Appointment::where('mode', $mode)->count(),
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
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
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

    /** Non-AJAX redirects go back to the page of the appointment's type. */
    private function respond(Request $request, string $message, int $status = 200, ?string $mode = null): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        $route = $mode === 'TELE' ? 'admin.appointments.telemedicine' : 'admin.appointments.face-to-face';

        return redirect()
            ->route($route)
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

    public function store(Request $request, ?string $type = null): RedirectResponse|JsonResponse
    {
        // On a type page the mode is forced by the page, never trusted from the form.
        if ($type !== null) {
            $request->merge(['mode' => $this->typeConfig($type)['mode']]);
        }

        $validated = $request->validate($this->rules($request));
        $validated['status'] = 'Pending';

        Appointment::create($validated);

        return $this->respond($request, 'Appointment created successfully.', 201, $validated['mode']);
    }

    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        if (! in_array($appointment->status, self::EDITABLE, true)) {
            return $this->respond($request, "Cannot edit an appointment with status '{$appointment->status}'.", 422, $appointment->mode);
        }

        // An appointment keeps its type; it cannot move between Face to Face and Telemedicine.
        $request->merge(['mode' => $appointment->mode]);

        $appointment->update($request->validate($this->rules($request)));

        return $this->respond($request, 'Appointment updated successfully.', 200, $appointment->mode);
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        if (! in_array($appointment->status, self::DELETABLE, true)) {
            return $this->respond($request, "Cannot delete an appointment with status '{$appointment->status}'.", 422, $appointment->mode);
        }

        $appointment->delete();

        return $this->respond($request, 'Appointment deleted successfully.', 200, $appointment->mode);
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
            return $this->respond($request, 'Invalid action.', 422, $appointment->mode);
        }

        [$fromStatuses, $toStatus] = $transitions[$action];

        if (! in_array($appointment->status, $fromStatuses, true)) {
            return $this->respond($request, "Cannot {$action} an appointment with status '{$appointment->status}'.", 422, $appointment->mode);
        }

        $appointment->update(['status' => $toStatus]);

        return $this->respond($request, "Appointment marked as {$toStatus}.", 200, $appointment->mode);
    }
}