<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PatientRequest;
use App\Models\Patient;
use App\Models\Prescription;
use App\Support\Homis;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * GET /admin/patients — searchable, filterable patient roster.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $gender = (string) $request->query('gender', '');

        $query = Patient::query()
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('hospital_number', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn (Builder $builder): Builder => $builder->where('status', $status))
            ->when($gender !== '', fn (Builder $builder): Builder => $builder->where('gender', $gender))
            ->orderBy('last_name')
            ->orderBy('first_name');

        $patients = $query->paginate(20)->appends([
            'search' => $search,
            'status' => $status,
            'gender' => $gender,
        ]);

        $patientModal = null;
        $patient = null;
        $appointments = null;
        $history = null;
        $medicalRecords = null;

        if ($request->has('view')) {
            $patientModal = 'view';
            $patient = Patient::findOrFail($request->integer('view'));
            $appointments = $this->appointmentQuery($patient)->paginate(10);
            $history = $this->historyQuery($patient)->paginate(10);
            $medicalRecords = $patient->medicalRecords()
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        } elseif ($request->has('edit')) {
            $patientModal = 'edit';
            $patient = Patient::findOrFail($request->integer('edit'));
        }

        return view('admin.patients', [
            'patients' => $patients,
            'patientStats' => [
                'total' => Patient::count(),
                'active' => Patient::where('status', 'Active')->count(),
                'pending' => Patient::where('status', 'Pending')->count(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'gender' => $gender,
            ],
            'patientModal' => $patientModal,
            'patient' => $patient,
            'appointments' => $appointments,
            'history' => $history,
            'medicalRecords' => $medicalRecords,
        ]);
    }

    public function show(Patient $patient): RedirectResponse
    {
        return redirect()->route('admin.patients', [
            'view' => $patient->id,
        ]);
    }

    public function edit(Patient $patient): RedirectResponse
    {
        return redirect()->route('admin.patients', [
            'edit' => $patient->id,
        ]);
    }

    /**
     * POST /admin/patients — admin creates a patient account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createPatient', [
            'firstname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique(Patient::class, 'username')],
            'dob' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'contactno' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(Patient::class, 'email')],
            'address' => ['nullable', 'string', 'max:255'],
            'hospital_number' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['Active', 'Pending'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $patient = Patient::create([
            'first_name' => $validated['firstname'],
            'middlename' => $validated['middlename'] ?? null,
            'last_name' => $validated['lastname'],
            'username' => $validated['username'],
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'],
            'contact_number' => $validated['contactno'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'hospital_number' => $validated['hospital_number'] ?? null,
            'status' => $validated['status'],
            'password' => $validated['password'], // hashed by the model cast
        ]);

        return redirect()
            ->route('admin.patients')
            ->with('success', "Patient {$this->patientName($patient)} created successfully.");
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($this->patientData($request, $patient));

        return redirect()
            ->route('admin.patients', ['view' => $patient->id])
            ->with('success', 'Patient information updated successfully.');
    }

    /**
     * PATCH /admin/patients/{patient}/status — activate (status=Active) or
     * deactivate (status=Pending) a patient account.
     * Returns JSON for AJAX requests; keeps the redirect for normal form posts.
     */
    public function toggleStatus(Request $request, Patient $patient): RedirectResponse|JsonResponse
    {
        $patient->update(['status' => $patient->status === 'Active' ? 'Pending' : 'Active']);

        $message = $patient->status === 'Active'
            ? 'Patient account activated successfully.'
            : 'Patient account deactivated successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => $patient->status,
                'stats' => [
                    'total' => number_format(Patient::count()),
                    'active' => number_format(Patient::where('status', 'Active')->count()),
                    'pending' => number_format(Patient::where('status', 'Pending')->count()),
                ],
            ]);
        }

        return redirect()
            ->route('admin.patients')
            ->with('success', $message);
    }

    /**
     * Generate a fresh temporary password for the patient account.
     */
    public function resetPassword(Patient $patient): RedirectResponse
    {
        $temporaryPassword = Str::random(10);

        $patient->forceFill(['password' => $temporaryPassword])->save();

        return redirect()
            ->route('admin.patients', ['view' => $patient->id])
            ->with('success', "Password reset for {$this->patientName($patient)}. Temporary password: {$temporaryPassword}");
    }

    /**
     * GET /admin/patients/{patient}/appointments — full appointment history.
     */
    public function appointments(Patient $patient): View
    {
        return view('admin.patients.appointments', [
            'patient' => $patient,
            'appointments' => $this->appointmentQuery($patient)->paginate(20),
            'history' => null,
            'pageTitle' => 'Patient appointment history',
        ]);
    }

    /**
     * GET /admin/patients/{patient}/history — completed consultations.
     */
    public function history(Patient $patient): View
    {
        return view('admin.patients.appointments', [
            'patient' => $patient,
            'appointments' => $this->historyQuery($patient)->paginate(20),
            'history' => true,
            'pageTitle' => 'Patient consultation history',
        ]);
    }

    /**
     * GET /admin/patients/{patient}/records — medical records and prescriptions.
     */
    public function records(Patient $patient): View
    {
        $hospitalNumber = trim((string) $patient->hospital_number);
        $homisAvailable = Homis::available();

        $homisPrescriptions = ($homisAvailable && $hospitalNumber !== '')
            ? Homis::prescriptions($hospitalNumber)
            : [];

        $homisProcedures = ($homisAvailable && $hospitalNumber !== '')
            ? Homis::procedures($hospitalNumber)
            : [];

        $homisVisits = ($homisAvailable && $hospitalNumber !== '')
            ? Homis::visitHistory($hospitalNumber)
            : [];

        return view('admin.patients.records', [
            'patient' => $patient,
            'medicalRecords' => $patient->medicalRecords()
                ->orderByDesc('created_at')
                ->paginate(20),
            'prescriptions' => Prescription::query()
                ->where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->paginate(20),
            'homisPrescriptions' => $homisPrescriptions,
            'homisProcedures' => $homisProcedures,
            'homisVisits' => $homisVisits,
            'homisAvailable' => $homisAvailable,
            'hasHospitalNumber' => $hospitalNumber !== '',
        ]);
    }

    /**
     * GET /admin/patients/{patient}/visits — completed visits timeline.
     */
    public function visits(Patient $patient): View
    {
        return view('admin.patients.visits', [
            'patient' => $patient,
            'visits' => $this->historyQuery($patient)->paginate(20),
            'pageTitle' => 'Patient visit history',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function patientData(PatientRequest $request, Patient $patient): array
    {
        $validated = $request->validated();
        $data = [
            'first_name' => $validated['firstname'],
            'middlename' => $validated['middlename'] ?? null,
            'last_name' => $validated['lastname'],
            'username' => $validated['username'],
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'contact_number' => $validated['contactno'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'hospital_number' => $validated['hospital_number'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if (! empty($validated['status']) && $validated['status'] !== $patient->status) {
            $data['status'] = $validated['status'];
        }

        return $data;
    }

    private function appointmentQuery(Patient $patient): HasMany
    {
        return $patient->appointments()
            ->with(['service', 'serviceTele', 'staff'])
            ->orderByDesc('date')
            ->orderByDesc('time_slot');
    }

    private function historyQuery(Patient $patient): HasMany
    {
        return $patient->appointments()
            ->where('status', 'Completed')
            ->with(['service', 'serviceTele', 'staff'])
            ->orderByDesc('date')
            ->orderByDesc('time_slot');
    }

    private function patientName(Patient $patient): string
    {
        return trim(implode(' ', array_filter([
            $patient->first_name,
            $patient->middlename,
            $patient->last_name,
        ])));
    }
}
