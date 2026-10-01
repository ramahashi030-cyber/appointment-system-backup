<?php

namespace App\Http\Controllers\Patient;

use App\ConsultationReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\CancelAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\ServiceTimeslotTele;
use App\Models\UnavailableTimeslotTele;
use App\Support\AppointmentJitsiRoom;
use App\Support\AppointmentQrCode;
use App\Support\AppointmentSchema;
use App\Support\Telemed;
use App\Symptom;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Patient-facing telemedicine screens (mirrors telemed.php / book_tele.php /
 * timeslots_tele.php).
 */
class TelemedController extends Controller
{
    /**
     * GET /telemed — patient hub.
     */
    public function home(): View
    {
        $patient = Telemed::currentPatient();
        $patientId = $patient?->id;

        $activeAppointment = Telemed::activeAppointment($patientId);

        $bookingLimits = $patientId
            ? $this->bookingLimits($patientId)
            : ['face' => false, 'tele' => false];

        $upcoming = $patientId
            ? $this->presentAppointments(
                $this->patientAppointments($patientId)
                    ->where('a.date', '>=', Carbon::today()->toDateString())
                    ->orderBy('a.date')
                    ->orderBy('a.time_slot')
                    ->get()
            )
            : [];

        $services = $this->bookableServices();

        $pendingFaceRequests = $patientId
            ? $this->presentRequests(
                $this->patientRequests($patientId)
                    ->where('a.request_mode', 'FACE')
                    ->orderByDesc('a.created_at')
                    ->get()
            )
            : [];

        $recentNotifications = $patient?->notifications()
            ->latest('created_at')
            ->limit(10)
            ->get() ?? collect();

        return view('patients.home', [
            'patient' => $patient,
            'patientName' => Telemed::patientFullName($patient),
            'activeAppointment' => $activeAppointment,
            'bookingLimits' => $bookingLimits,
            'upcoming' => $upcoming,
            'pendingFaceRequests' => $pendingFaceRequests,
            'services' => $services,
            'consultationReasons' => ConsultationReason::options(),
            'symptoms' => Symptom::options(),
            'unreadNotificationCount' => $patient?->notifications()
                ->where('is_read', false)
                ->count() ?? 0,
            'recentNotifications' => $recentNotifications,
        ]);
    }

    /**
     * Per-mode booking gate: which consultation types the patient may still request.
     *
     * A patient may hold one face-to-face and one telemedicine booking at the
     * same time, so each type is limited on its own. A type counts as taken
     * when the patient already has either a pending request for it or an
     * active appointment of it.
     *
     * @return array{face: bool, tele: bool} true when that type cannot be requested
     */
    private function bookingLimits(int $patientId): array
    {
        $isTaken = static fn (string $mode): bool => Appointment::query()
            ->where('patient_id', $patientId)
            ->where(function (Builder $query) use ($mode): void {
                $query
                    // Active, scheduled appointment of this type.
                    ->where(function (Builder $active) use ($mode): void {
                        $active->where('mode', $mode)
                            ->whereIn('status', Appointment::ACTIVE_STATUSES);
                    })
                    // Request awaiting triage, not yet scheduled — must also be active
                    // (not cancelled) to count as a blocker.
                    ->orWhere(function (Builder $pending) use ($mode): void {
                        $pending->where('request_mode', $mode)
                            ->whereNull('date')
                            ->whereIn('triager_status', ['Pending', 'Processing', 'In Progress', 'Approved'])
                            ->whereIn('status', Appointment::ACTIVE_STATUSES);
                    });
            })
            ->exists();

        return [
            'face' => $isTaken('FACE'),
            'tele' => $isTaken('TELE'),
        ];
    }

