<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecordController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $patient = (string) $request->query('patient', '');

        $query = MedicalRecord::query()
            ->with('patient')
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('description', 'like', "%{$search}%")
                        ->orWhere('record_type', 'like', "%{$search}%")
                        ->orWhereHas('patient', function (Builder $patientQuery) use ($search): void {
                            $patientQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($type !== '', fn (Builder $builder): Builder => $builder->where('record_type', $type))
            ->when($patient !== '', fn (Builder $builder): Builder => $builder->where('patient_id', $patient))
            ->orderByDesc('created_at');

        $records = $query->paginate(20)->appends([
            'search' => $search,
            'type' => $type,
            'patient' => $patient,
        ]);

        $recordModal = null;
        $recordDetail = null;

        if ($request->has('view')) {
            $recordModal = 'view';
            $recordDetail = MedicalRecord::with('patient')->findOrFail($request->integer('view'));
        }

        return view('admin.records', [
            'records' => $records,
            'recordStats' => [
                'total' => MedicalRecord::count(),
                'lab' => MedicalRecord::where('record_type', 'lab_result')->count(),
                'prescriptions' => MedicalRecord::where('record_type', 'prescription')->count(),
            ],
            'filters' => [
                'search' => $search,
                'type' => $type,
                'patient' => $patient,
            ],
            'patients' => Patient::orderBy('last_name')->orderBy('first_name')->get(),
            'recordModal' => $recordModal,
            'recordDetail' => $recordDetail,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'record_type' => ['required', 'in:diagnosis,lab_result,prescription,treatment,consultation,document'],
            'description' => ['required', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('medical-records', 'public');
        }

        MedicalRecord::create([
            'patient_id' => $validated['patient_id'],
            'record_type' => $validated['record_type'],
            'description' => $validated['description'],
            'file_path' => $filePath,
        ]);

        return redirect()
            ->route('admin.records')
            ->with('success', 'Record created successfully.');
    }

    public function destroy(MedicalRecord $record): RedirectResponse
    {
        $record->delete();

        return redirect()
            ->route('admin.records')
            ->with('success', 'Record deleted successfully.');
    }
}
