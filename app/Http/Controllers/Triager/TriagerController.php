<?php

namespace App\Http\Controllers\Triager;

use App\ConsultationReason;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\ServiceTimeslot;
use App\Models\ServiceTimeslotTele;
use App\Models\Staff;
use App\Models\UnavailableTimeslot;
use App\Models\UnavailableTimeslotTele;
use App\Support\AppointmentJitsiRoom;
use App\Support\AppointmentQrCode;
use App\Support\Telemed;
use App\Support\TriageAuthorization;
use App\Symptom;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Staff-facing triage dashboard.
 *
 * Patients submit appointment requests (face-to-face or telemedicine)
 * without picking a date or time. Triagers review pending requests, add
 * remarks/actions, and attach the actual consultation schedule. The
 * patient then receives a QR code (face-to-face) or meeting link (telemed).
 */
class TriagerController extends Controller
{
    /**
     * GET /triager — pending requests for both consultation modes.
     */
    public function dashboard(): View
    {
        /** @var Admin|null $admin */
        $admin = auth('admin')->user();
        $channel = TriageAuthorization::triageChannel($admin);

        $faceRequests = in_array($channel, [TriageAuthorization::CHANNEL_FACE, TriageAuthorization::CHANNEL_BOTH], true)
            ? $this->pendingRequests('FACE')
            : [];
        $teleRequests = in_array($channel, [TriageAuthorization::CHANNEL_TELE, TriageAuthorization::CHANNEL_BOTH], true)
            ? $this->pendingRequests('TELE')
            : [];
        $processedToday = $this->processedToday();

        return view('admin.triager-dashboard', [
            'faceRequests' => $faceRequests,
            'teleRequests' => $teleRequests,
            'processedToday' => $processedToday,
            'faceServices' => Service::orderBy('service_name')->get(),
            'teleServices' => ServiceTele::orderBy('service_name')->get(),
            'triageActions' => Appointment::TRIAGE_ACTIONS,
            'triageChannel' => $channel,
        ]);
    }

    /**
     * POST /triager/requests/{appointment}/start — begin processing a request.
     */
    public function startProcessing(Appointment $appointment): RedirectResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('admin')->user();

        if (! TriageAuthorization::canViewMode($admin, (string) $appointment->request_mode)) {
            abort(403, 'You are not allowed to process this request.');
        }

        if (! TriageAuthorization::canStartProcessing($appointment, $admin)) {
            if (TriageAuthorization::isLockedByOther($appointment, $admin?->id)) {
                return TriageAuthorization::deny('Another triager is already processing this request.');
            }

            return TriageAuthorization::deny('This request cannot be claimed right now.');
        }

        $updated = Appointment::query()
            ->whereKey($appointment->id)
            ->where('triager_status', 'Pending')
            ->whereNull('processed_by')
            ->update([
                'triager_status' => 'Processing',
                'processed_by' => $admin?->id,
            ]);

        if ($updated === 0) {
            return TriageAuthorization::deny('Another triager claimed this request first.');
        }