    /**
     * Every bookable service, labelled with the consultation type it belongs to.
     *
     * Face-to-face services live in `services`, telemedicine services in
     * `services_tele`. Both are merged into a single, alphabetically ordered
     * list so the patient can see every option with its type attached.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bookableServices(): array
    {
        $tele = ServiceTele::orderBy('service_name')
            ->get()
            ->map(fn (ServiceTele $service) => $this->presentService(
                (int) $service->id,
                (string) $service->service_name,
                $service->availability_day,
                'TELE',
            ));

        $face = Service::orderBy('service_name')
            ->get()
            ->map(fn (Service $service) => $this->presentService(
                (int) $service->id,
                (string) $service->service_name,
                $service->availability_day,
                'FACE',
            ));

        return $tele->concat($face)
            ->sortBy(fn (array $service) => Str::lower($service['service_name']))
            ->values()
            ->all();
    }

    /**
     * Shape a service row for display, with a human label for its mode.
     *
     * @return array<string, mixed>
     */
    private function presentService(int $id, string $name, ?string $availabilityDay, string $mode): array
    {
        return [
            'id' => $id,
            'service_name' => $name,
            'mode' => $mode,
            'mode_label' => $mode === 'FACE' ? 'Face-to-Face' : 'Telemedicine',
            'availability_days' => implode(', ', array_map(
                fn (string $day) => substr($day, 0, 3),
                Telemed::codesToFull($availabilityDay)
            )),
        ];
    }

