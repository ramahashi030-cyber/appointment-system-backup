<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Support\Homis;
use App\Support\Telemed;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Patient medical records (port of QALINGA1/medical_records.php, which was
 * only a read-only table, plus the "Records" nav item that pointed at the
 * never-created records.php).
 *
 * The Blade view drives everything through jQuery $.ajax, so the write
 * endpoints answer JSON while index() stays a normal page render.
 */
class RecordsController extends Controller
{
    /** Directory under public/ that uploaded record files live in. */
    private const UPLOAD_DIR = 'uploads/medical-records';

    /**
     * GET /records — the records table for the signed-in patient.
     */
    public function index(): View|RedirectResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return redirect()->route('auth.login');
        }

        $homisStatus = Homis::status();

        return view('patients.records', [
            'patientName' => Telemed::patientFullName($patient),
            'records' => $this->recordsFor($patient->id)->get(),
            'homisHistory' => $homisStatus['available'] && $patient->hospital_number
                ? Homis::visitHistory($patient->hospital_number)
                : [],
            'homisStatus' => $homisStatus,
            'hasHospitalNumber' => filled(trim((string) $patient->hospital_number)),
            'canEdit' => true,
        ]);
    }

    /**
     * POST /records — add a record.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function store(Request $request)
    {
        $patientId = Telemed::currentPatientId();

        if ($patientId === null) {
            return $this->rejectGuest($request);
        }

        $validated = $request->validate($this->rules());

        $record = MedicalRecord::create([
            'patient_id' => $patientId,
            'record_type' => $validated['record_type'],
            'description' => $validated['description'] ?? null,
            'file_path' => $this->storeUpload($request),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Medical record added.',
            'record' => $this->present($record),
        ], 201);
    }

    /**
     * PUT /records/{id} — edit one of the patient's own records.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function update(Request $request, int $id)
    {
        $patientId = Telemed::currentPatientId();

        if ($patientId === null) {
            return $this->rejectGuest($request);
        }

        $record = $this->ownedRecord($patientId, $id);

        $validated = $request->validate($this->rules());

        $record->record_type = $validated['record_type'];
        $record->description = $validated['description'] ?? null;

        $replacement = $this->storeUpload($request);

        if ($replacement !== null) {
            $this->deleteFile($record->file_path);
            $record->file_path = $replacement;
        }

        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Medical record updated.',
            'record' => $this->present($record->refresh()),
        ]);
    }

    /**
     * DELETE /records/{id} — remove the row and its uploaded file.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(Request $request, int $id)
    {
        $patientId = Telemed::currentPatientId();

        if ($patientId === null) {
            return $this->rejectGuest($request);
        }

        $record = $this->ownedRecord($patientId, $id);

        $this->deleteFile($record->file_path);
        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medical record deleted.',
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'record_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
            ],
        ];
    }

    /**
     * Nobody in the session: JSON callers get 401, browsers go to the login.
     *
     * @return JsonResponse|RedirectResponse
     */
    private function rejectGuest(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Your session has expired. Please sign in again.',
            ], 401);
        }

        return redirect()->route('auth.login');
    }

    /**
     * 404s when the record belongs to another patient — patients only ever
     * reach their own rows.
     */
    private function ownedRecord(int $patientId, int $id): MedicalRecord
    {
        return MedicalRecord::query()
            ->where('patient_id', $patientId)
            ->findOrFail($id);
    }

    /**
     * @return Builder
     */
    private function recordsFor(int $patientId)
    {
        return MedicalRecord::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * Saves the optional upload and returns its public-relative path, or null
     * when no new file came in. Legacy rows keep whatever path they already
     * have (QALINGA1 wrote those into its own uploads/ folder).
     */
    private function storeUpload(Request $request): ?string
    {
        if (! $request->hasFile('file') || ! $request->file('file')->isValid()) {
            return null;
        }

        $file = $request->file('file');

        $name = Str::lower(Str::random(10)).'.'.$file->getClientOriginalExtension();

        $file->move(public_path(self::UPLOAD_DIR), $name);

        return self::UPLOAD_DIR.'/'.$name;
    }

    private function deleteFile(?string $path): void
    {
        if ($path === null || $path === '' || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        File::delete(public_path($path));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(MedicalRecord $record): array
    {
        $path = (string) $record->file_path;

        return [
            'id' => $record->id,
            'record_type' => $record->record_type,
            'description' => $record->description,
            'file_path' => $path,
            'file_url' => $path === ''
                ? null
                : (Str::startsWith($path, ['http://', 'https://']) ? $path : asset($path)),
            'file_name' => $path === '' ? null : basename($path),
            'created_at' => $record->created_at?->format('M d, Y h:i A'),
        ];
    }
}
