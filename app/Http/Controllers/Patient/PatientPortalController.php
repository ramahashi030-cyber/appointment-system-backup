<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PatientNotification;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Support\Homis;
use App\Support\Telemed;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The four patient nav destinations that are not the telemedicine hub:
 * Notification (upcoming appointments), Prescriptions (local portal rows plus
 * HOMIS medication history), Procedures (HOMIS procedure orders/results), and
 * Profile (port of QALINGA1/profile.php).
 *
 * Every screen follows RecordsController's rule: the signed-in patient comes
 * from Telemed::currentPatient() (session `patient_id`), never from the URL.
 */
class PatientPortalController extends Controller
{
    /** Directory under public/ that profile pictures are saved to. */
    private const PROFILE_PIC_DIR = 'uploads/profile-pics';

    /**
     * GET /patients/data/prescriptions — JSON for the prescriptions modal.
     */
    public function dataPrescriptions(): JsonResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $prescriptions = Schema::hasTable('prescriptions')
            ? Prescription::query()
                ->where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'medicine' => $p->medicine ?? $p->drug_name ?? '—',
                    'dosage' => $p->dosage ?? '—',
                    'frequency' => $p->frequency ?? '—',
                    'prescribed_by' => $p->prescribed_by ?? $p->doctor ?? '—',
                    'date' => $p->created_at?->format('M j, Y') ?? '—',
                ])
            : collect();

        return response()->json(['prescriptions' => $prescriptions]);
    }

    /**
     * GET /patients/data/procedures — JSON for the procedures modal.
     */
    public function dataProcedures(): JsonResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $homisStatus = Homis::status();
        $procedures = $homisStatus['available'] && $patient->hospital_number
            ? Homis::procedures($patient->hospital_number)
            : [];

        return response()->json([
            'procedures' => $procedures,
            'homis_available' => $homisStatus['available'],
            'has_hospital_number' => filled(trim((string) $patient->hospital_number)),
        ]);
    }

    /**
     * GET /patients/data/records — JSON for the medical records modal.
     */
    public function dataRecords(): JsonResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $records = $patient->medicalRecords()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title ?? $r->record_type ?? 'Medical Record',
                'type' => $r->record_type ?? 'General',
                'date' => $r->created_at?->format('M j, Y') ?? '—',
                'summary' => $r->summary ?? $r->notes ?? '',
            ]);

        return response()->json(['records' => $records]);
    }

    /**
     * GET /notifications — upcoming appointments plus the stored notices.
     *
     * @return View|RedirectResponse
     */
    public function notifications()
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return redirect()->route('auth.login');
        }

        $upcoming = $this->upcomingAppointments($patient->id);

        $notices = PatientNotification::query()
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('patients.notifications', [
            'patientName' => Telemed::patientFullName($patient),
            'upcoming' => $upcoming,
            'notices' => $notices,
        ]);
    }

    /**
     * GET /prescriptions — local and HOMIS prescriptions for the patient.
     */
    public function prescriptions(): View|RedirectResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return redirect()->route('auth.login');
        }

        $prescriptions = Schema::hasTable('prescriptions')
            ? Prescription::query()
                ->where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->get()
            : collect();
        $homisStatus = Homis::status();
        $homisPrescriptions = $homisStatus['available'] && $patient->hospital_number
            ? Homis::prescriptions($patient->hospital_number)
            : [];

        return view('patients.prescriptions', [
            'patientName' => Telemed::patientFullName($patient),
            'prescriptions' => $prescriptions,
            'homisPrescriptions' => $homisPrescriptions,
            'homisStatus' => $homisStatus,
            'hasHospitalNumber' => filled(trim((string) $patient->hospital_number)),
        ]);
    }

    /**
     * GET /procedures — HOMIS procedure orders and result availability.
     */
    public function procedures(): View|RedirectResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return redirect()->route('auth.login');
        }

        $homisStatus = Homis::status();

        return view('patients.procedures', [
            'patientName' => Telemed::patientFullName($patient),
            'procedures' => $homisStatus['available'] && $patient->hospital_number
                ? Homis::procedures($patient->hospital_number)
                : [],
            'homisStatus' => $homisStatus,
            'hasHospitalNumber' => filled(trim((string) $patient->hospital_number)),
        ]);
    }

    /**
     * GET /profile — the patient's own details.
     *
     * @return View|RedirectResponse
     */
    public function profile()
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return redirect()->route('auth.login');
        }

        return view('patients.profile', [
            'patientName' => Telemed::patientFullName($patient),
            'patient' => $patient,
        ]);
    }

    /**
     * PUT /profile — save the edits (legacy profile.php's save_profile branch).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return redirect()->route('auth.login');
        }

        // Length limits mirror the legacy MySQL column widths so a long
        // value fails validation instead of a 1406 at update time.
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:Male,Female'],
            'dob' => ['nullable', 'date'],
            'contact_number' => ['required', 'string', 'max:11'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'hospital_number' => ['nullable', 'string', 'max:20'],
            'profile_pic' => ['nullable', 'image', 'max:2048'],
        ]);

        $replacement = $this->storeProfilePic($request);

        if ($replacement !== null) {
            $this->deleteProfilePic($patient->profile_pic);
        }

        $patient->fill(collect($validated)->except('profile_pic')->all());

        if ($replacement !== null) {
            $patient->profile_pic = $replacement;
        }

        $patient->save();

        return redirect()->route('patient.profile')->with(
            'success',
            'Your profile has been updated.'
        );
    }

    /**
     * Booked-or-not appointments from today onward, with the service name
     * resolved against whichever table the mode points at (see the
     * Appointment model: one service_id, two candidate tables).
     *
     * @return array<int, array{date: string, day: string, month: string, time_slot: string, status: string, service_name: string, mode: string, meeting_link: ?string}>
     */
    private function upcomingAppointments(int $patientId): array
    {
        $rows = Appointment::query()
            ->where('patient_id', $patientId)
            ->where('date', '>=', Carbon::today()->toDateString())
            ->where('status', '!=', 'Cancelled')
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $teleNames = ServiceTele::whereIn('id', $rows->where('mode', 'TELE')->pluck('service_id'))->pluck('service_name', 'id');
        $faceNames = Service::whereIn('id', $rows->where('mode', '!=', 'TELE')->pluck('service_id'))->pluck('service_name', 'id');

        return $rows->map(function ($row) use ($teleNames, $faceNames) {
            $names = $row->mode === 'TELE' ? $teleNames : $faceNames;

            return [
                'date' => $row->date->format('Y-m-d'),
                'day' => $row->date->format('d'),
                'month' => $row->date->format('M'),
                'time_slot' => (string) $row->time_slot,
                'status' => (string) $row->status,
                'service_name' => (string) ($names[$row->service_id] ?? 'Consultation'),
                'mode' => (string) $row->mode,
                'meeting_link' => $row->meeting_link,
            ];
        })->all();
    }

    /**
     * Saves the optional upload and returns its public-relative path, or null
     * when no new picture came in.
     */
    private function storeProfilePic(Request $request): ?string
    {
        if (! $request->hasFile('profile_pic') || ! $request->file('profile_pic')->isValid()) {
            return null;
        }

        $file = $request->file('profile_pic');

        $name = Str::lower(Str::random(10)).'.'.$file->getClientOriginalExtension();

        $file->move(public_path(self::PROFILE_PIC_DIR), $name);

        return self::PROFILE_PIC_DIR.'/'.$name;
    }

    private function deleteProfilePic(?string $path): void
    {
        if ($path === null || $path === '' || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        File::delete(public_path($path));
    }
}