    /**
     * POST /telemed/consent — record consent before opening the booking form.
     *
     * The "already have an appointment" gate is now per-mode. We only return
     * an active_appointment when the requested consultation type (FACE or TELE)
     * is already taken AND that appointment has a scheduled date (not just a
     * pending request).
     */
    public function consent(Request $request): JsonResponse|RedirectResponse
    {
        $serviceMode = strtoupper((string) $request->input('service_mode', 'TELE')) === 'FACE' ? 'FACE' : 'TELE';

        $validated = $request->validate([
            'service_mode' => ['nullable', Rule::in(['TELE', 'FACE'])],
            'service_id' => ['nullable', 'integer', 'exists:'.($serviceMode === 'FACE' ? 'services' : 'services_tele').',id'],
        ]);

        $patient = Telemed::currentPatient();

        if ($patient === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please sign in before providing consent and booking a visit.',
                ], 401);
            }

            return redirect()->route('auth.login')->with(
                'error',
                'Please sign in before providing consent and booking a visit.'
            );
        }

        $patient->consents()->create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'consented_at' => now(),
            'version' => config('telemed.consent_version', 1),
        ]);

        // Per-mode limits — only the requested mode matters for the "already have appointment" gate
        $bookingLimits = $this->bookingLimits($patient->id);
        $requestedModeTaken = $serviceMode === 'FACE' ? $bookingLimits['face'] : $bookingLimits['tele'];

        // Only return an "active appointment" if it's a SCHEDULED appointment (has date) for the requested mode
        $activeAppointment = null;
        if ($requestedModeTaken) {
            $row = Appointment::query()
                ->where('patient_id', $patient->id)
                ->where('mode', $serviceMode)
                ->whereIn('status', Appointment::ACTIVE_STATUSES)
                ->whereNotNull('date')
                ->orderByDesc('created_at')
                ->first();

            if ($row) {
                $activeAppointment = [
                    'id' => $row->id,
                    'date' => $row->date?->format('Y-m-d'),
                    'time_slot' => $row->time_slot,
                    'status' => $row->status,
                    'meeting_link' => $row->meeting_link,
                    'service_name' => $row->serviceTele?->service_name
                        ?? $row->service?->service_name
                        ?? ConsultationReason::tryFrom($row->consultation_reason)?->label()
                        ?? ($row->mode === 'TELE' ? 'Telemedicine consultation' : 'Face-to-face consultation'),
                ];
            }
        }

        $parameters = isset($validated['service_id']) ? ['service_id' => $validated['service_id']] : [];
        $message = ($bookingLimits['face'] && $bookingLimits['tele'])
            ? 'Your consent has been recorded. You already have a request or appointment for both consultation types.'
            : 'Your consent has been recorded. You may now continue with your booking.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'service_id' => $validated['service_id'] ?? null,
                'active_appointment' => $activeAppointment,
                'booking_limits' => $bookingLimits,
            ], 201);
        }

        return redirect()->route('telemed.book', $parameters)->with('success', $message);
    }

    /**
     * GET /telemed/book — appointment request form (no calendar).
     *
     * Patients answer the consultation question first. Picking a specific
     * consultation type creates a face-to-face pending request immediately;
     * picking "None of the above" reveals the symptom selector and complaint
     * details, which submit as a telemedicine pending request.
     */
    public function showBook(Request $request): View
    {
        $patient = Telemed::currentPatient();
        $patientId = $patient?->id;

        $age = Telemed::age($patient?->dob);
        $active = Telemed::activeAppointment($patientId);

        // Check for active appointments per mode
        $activeFaceAppointment = $patientId
            ? Appointment::query()
                ->where('patient_id', $patientId)
                ->where('mode', 'FACE')
                ->whereIn('status', Appointment::ACTIVE_STATUSES)
                ->orderByDesc('created_at')
                ->first()
            : null;

        $activeTeleAppointment = $patientId
            ? Appointment::query()
                ->where('patient_id', $patientId)
                ->where('mode', 'TELE')
                ->whereIn('status', Appointment::ACTIVE_STATUSES)
                ->orderByDesc('created_at')
                ->first()
            : null;

        $pendingFaceRequest = $patientId
            ? Appointment::query()
                ->where('patient_id', $patientId)
                ->where('request_mode', 'FACE')
                ->whereNull('date')
                ->whereIn('triager_status', ['Pending', 'Processing', 'In Progress', 'Approved'])
                ->orderByDesc('created_at')
                ->first()
            : null;

        $pendingTeleRequest = $patientId
            ? Appointment::query()
                ->where('patient_id', $patientId)
                ->where('request_mode', 'TELE')
                ->whereNull('date')
                ->whereIn('triager_status', ['Pending', 'Processing', 'In Progress', 'Approved'])
                ->orderByDesc('created_at')
                ->first()
            : null;

        return view('patients.book', [
            'patientName' => Telemed::patientFullName($patient),
            'ageDisplay' => $age['display'],
            'ageValue' => $age['value'],
            'gender' => $patient?->gender ?? '',
            'activeAppointment' => $active,
            'isExpired' => (bool) ($active['is_expired'] ?? false),
            'activeFaceAppointment' => $activeFaceAppointment,
            'activeTeleAppointment' => $activeTeleAppointment,
            'pendingFaceRequest' => $pendingFaceRequest,
            'pendingTeleRequest' => $pendingTeleRequest,
            'consultationReasons' => ConsultationReason::options(),
            'symptoms' => Symptom::options(),
        ]);
    }

    /**
     * POST /telemed/book — create a pending appointment request.
     *
     * The patient never picks a date or time. A specific consultation type
     * becomes a face-to-face request; "None of the above" becomes a
     * telemedicine request with symptoms and complaint details.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return $this->bookingError($request, 'No patient session found. Please sign in again.');
        }

        // Repair missing intake columns before inserting. Both request paths write
        // them, so a database that has not been migrated yet would otherwise 500.
        try {
            AppointmentSchema::ensureCompatibleColumns();
        } catch (QueryException $exception) {
            report($exception);
        }

        // The reason decides the consultation type, so it must be present and
        // valid here. Validating up front keeps a missing or null reason from
        // silently falling through to the telemedicine path below.
        $validated = $request->validate([
            'consultation_reason' => ['required', Rule::enum(ConsultationReason::class)],
        ]);

        $reason = $validated['consultation_reason'];

        // Specific consultation type → face-to-face pending request.
        if ($reason !== ConsultationReason::NoneOfTheAbove->value) {
            return $this->storeFaceRequest($request, $patient, $reason);
        }

        // None of the above → telemedicine pending request with symptoms.
        return $this->storeTeleRequest($request, $patient);
    }

    /**
     * Create a face-to-face pending request from a specific consultation type.
     */
    private function storeFaceRequest(Request $request, Patient $patient, string $reason): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'consultation_reason' => ['required', Rule::enum(ConsultationReason::class)],
        ]);

        $reasonEnum = ConsultationReason::from($validated['consultation_reason']);

        if ($reasonEnum === ConsultationReason::NoneOfTheAbove) {
            return $this->bookingError($request, 'Please choose what you need to consult for.');
        }

        // Check for existing pending face-to-face request
        $existingPending = Appointment::query()
            ->where('patient_id', $patient->id)
            ->where('request_mode', 'FACE')
            ->whereNull('date')
            ->whereIn('triager_status', ['Pending', 'Processing', 'In Progress', 'Approved'])
            ->orderByDesc('created_at')
            ->first();

        if ($existingPending !== null) {
            $message = 'You already have a pending face-to-face request. Please wait for the triage team to process it before submitting another.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'existing_request_id' => $existingPending->id,
                    'appointments_url' => route('telemed.mine'),
                ], 422);
            }

            return redirect()->route('telemed.mine')->with('error', $message);
        }

        // Check for active face-to-face appointment
        $activeFace = Appointment::query()
            ->where('patient_id', $patient->id)
            ->where('mode', 'FACE')
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->orderByDesc('created_at')
            ->first();

        if ($activeFace !== null) {
            $message = 'You already have an active face-to-face appointment. Please wait for it to be completed before requesting another.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'appointments_url' => route('telemed.mine'),
                ], 422);
            }

            return redirect()->route('telemed.mine')->with('error', $message);
        }

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'consultation_reason' => $reasonEnum->value,
            'complaint' => Str::limit($reasonEnum->label(), 255),
            'complaint_details' => $reasonEnum->label(),
            'status' => 'Pending',
            'mode' => 'FACE',
            'request_mode' => 'FACE',
            'triager_status' => 'Pending',
        ]);

        $message = 'Your face-to-face consultation request has been submitted. Our triage team will process it shortly.';

        if ($request->expectsJson()) {
            $request->session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'request_id' => $appointment->id,
                'appointments_url' => route('telemed.mine'),
            ], 201);
        }

        return redirect()->route('telemed.mine')->with('success', $message);
    }

    /**
     * Create a telemedicine pending request from symptoms + complaint details.
     */
    private function storeTeleRequest(Request $request, Patient $patient): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'symptoms' => ['required', 'array', 'list', 'min:1', 'max:3'],
            'symptoms.*' => ['required', 'string', 'distinct', Rule::enum(Symptom::class)],
            'complaint_details' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'symptoms.required' => 'Please select at least 1 and maximum of 3 symptoms.',
            'symptoms.min' => 'Please select at least 1 and maximum of 3 symptoms.',
            'symptoms.max' => 'Please select no more than 3 symptoms.',
            'symptoms.*.distinct' => 'Please do not select the same symptom more than once.',
            'complaint_details.required' => 'Please provide details about your complaint.',
        ]);

        // Check for existing pending telemedicine request
        $existingPending = Appointment::query()
            ->where('patient_id', $patient->id)
            ->where('request_mode', 'TELE')
            ->whereNull('date')
            ->whereIn('triager_status', ['Pending', 'Processing', 'In Progress', 'Approved'])
            ->orderByDesc('created_at')
            ->first();

        if ($existingPending !== null) {
            $message = 'You already have a pending telemedicine request. Please wait for the triage team to process it before submitting another.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'existing_request_id' => $existingPending->id,
                    'appointments_url' => route('telemed.mine'),
                ], 422);
            }

            return redirect()->route('telemed.mine')->with('error', $message);
        }

        // Check for active telemedicine appointment
        $activeTele = Appointment::query()
            ->where('patient_id', $patient->id)
            ->where('mode', 'TELE')
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->orderByDesc('created_at')
            ->first();

        if ($activeTele !== null) {
            $message = 'You already have an active telemedicine appointment. Please wait for it to be completed before requesting another.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'appointments_url' => route('telemed.mine'),
                ], 422);
            }

            return redirect()->route('telemed.mine')->with('error', $message);
        }

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            // Recorded so the triage team can see the patient chose
            // "none of the above" rather than a specific consultation type.
            'consultation_reason' => ConsultationReason::NoneOfTheAbove->value,
            'symptoms' => $validated['symptoms'],
            'complaint' => Str::limit($validated['complaint_details'], 255),
            'complaint_details' => $validated['complaint_details'],
            'status' => 'Pending',
            'mode' => 'TELE',
            'request_mode' => 'TELE',
            'triager_status' => 'Pending',
        ]);

        $message = 'Your telemedicine consultation request has been submitted. Our triage team will process it shortly.';

        if ($request->expectsJson()) {
            $request->session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'request_id' => $appointment->id,
                'appointments_url' => route('telemed.mine'),
            ], 201);
        }

        return redirect()->route('telemed.mine')->with('success', $message);
    }

    /**
     * GET /telemed/appointments/{appointment}/qr — patient appointment QR.
     */
    public function qr(Appointment $appointment, AppointmentQrCode $appointmentQrCode): Response
    {
        abort_unless(
            (int) Telemed::currentPatientId() === (int) $appointment->patient_id,
            403,
            'You are not allowed to view this appointment QR code.'
        );
        abort_unless($appointment->mode === 'FACE', 404);

        try {
            AppointmentSchema::ensureCompatibleColumns();
        } catch (QueryException $exception) {
            report($exception);
            abort(503, 'The appointment database needs a safe schema update before the QR code can be loaded.');
        }

        $appointmentQrCode->ensureToken($appointment);

        if (blank($appointment->qr_code_path)) {
            $appointment->forceFill([
                'qr_code_path' => route('telemed.appointment.qr', $appointment, false),
            ])->save();
        }

        return response($appointmentQrCode->svg($appointment), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * GET /telemed/appointments/{appointment}/join — gated Jitsi entry for patients.
     */
    public function join(Appointment $appointment): RedirectResponse
    {
        abort_unless(
            (int) Telemed::currentPatientId() === (int) $appointment->patient_id,
            403,
            'You are not allowed to join this consultation.'
        );
        abort_unless($appointment->mode === 'TELE', 404);
        abort_unless(Appointment::hasActiveStatus($appointment->status), 403);
        abort_unless((bool) $appointment->room_opened, 403, 'Create the Jitsi room before joining the consultation.');

        $appointment->loadMissing('patient');

        $meetingLink = filled($appointment->meeting_link)
            ? (string) $appointment->meeting_link
            : app(AppointmentJitsiRoom::class)->buildMeetingLink($appointment->patient, $appointment->date);

        abort_unless(filled($meetingLink), 404);

        return redirect()->away($meetingLink);
    }

    /**
     * POST /telemed/appointments/{appointment}/open-room — patient creates the shared Jitsi room.
     */
    public function openRoom(Request $request, Appointment $appointment, AppointmentJitsiRoom $rooms): RedirectResponse|JsonResponse
    {
        abort_unless(
            (int) Telemed::currentPatientId() === (int) $appointment->patient_id,
            403,
            'You are not allowed to open this consultation room.'
        );

        $result = $rooms->openForPatient($appointment);

        $message = $result['created']
            ? 'Jitsi room created. You may now join the consultation.'
            : 'The Jitsi room is already available. You may join now.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'room' => $rooms->presentRoomState($result['appointment']->fresh(), AppointmentJitsiRoom::OPENED_BY_PATIENT),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * GET /telemed/appointments/{appointment}/room — live Jitsi room state for polling.
     */
    public function roomStatus(Appointment $appointment, AppointmentJitsiRoom $rooms): JsonResponse
    {
        abort_unless(
            (int) Telemed::currentPatientId() === (int) $appointment->patient_id,
            403,
            'You are not allowed to view this consultation room.'
        );
        abort_unless($appointment->mode === 'TELE', 404);

        return response()->json([
            'room' => $rooms->presentRoomState($appointment->fresh(), AppointmentJitsiRoom::OPENED_BY_PATIENT),
        ]);
    }

    /**
     * POST /telemed/book/cancel
     */
    public function cancel(CancelAppointmentRequest $request): RedirectResponse|JsonResponse
    {
        $patientId = Telemed::currentPatientId();

        if ($patientId === null) {
            return back()->with('error', 'No patient session found. Please sign in again.');
        }

        $appointment = Appointment::query()
            ->where('id', $request->integer('cancel_id'))
            ->where('patient_id', $patientId)
            ->whereIn('status', Appointment::PATIENT_CANCELLABLE_STATUSES)
            ->whereIn('mode', ['TELE', 'FACE'])
            ->first();

        if ($appointment === null) {
            $message = 'That appointment could not be cancelled.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        try {
            AppointmentSchema::ensureCompatibleColumns();
        } catch (QueryException $exception) {
            report($exception);
        }

        $cancellationReason = $request->string('cancellation_reason')->toString();

        $update = ['status' => 'Cancelled'];

        if (AppointmentSchema::hasCancellationReasonColumn()) {
            $update['cancellation_reason'] = $cancellationReason;
        }

        $appointment->update($update);

        $message = 'Your appointment has been cancelled.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * GET /telemed/calendar?service_id=&month=YYYY-MM — monthly booking availability.
     */
    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services_tele,id'],
            'month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $service = ServiceTele::findOrFail($validated['service_id']);
        $month = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $availableWeekdays = Telemed::codesToFull($service->availability_day);
        $holidays = Telemed::holidaysMap();
        $slots = ServiceTimeslotTele::query()
            ->where('service_id', $service->id)
            ->orderBy('time_slot')
            ->get()
            ->keyBy('time_slot');

        $booked = Appointment::query()
            ->where('mode', 'TELE')
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->where('service_id', $service->id)
            ->whereBetween('date', [$month->toDateString(), $monthEnd->toDateString()])
            ->get(['date', 'time_slot'])
            ->groupBy(fn (Appointment $appointment) => $appointment->date->format('Y-m-d').'|'.$appointment->time_slot)
            ->map(fn ($appointments) => $appointments->count());

        $blocked = UnavailableTimeslotTele::query()
            ->where('service_id', $service->id)
            ->whereBetween('date', [$month->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->mapWithKeys(fn (UnavailableTimeslotTele $slot) => [
                $slot->date->format('Y-m-d').'|'.$slot->time_slot => $slot->reason ?: 'Unavailable',
            ]);

        $days = [];
        $date = $month->copy();

        while ($date->lessThanOrEqualTo($monthEnd)) {
            $dateKey = $date->toDateString();
            $remainingSlots = 0;

            foreach ($slots as $timeSlot => $slot) {
                $slotKey = $dateKey.'|'.$timeSlot;

                if (! $blocked->has($slotKey)) {
                    $remainingSlots += max(
                        0,
                        (int) $slot->slots - (int) $booked->get($slotKey, 0)
                    );
                }
            }

            $isPast = $date->isBefore(Carbon::today()->startOfDay());
            $isClosedToday = $date->isSameDay(Carbon::today()) && now()->hour >= 18;
            $isServiceDay = in_array($date->format('l'), $availableWeekdays, true);
            $holiday = $holidays[$dateKey] ?? null;
            $isAvailable = ! $isPast
                && ! $isClosedToday
                && $isServiceDay
                && $holiday === null
                && $remainingSlots > 0;
            $isFullyBooked = ! $isPast
                && ! $isClosedToday
                && $isServiceDay
                && $holiday === null
                && $remainingSlots === 0;

            $reason = match (true) {
                $isPast => 'Date has passed',
                $isClosedToday => 'Booking is closed for today',
                $holiday !== null => $holiday,
                ! $isServiceDay => 'Service is not available on this day',
                $isFullyBooked => 'Fully booked',
                default => $remainingSlots.' slot'.($remainingSlots === 1 ? '' : 's').' remaining',
            };

            $days[] = [
                'date' => $dateKey,
                'day' => (int) $date->format('j'),
                'available' => $isAvailable,
                'fully_booked' => $isFullyBooked,
                'remaining_slots' => $remainingSlots,
                'reason' => $reason,
            ];

            $date->addDay();
        }

        return response()->json([
            'service' => [
                'id' => $service->id,
                'name' => $service->service_name,
            ],
            'month' => $month->format('Y-m'),
            'month_label' => $month->format('F Y'),
            'days' => $days,
        ]);
    }

    /**
     * GET /telemed/timeslots?service_id=&date=  (JSON, port of timeslots_tele.php)
     */
    public function timeslots(Request $request): JsonResponse
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
     * GET /telemed/notifications/poll — live unread notification count.
     */
    public function pollNotifications(): JsonResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return response()->json(['unread' => 0, 'notifications' => []]);
        }

        $unread = $patient->notifications()
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'message' => $notification->message,
                'created_at' => $notification->created_at?->format('M j, Y h:i A'),
                'is_read' => (bool) $notification->is_read,
            ]);

        return response()->json([
            'unread' => $unread->where('is_read', false)->count(),
            'notifications' => $unread,
        ]);
    }

    /**
     * GET /telemed/my-appointments — scheduled visits + pending requests.
     */
    public function myAppointments(): View
    {
        $patientId = Telemed::currentPatientId();

        $appointments = $patientId
            ? $this->presentAppointments(
                $this->patientAppointments($patientId)
                    ->orderByDesc('a.date')
                    ->orderByDesc('a.time_slot')
                    ->get()
            )
            : [];

        $requests = $patientId
            ? $this->presentRequests(
                $this->patientRequests($patientId)
                    ->orderByDesc('a.created_at')
                    ->get()
            )
            : [];

        return view('patients.appointment', [
            'appointments' => $appointments,
            'requests' => $requests,
        ]);
    }

    /**
     * Return a JSON error for the dashboard booking modal or the legacy redirect.
     */
    private function bookingError(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }

    /**
     * Shape the joined rows the way the Blade views read them (plain strings,
     * so dates render as Y-m-d instead of a Carbon object).
     *
     * @param  Collection<int, Appointment>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function presentAppointments(Collection $rows): array
    {
        return $rows->map(function (Appointment $row) {
            $symptoms = is_array($row->symptoms) ? $row->symptoms : [];
            $symptomLabels = array_values(array_filter(array_map(
                fn (mixed $symptom): ?string => is_string($symptom)
                    ? Symptom::tryFrom($symptom)?->label()
                    : null,
                $symptoms,
            )));

            $isActive = Appointment::hasActiveStatus((string) $row->status);
            $roomState = app(AppointmentJitsiRoom::class)->presentRoomState($row, AppointmentJitsiRoom::OPENED_BY_PATIENT);
            $displayStatus = $this->patientDisplayStatus($row);

            return [
                'id' => $row->id,
                'service_name' => $this->serviceDisplayName($row),
                'service_mode_label' => self::modeLabel((string) $row->mode),
                'date' => $row->date?->format('Y-m-d'),
                'time_slot' => $row->time_slot,
                'status' => $row->status,
                'display_status' => $displayStatus,
                'mode' => $row->mode,
                'meeting_link' => $roomState['meeting_link'],
                'can_join' => $roomState['can_join'],
                'can_create_room' => $roomState['can_create_room'],
                'room_opened' => $roomState['room_opened'],
                'room_opened_by' => $roomState['room_opened_by'],
                'room_peer_notice' => $roomState['peer_notice'],
                'consultation_reason' => $row->consultation_reason,
                'consultation_reason_label' => ConsultationReason::tryFrom((string) $row->consultation_reason)?->label(),
                'symptoms' => $symptoms,
                'symptom_labels' => $symptomLabels,
                'complaint_details' => $row->complaint_details,
                'qr_code_url' => $row->mode === 'FACE' && $row->qr_code_token
                    ? route('telemed.appointment.qr', $row, false)
                    : null,
                'join_url' => $row->mode === 'TELE' && $roomState['can_join']
                    ? route('telemed.appointment.join', $row, false)
                    : null,
                'open_room_url' => $row->mode === 'TELE' && $roomState['can_create_room']
                    ? route('telemed.appointment.open-room', $row, false)
                    : null,
                'room_status_url' => $row->mode === 'TELE'
                    ? route('telemed.appointment.room', $row, false)
                    : null,
                'can_cancel' => $isActive && in_array((string) $row->mode, ['TELE', 'FACE'], true),
                'is_expired' => $row->date ? $row->date->lt(Carbon::today()) : false,
            ];
        })->all();
    }

    /**
     * Human label for the consultation type carried by `appointments.mode`.
     */
    private static function modeLabel(?string $mode): string
    {
        return $mode === 'FACE' ? 'Face-to-Face' : 'Telemedicine';
    }

    /**
     * Best available label for a booked appointment, most specific first:
     *
     *   1. the service the triager assigned (`service_id` joined per mode)
     *   2. the consultation reason captured on the request
     *   3. the consultation type, so the row is never a bare "Consultation"
     *
     * Legacy rows were stored with a NULL or orphaned `service_id`, so the
     * fallbacks are what keep those readable instead of misleading.
     */
    private function serviceDisplayName(Appointment $row): string
    {
        $serviceName = trim((string) ($row->service_name ?? ''));

        if ($serviceName !== '' && $serviceName !== 'Consultation') {
            return $serviceName;
        }

        $reasonLabel = ConsultationReason::tryFrom((string) $row->consultation_reason)?->label();

        return $reasonLabel ?? self::modeLabel((string) $row->mode);
    }

    private function patientDisplayStatus(Appointment $row): string
    {
        if ($row->triager_status === 'Approved' && strtolower((string) $row->status) === 'pending') {
            return 'Approved';
        }

        if (strtolower((string) $row->status) === 'approved') {
            return 'Approved';
        }

        return (string) ($row->status ?: 'Pending');
    }

    /**
     * Scheduled appointments for one patient (face-to-face and telemedicine).
     */
    private function patientAppointments(int $patientId): Builder
    {
        return Appointment::query()
            ->from('appointments as a')
            ->select([
                'a.*',
                DB::raw("COALESCE(st.service_name, s.service_name, 'Consultation') as service_name"),
            ])
            ->leftJoin('services_tele as st', function ($join): void {
                $join->on('a.service_id', '=', 'st.id')->where('a.mode', '=', 'TELE');
            })
            ->leftJoin('services as s', function ($join): void {
                $join->on('a.service_id', '=', 's.id')->where('a.mode', '=', 'FACE');
            })
            ->where('a.patient_id', $patientId)
            ->whereNotNull('a.date')
            ->whereIn('a.status', Appointment::ACTIVE_STATUSES);
    }

    /**
     * Pending appointment requests for one patient (no date/time yet).
     */
    private function patientRequests(int $patientId): Builder
    {
        return Appointment::query()
            ->from('appointments as a')
            ->where('a.patient_id', $patientId)
            ->whereNull('a.date')
            ->whereIn('a.triager_status', ['Pending', 'Processing', 'In Progress', 'Approved']);
    }

    /**
     * Shape pending requests for the patient dashboard.
     *
     * @param  Collection<int, Appointment>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function presentRequests(Collection $rows): array
    {
        return $rows->map(function (Appointment $row) {
            $symptoms = is_array($row->symptoms) ? $row->symptoms : [];
            $symptomLabels = array_values(array_filter(array_map(
                fn (mixed $symptom): ?string => is_string($symptom)
                    ? Symptom::tryFrom($symptom)?->label()
                    : null,
                $symptoms,
            )));

            $reasonLabel = $row->consultation_reason !== null
                ? ConsultationReason::tryFrom($row->consultation_reason)?->label()
                : null;

            $isFace = $row->request_mode === 'FACE';
            $symptomsText = $isFace && $reasonLabel
                ? $reasonLabel
                : implode(', ', $symptomLabels);
            $consultationDetails = $isFace
                ? ($reasonLabel ?? 'None')
                : ($row->complaint_details ?? 'None');

            return [
                'id' => $row->id,
                'is_face' => $isFace,
                'mode_label' => $isFace ? 'Face-to-Face' : 'Telemedicine',
                'symptoms' => $isFace && $reasonLabel ? [$reasonLabel] : $symptomLabels,
                'symptoms_text' => $symptomsText,
                'consultation_reason' => $row->consultation_reason,
                'consultation_reason_label' => $reasonLabel,
                'consultation_details' => $consultationDetails,
                'complaint_details' => $row->complaint_details,
                'status' => $row->status,
                'display_status' => $this->patientDisplayStatus($row),
                'triager_status' => $row->triager_status,
                'triager_action' => $row->triager_action,
                'requested_at' => $row->created_at,
                'qr_code_url' => null,
            ];
        })->all();
    }

    /**
     * GET /telemed/history — past appointments for the patient dashboard.
     */
    public function history(): JsonResponse
    {
        $patient = Telemed::currentPatient();

        if ($patient === null) {
            return response()->json(['history' => []]);
        }

        $pastAppointments = Appointment::query()
            ->from('appointments as a')
            ->select([
                'a.*',
                DB::raw("COALESCE(st.service_name, s.service_name, 'Consultation') as service_name"),
            ])
            ->leftJoin('services_tele as st', function ($join): void {
                $join->on('a.service_id', '=', 'st.id')->where('a.mode', '=', 'TELE');
            })
            ->leftJoin('services as s', function ($join): void {
                $join->on('a.service_id', '=', 's.id')->where('a.mode', '=', 'FACE');
            })
            ->where('a.patient_id', $patient->id)
            ->where(function ($query): void {
                $query->where('a.date', '<', Carbon::today()->toDateString())
                    ->orWhereIn('a.status', ['Completed', 'Cancelled', 'No Show']);
            })
            ->orderByDesc('a.date')
            ->orderByDesc('a.time_slot')
            ->get()
            ->map(fn (Appointment $row): array => [
                'id' => $row->id,
                'service_name' => $this->serviceDisplayName($row),
                'date' => $row->date?->format('M j, Y'),
                'time_slot' => $row->time_slot,
                'mode' => self::modeLabel((string) $row->mode),
                'status' => $row->status,
            ])
            ->all();

        return response()->json(['history' => $pastAppointments]);
    }
}