        return back()->with('success', 'You are now processing this request.');
    }

    /**
     * POST /triager/requests/{appointment}/update — save action and remarks.
     */
    public function updateRequest(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('admin')->user();

        if (! TriageAuthorization::canUpdateRequest($appointment, $admin)) {
            abort(403, 'You are not allowed to update this request.');
        }

        $validated = $request->validate([
            'triager_action' => ['required', 'string', 'in:'.implode(',', Appointment::TRIAGE_ACTIONS)],
            'triager_remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $triagerStatus = $validated['triager_action'] === 'Approved'
            ? 'Approved'
            : 'In Progress';

        $updates = [
            'triager_action' => $validated['triager_action'],
            'triager_remarks' => $validated['triager_remarks'] ?? null,
            'triager_status' => $triagerStatus,
            'processed_by' => $admin?->id,
            'processed_at' => now(),
        ];

        if ($validated['triager_action'] === 'Approved') {
            $updates['status'] = 'Approved';
        }

        $appointment->update($updates);

        return back()->with('success', 'Request updated.');
    }

    /**
     * POST /triager/requests/{appointment}/schedule/telemed — attach a telemed schedule.
     */
    public function scheduleTelemed(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('admin')->user();

        if (! TriageAuthorization::canSchedule($appointment, $admin, 'TELE')) {
            abort(403, 'You cannot schedule this request yet.');
        }

        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services_tele,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time_slot' => ['required', 'string', 'max:50'],
        ]);

        $service = ServiceTele::findOrFail($validated['service_id']);
        $date = Carbon::parse($validated['date']);

        if (! in_array($date->format('l'), Telemed::codesToFull($service->availability_day), true)) {
            return back()->with('error', $service->service_name.' is not available on '.$date->format('l').'.');
        }

        $slot = ServiceTimeslotTele::query()
            ->where('service_id', $service->id)
            ->where('time_slot', $validated['time_slot'])
            ->first();

        if ($slot === null) {
            return back()->with('error', 'That time slot is not offered for this service.');
        }

        $blocked = UnavailableTimeslotTele::query()
            ->where('service_id', $service->id)
            ->where('date', $validated['date'])
            ->where('time_slot', $validated['time_slot'])
            ->exists();

        if ($blocked) {
            return back()->with('error', 'That time slot is unavailable.');
        }

        $booked = Appointment::query()
            ->where('service_id', $service->id)
            ->where('date', $validated['date'])
            ->where('time_slot', $validated['time_slot'])
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->where('mode', 'TELE')
            ->whereKeyNot($appointment->id)
            ->count();

        if ($booked >= (int) $slot->slots) {
            return back()->with('error', 'That slot is already full.');
        }

        $patient = $appointment->patient;

        DB::transaction(function () use ($appointment, $patient, $service, $date, $validated, $admin): void {
            $meetingLink = app(AppointmentJitsiRoom::class)->buildMeetingLink($patient, $date);

            $appointment->update([
                'service_id' => $service->id,
                'date' => $date->toDateString(),
                'time_slot' => $validated['time_slot'],
                'status' => 'Booked',
                'mode' => 'TELE',
                'request_mode' => 'TELE',
                'meeting_link' => $meetingLink,
                'room_opened' => false,
                'opened_by' => null,
                'triager_status' => 'Completed',
                'processed_by' => $admin?->id,
                'processed_at' => now(),
            ]);

            $patient->notifications()->create([
                'message' => 'Your telemedicine consultation has been scheduled for '.$date->format('M j, Y').' at '.$validated['time_slot'].'. You or your doctor may create the Jitsi room when it is time to consult.',
                'is_read' => false,
            ]);
        });

        return back()->with('success', 'Telemedicine schedule added. The patient and doctor can create the Jitsi room when ready.');
    }

    /**
     * POST /triager/requests/{appointment}/schedule/face — attach a face-to-face schedule.
     */
    public function scheduleFace(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('admin')->user();

        if (! TriageAuthorization::canSchedule($appointment, $admin, 'FACE')) {
            abort(403, 'You cannot schedule this request yet.');
        }

        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time_slot' => ['required', 'string', 'max:50'],
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $date = Carbon::parse($validated['date']);

        if (! in_array($date->format('l'), Telemed::codesToFull($service->availability_day), true)) {
            return back()->with('error', $service->service_name.' is not available on '.$date->format('l').'.');
        }

        $slot = ServiceTimeslot::query()
            ->where('service_id', $service->id)
            ->where('time_slot', $validated['time_slot'])
            ->first();

        if ($slot === null) {
            return back()->with('error', 'That time slot is not offered for this service.');
        }

        $blocked = UnavailableTimeslot::query()
            ->where('service_id', $service->id)
            ->where('date', $validated['date'])
            ->where('time_slot', $validated['time_slot'])
            ->exists();

        if ($blocked) {
            return back()->with('error', 'That time slot is unavailable.');
        }

        $booked = Appointment::query()
            ->where('service_id', $service->id)
            ->where('date', $validated['date'])
            ->where('time_slot', $validated['time_slot'])
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->where('mode', 'FACE')
            ->whereKeyNot($appointment->id)
            ->count();

        if ($booked >= (int) $slot->slots) {
            return back()->with('error', 'That slot is already full.');
        }

        $patient = $appointment->patient;
        $qrCode = app(AppointmentQrCode::class);

        DB::transaction(function () use ($appointment, $patient, $service, $date, $validated, $admin, $qrCode): void {
            $appointment->update([
                'service_id' => $service->id,
                'date' => $date->toDateString(),
                'time_slot' => $validated['time_slot'],
                'status' => 'Booked',
                'mode' => 'FACE',
                'request_mode' => 'FACE',
                'triager_status' => 'Completed',
                'processed_by' => $admin?->id,
                'processed_at' => now(),
            ]);

            $qrCode->ensureToken($appointment->fresh());
            $appointment->forceFill([
                'qr_code_path' => route('telemed.appointment.qr', $appointment, false),
            ])->save();

            $patient->notifications()->create([
                'message' => 'Your face-to-face consultation has been scheduled for '.$date->format('M j, Y').' at '.$validated['time_slot'].'. Please show your QR code at the kiosk when you arrive.',
                'is_read' => false,
            ]);
        });

        return back()->with('success', 'Face-to-face schedule added. The patient can now view the QR code.');
    }

    /**
     * GET /triager/processed/print — printable list of today's processed requests.
     */
    public function printProcessed(): View
    {
        return view('admin.triager-processed', [
            'processedToday' => $this->processedToday(),
            'printedAt' => now(),
        ]);
    }

    /**
     * Pending triage requests for one consultation mode.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pendingRequests(string $mode): array
    {
        return Appointment::query()
            ->where('request_mode', $mode)
            ->whereNull('date')
            ->whereIn('triager_status', ['Pending', 'Processing', 'In Progress', 'Approved'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Appointment $appointment): array => $this->presentRequest($appointment))
            ->all();
    }

    /**
     * Requests processed today.
     *
     * @return array<int, array<string, mixed>>
     */
    private function processedToday(): array
    {
        return Appointment::query()
            ->where('triager_status', 'Completed')
            ->whereDate('processed_at', Carbon::today())
            ->orderByDesc('processed_at')
            ->get()
            ->map(fn (Appointment $appointment): array => $this->presentRequest($appointment))
            ->all();
    }

    /**
     * Shape a triage request for the dashboard cards.
     *
     * @return array<string, mixed>
     */
    private function presentRequest(Appointment $appointment): array
    {
        $patient = $appointment->patient;
        $symptoms = is_array($appointment->symptoms) ? $appointment->symptoms : [];
        $symptomLabels = array_values(array_filter(array_map(
            fn (mixed $symptom): ?string => is_string($symptom)
                ? Symptom::tryFrom($symptom)?->label()
                : null,
            $symptoms,
        )));

        $reasonLabel = $appointment->consultation_reason !== null
            ? ConsultationReason::tryFrom($appointment->consultation_reason)?->label()
            : null;

        $currentAdminId = auth('admin')->id();
        $isProcessedByOther = TriageAuthorization::isLockedByOther($appointment, $currentAdminId);
        $isOwner = TriageAuthorization::ownsRequest($appointment, $currentAdminId);
        $canEdit = TriageAuthorization::canUpdateRequest($appointment, auth('admin')->user());
        $canSchedule = $appointment->request_mode === 'FACE'
            ? TriageAuthorization::canSchedule($appointment, auth('admin')->user(), 'FACE')
            : TriageAuthorization::canSchedule($appointment, auth('admin')->user(), 'TELE');

        return [
            'id' => $appointment->id,
            'patient_name' => $this->patientName($patient),
            'hospital_number' => $patient?->hospital_number ?: '—',
            'initials' => $this->initials($this->patientName($patient)),
            'symptoms' => $symptomLabels,
            'symptoms_text' => implode(' | ', $symptomLabels),
            'consultation_reason' => $appointment->consultation_reason,
            'consultation_reason_label' => $reasonLabel,
            'complaint_details' => $appointment->complaint_details,
            'requested_at' => $appointment->created_at,
            'triager_status' => $appointment->triager_status,
            'triager_action' => $appointment->triager_action,
            'triager_remarks' => $appointment->triager_remarks,
            'processed_by' => $appointment->processed_by,
            'is_processed_by_other' => $isProcessedByOther,
            'is_owner' => $isOwner,
            'can_edit' => $canEdit,
            'can_schedule' => $canSchedule,
            'request_mode' => $appointment->request_mode,
            'existing_appointments' => $this->existingAppointments($appointment->patient_id),
        ];
    }

    /**
     * The patient's scheduled appointments shown inside each request card.
     *
     * @return array<int, array<string, mixed>>
     */
    private function existingAppointments(?int $patientId): array
    {
        if (! $patientId) {
            return [];
        }

        return Appointment::query()
            ->where('patient_id', $patientId)
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->whereNotNull('date')
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get()
            ->map(fn (Appointment $row): array => [
                'date' => $row->date?->format('Y-m-d'),
                'time_slot' => $row->time_slot,
                'mode' => $row->mode,
                'consultation' => $row->consultation_reason
                    ? (ConsultationReason::tryFrom($row->consultation_reason)?->label() ?? 'Consultation')
                    : 'Consultation',
            ])
            ->all();
    }

    /**
     * GET /triager/timeslots/telemed?service_id=&date= — slots for the schedule modal.
     */
    public function telemedTimeslots(Request $request): JsonResponse
    {
        $serviceId = (int) $request->query('service_id', 0);
        $date = (string) $request->query('date', '');

        if (! $serviceId || ! Carbon::hasFormat($date, 'Y-m-d')) {
            return response()->json(['error' => 'Invalid request'], 422);
        }

        $unavailable = UnavailableTimeslotTele::query()
            ->where('service_id', $serviceId)
            ->where('date', $date)
            ->get()
            ->mapWithKeys(fn ($row) => [$row->time_slot => $row->reason ?: 'Unavailable']);

        $booked = Appointment::query()
            ->where('service_id', $serviceId)
            ->where('date', $date)
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->where('mode', 'TELE')
            ->get(['time_slot'])
            ->groupBy('time_slot')
            ->map(fn ($rows) => $rows->count());

        $slots = ServiceTimeslotTele::query()
            ->where('service_id', $serviceId)
            ->orderBy('time_slot')
            ->get()
            ->map(function ($slot) use ($unavailable, $booked) {
                if ($unavailable->has($slot->time_slot)) {
                    return [
                        'time_slot' => $slot->time_slot,
                        'remaining' => 0,
                        'blocked' => true,
                        'reason' => $unavailable[$slot->time_slot],
                    ];
                }

                return [
                    'time_slot' => $slot->time_slot,
                    'remaining' => max(0, (int) $slot->slots - (int) $booked->get($slot->time_slot, 0)),
                    'blocked' => false,
                ];
            })
            ->values();

        return response()->json($slots);
    }

    /**
     * GET /triager/timeslots/face?service_id=&date= — slots for the schedule modal.
     */
    public function faceTimeslots(Request $request): JsonResponse
    {
        $serviceId = (int) $request->query('service_id', 0);
        $date = (string) $request->query('date', '');

        if (! $serviceId || ! Carbon::hasFormat($date, 'Y-m-d')) {
            return response()->json(['error' => 'Invalid request'], 422);
        }

        $unavailable = UnavailableTimeslot::query()
            ->where('service_id', $serviceId)
            ->where('date', $date)
            ->get()
            ->mapWithKeys(fn ($row) => [$row->time_slot => $row->reason ?: 'Unavailable']);

        $booked = Appointment::query()
            ->where('service_id', $serviceId)
            ->where('date', $date)
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->where('mode', 'FACE')
            ->get(['time_slot'])
            ->groupBy('time_slot')
            ->map(fn ($rows) => $rows->count());

        $slots = ServiceTimeslot::query()
            ->where('service_id', $serviceId)
            ->orderBy('time_slot')
            ->get()
            ->map(function ($slot) use ($unavailable, $booked) {
                if ($unavailable->has($slot->time_slot)) {
                    return [
                        'time_slot' => $slot->time_slot,
                        'remaining' => 0,
                        'blocked' => true,
                        'reason' => $unavailable[$slot->time_slot],
                    ];
                }

                return [
                    'time_slot' => $slot->time_slot,
                    'remaining' => max(0, (int) $slot->slots - (int) $booked->get($slot->time_slot, 0)),
                    'blocked' => false,
                ];
            })
            ->values();

        return response()->json($slots);
    }

    private function patientName(?Patient $patient): string
    {
        if ($patient === null) {
            return 'Unknown patient';
        }

        return trim(implode(' ', array_filter([
            $patient->first_name,
            $patient->middlename,
            $patient->last_name,
        ])));
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials ?: '?';
    }
}
