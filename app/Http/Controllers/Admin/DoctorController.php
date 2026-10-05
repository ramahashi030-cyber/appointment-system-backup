<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DoctorRequest;
use App\Models\Appointment;
use App\Models\Staff;
use App\Support\StaffDoctorSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(Request $request): View
    {
        StaffDoctorSchema::migrateDoctorsIntoStaff();

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = Staff::query()
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('FirstName', 'like', "%{$search}%")
                        ->orWhere('MiddleName', 'like', "%{$search}%")
                        ->orWhere('LastName', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn (Builder $builder): Builder => $builder->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $builder): Builder => $builder->where('is_active', false))
            ->orderBy('LastName')
            ->orderBy('FirstName');

        $doctors = $query->paginate(20)->appends([
            'search' => $search,
            'status' => $status,
        ]);

        $providerModal = null;
        $provider = null;
        $appointments = null;
        $history = null;
        $unassignedAppointments = null;

        if ($request->has('view')) {
            $providerModal = 'view';
            $provider = Staff::findOrFail($request->integer('view'));
            $appointments = $this->appointmentQuery($provider)->paginate(10);
            $history = $this->historyQuery($provider)->paginate(10);
            $unassignedAppointments = Appointment::query()
                ->whereNull('staff_id')
                ->with(['patient', 'service', 'serviceTele'])
                ->orderByDesc('date')
                ->limit(50)
                ->get();
        } elseif ($request->has('edit')) {
            $providerModal = 'edit';
            $provider = Staff::findOrFail($request->integer('edit'));
        }

        return view('admin.doctors-and-staff', [
            'doctors' => $doctors,
            'doctorStats' => [
                'total' => Staff::count(),
                'active' => Staff::where('is_active', true)->count(),
                'scheduled' => Staff::whereNotNull('availability')->count(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'providerModal' => $providerModal,
            'provider' => $provider,
            'appointments' => $appointments,
            'history' => $history,
            'unassignedAppointments' => $unassignedAppointments,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.doctors', [
            'create' => 1,
        ]);
    }

    public function store(DoctorRequest $request): RedirectResponse
    {
        $data = array_merge($this->doctorData($request), [
            'is_verified' => true,
        ]);

        // New providers are always recorded in capital letters so the roster,
        // search results and exports all read the same way.
        foreach (['FirstName', 'MiddleName', 'LastName'] as $nameField) {
            if (isset($data[$nameField])) {
                $data[$nameField] = mb_strtoupper((string) $data[$nameField]);
            }
        }

        Staff::create($data);

        return redirect()
            ->route('admin.doctors')
            ->with('success', 'Doctor added successfully.');
    }

    public function show(Staff $staff): RedirectResponse
    {
        return redirect()->route('admin.doctors', [
            'view' => $staff->id,
        ]);
    }

    public function edit(Staff $staff): RedirectResponse
    {
        return redirect()->route('admin.doctors', [
            'edit' => $staff->id,
        ]);
    }

    public function update(DoctorRequest $request, Staff $staff): RedirectResponse
    {
        $staff->update($this->doctorData($request, $staff));

        return redirect()
            ->route('admin.doctors', ['view' => $staff->id])
            ->with('success', 'Doctor information updated successfully.');
    }

    public function toggleStatus(Staff $staff): RedirectResponse
    {
        $staff->update(['is_active' => ! $staff->is_active]);

        return redirect()
            ->route('admin.doctors')
            ->with('success', $staff->is_active ? 'Doctor activated successfully.' : 'Doctor deactivated successfully.');
    }

    public function assignAppointment(Request $request, Staff $staff): RedirectResponse
    {
        if (! $staff->is_active) {
            return back()->withErrors([
                'appointment_id' => 'Only active providers can receive new appointments.',
            ]);
        }

        $validated = $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
        ]);
        $appointment = Appointment::findOrFail($validated['appointment_id']);

        if ($appointment->staff_id !== null && (int) $appointment->staff_id !== $staff->id) {
            return back()->withErrors([
                'appointment_id' => 'That appointment is already assigned to another provider.',
            ]);
        }

        $appointment->update(['staff_id' => $staff->id]);

        return redirect()
            ->route('admin.doctors', ['view' => $staff->id])
            ->with('success', 'Appointment assigned to the provider.');
    }

    public function appointments(Staff $staff): View
    {
        return view('admin.doctors.appointments', [
            'doctor' => $staff,
            'appointments' => $this->appointmentQuery($staff)->paginate(20),
            'history' => null,
            'pageTitle' => 'Doctor appointments',
        ]);
    }

    public function history(Staff $staff): View
    {
        return view('admin.doctors.appointments', [
            'doctor' => $staff,
            'appointments' => $this->historyQuery($staff)->paginate(20),
            'history' => true,
            'pageTitle' => 'Consultation history',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function doctorData(DoctorRequest $request, ?Staff $staff = null): array
    {
        $validated = $request->validated();
        $data = [
            'FirstName' => $validated['firstname'],
            'MiddleName' => $validated['middlename'] ?? null,
            'LastName' => $validated['lastname'],
            'username' => $validated['username'],
            'employee_id' => $validated['employee_id'] ?? null,
            'legacy_doctor_id' => $validated['legacy_doctor_id'] ?? null,
            'email' => $validated['email'],
            'contactno' => $validated['contactno'] ?? null,
            'consultation_type' => $validated['consultation_type'] ?? null,
            'site' => $validated['site'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? $staff?->is_active ?? true),
            'is_verified' => true,
            'is_doctor' => true,
            'availability' => [
                'days' => $validated['availability_days'] ?? [],
                'start' => $validated['shift_start'] ?? null,
                'end' => $validated['shift_end'] ?? null,
            ],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        return $data;
    }

    private function appointmentQuery(Staff $staff): Builder
    {
        return Appointment::query()
            ->where('staff_id', $staff->id)
            ->with(['patient', 'service', 'serviceTele'])
            ->orderByDesc('date')
            ->orderByDesc('time_slot');
    }

    private function historyQuery(Staff $staff): Builder
    {
        return Appointment::query()
            ->where('staff_id', $staff->id)
            ->where('status', 'Completed')
            ->with(['patient', 'service', 'serviceTele'])
            ->orderByDesc('date')
            ->orderByDesc('time_slot');
    }
}
