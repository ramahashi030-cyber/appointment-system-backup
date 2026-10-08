<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentCheckin;
use App\Models\Patient;
use App\Support\Kiosk\HomisGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Patient-facing OPD kiosk check-in.
 *
 * The flow mirrors the legacy QALINGA1 kiosk (process_qr.php,
 * update_appointment.php) but is enforced entirely server-side: the browser
 * only ever sends the raw QR payload (verify) and an opaque scan id plus the
 * patient's Correct / Not Correct answer (confirm). The QR verification token
 * never leaves the server session.
 *
 * Idempotency: verify reserves the appointment inside a transaction by marking
 * an appointment_checkins row "processing" (and locking the appointment row),
 * so a second terminal, a refresh or a double click cannot start a second
 * HOMIS registration. The encounter code is generated once per appointment per
 * day, stored on that row, and reused on every retry; HOMIS itself adopts an
 * encounter whose code already exists. The local status only becomes
 * "Completed" after HOMIS confirmed the registration.
 */
class KioskController extends Controller
{
    /**
     * Minutes after which a "processing" scan left by another session is
     * considered abandoned and may be taken over (reusing its encounter code).
     */
    private const STALE_PROCESSING_MINUTES = 3;

    public function __construct(private HomisGateway $homis) {}

    /**
     * Full-screen kiosk landing page (standalone, no admin layout).
     */
    public function show(): View
    {
        return view('admin.kiosk', [
            'kioskName' => (string) config('kiosk.name'),
            'screensaverAfter' => (int) config('kiosk.screensaver_after'),
            'resetAfter' => (int) config('kiosk.reset_after'),
        ]);
    }

    /**
     * Step 1: parse and fully validate a scanned QR payload, then reserve the
     * appointment. On success the confirmation data and an opaque scan id are
     * returned; the verification token stays in the server session.
     */
    public function verify(Request $request): JsonResponse
    {
        $payload = trim((string) $request->input('qr', ''));

        if ($payload === '') {
            return $this->refuse($request, null, null, 'qr-empty', 'Invalid QR code.');
        }

        if (! preg_match('/Appointment ID[:\s]*([0-9]+)/i', $payload, $idMatches)) {
            return $this->refuse($request, null, null, 'qr-format', 'Invalid QR code format.');
        }

        if (! preg_match('/Verification[:\s]*([A-Za-z0-9]{64})/i', $payload, $tokenMatches)) {
            return $this->refuse($request, null, null, 'qr-token-missing', 'Appointment QR verification is missing.');
        }

        $token = $tokenMatches[1];

        $appointment = Appointment::query()
            ->whereKey((int) $idMatches[1])
            ->where('qr_code_token', $token)
            ->first();

        if ($appointment === null) {
            return $this->refuse($request, null, $token, 'qr-unverified', 'Invalid or unverified appointment QR code.');
        }

        $patient = $appointment->patient;

        if ($patient === null) {
            return $this->refuse($request, $appointment, $token, 'patient-missing', 'Invalid or unverified appointment QR code.');
        }

        $message = $this->validateForScan($appointment);

        if ($message !== null) {
            return $this->refuse($request, $appointment, $token, $message['reason'], $message['message']);
        }

        $hospitalNumber = trim((string) $patient->hospital_number);

        if ($hospitalNumber === '') {
            return $this->refuse(
                $request,
                $appointment,
                $token,
                'no-hospital-number',
                "System has detected that you don't have a Hospital Number yet. Please proceed to the OPD encoder.",
            );
        }

        $lookup = $this->homis->lookupPerson($hospitalNumber);

        if ($lookup['status'] !== 'ok') {
            return $this->refuse(
                $request,
                $appointment,
                $token,
                'homis-unreachable',
                'The hospital records system is temporarily unavailable. Please try again or proceed to the OPD encoder.',
            );
        }

        if (! $lookup['found'] || $lookup['person'] === null) {
            return $this->refuse(
                $request,
                $appointment,
                $token,
                'homis-not-found',
                "Hospital number {$hospitalNumber} was not found in the hospital records system. Please proceed to the OPD encoder.",
            );
        }

        $sessionHash = $this->sessionHash();

        // T1: reserve the appointment before any HOMIS work starts.
        $reservation = DB::transaction(function () use ($request, $appointment, $patient, $token, $sessionHash, $hospitalNumber): array {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->first();

            if ($locked === null) {
                return ['reason' => 'qr-unverified', 'message' => 'Invalid or unverified appointment QR code'];
            }

            $message = $this->validateForScan($locked);

            if ($message !== null) {
                return $message;
            }

            // Reservations this kiosk session already holds are tracked in the
            // session itself, so a rotation of the session id can never make a
            // kiosk look like a competing terminal.
            $reservedIds = $this->reservedCheckinIds();

            $foreignProcessing = AppointmentCheckin::query()
                ->where('appointment_id', $locked->id)
                ->where('result', 'processing')
                ->where('scanned_at', '>=', Carbon::now()->subMinutes(self::STALE_PROCESSING_MINUTES))
                ->whereNotIn('id', $reservedIds === [] ? [-1] : $reservedIds)
                ->exists();

            if ($foreignProcessing) {
                return [
                    'reason' => 'another-terminal',
                    'message' => 'This appointment is being processed at another kiosk. Please wait a moment.',
                ];
            }

            // Scans this kiosk abandoned earlier (new QR, refresh, crashed
            // confirm) never completed, so they are resolved before the new
            // reservation is taken.
            AppointmentCheckin::query()
                ->whereIn('id', $reservedIds)
                ->where('result', 'processing')
                ->update(['result' => 'failed', 'reason' => 'superseded']);

            $checkin = AppointmentCheckin::create([
                'appointment_id' => $locked->id,
                'token_digest' => hash('sha256', $token),
                'kiosk_name' => (string) config('kiosk.name'),
                'ip_address' => $request->ip(),
                'scan_id' => (string) Str::uuid(),
                'result' => 'processing',
                'patient_name' => $this->patientName($patient),
                'hospital_number' => $hospitalNumber,
                'homis_encounter_code' => $this->encounterCode($locked, $hospitalNumber),
                'session_hash' => $sessionHash,
                'scanned_at' => Carbon::now(),
            ]);

            $reservedIds[] = $checkin->id;
            session(['kiosk_reserved' => array_slice($reservedIds, -10)]);

            return [
                'checkin_id' => $checkin->id,
                'scan_id' => $checkin->scan_id,
                'enccode' => (string) $checkin->homis_encounter_code,
            ];
        });

        if (isset($reservation['reason'])) {
            return $this->refuse($request, $appointment, $token, $reservation['reason'], $reservation['message']);
        }

        $person = $lookup['person'];

        session(['kiosk_scan' => [
            'scan_id' => $reservation['scan_id'],
            'checkin_id' => $reservation['checkin_id'],
            'appointment_id' => $appointment->id,
            'expires_at' => Carbon::now()->addSeconds((int) config('kiosk.scan_ticket_ttl', 120))->getTimestamp(),
            'hospital_number' => $hospitalNumber,
            'person' => $person,
            'patient_name' => $this->patientName($patient),
            'dob' => $patient->dob?->toDateString() ?? '',
            'tscode' => $this->tscode($appointment),
            'diagtxt' => $this->diagnosis($appointment),
            'enccode' => $reservation['enccode'],
        ]]);

        return response()->json([
            'success' => true,
            'scan_id' => $reservation['scan_id'],
            'patient' => [
                'last' => $person['patlast'] ?? '',
                'first' => $person['patfirst'] ?? '',
                'middle' => $person['patmiddle'] ?? '',
                'birth_date' => $this->displayBirthDate($person['patbdate'] ?? ''),
                'hospital_number' => $hospitalNumber,
            ],
            'appointment' => [
                'date' => $appointment->date->format('F d, Y'),
            ],
        ]);
    }

    /**
     * Step 2: the patient's Correct / Not Correct answer.
     *
     * Correct registers the OPD encounter in HOMIS first and only marks the
     * appointment Completed once HOMIS confirmed it. Not Correct only marks
     * the appointment Completed and shows the OPD encoder message, exactly
     * like the legacy kiosk. A finished scan consumes its ticket, so a second
     * confirm of the same scan can never run twice.
     */
    public function confirm(Request $request): JsonResponse
    {
        $scanId = trim((string) $request->input('scan_id', ''));
        $confirmed = filter_var($request->input('confirmed'), FILTER_VALIDATE_BOOL);
        $ticket = session('kiosk_scan');

        if ($scanId === '' || ! is_array($ticket) || ($ticket['scan_id'] ?? '') !== $scanId) {
            $this->abandonTicket($ticket);

            return $this->answer(false, 'Please scan your QR code again.');
        }

        if (Carbon::now()->getTimestamp() > (int) ($ticket['expires_at'] ?? 0)) {
            $this->abandonTicket($ticket, 'expired');

            return $this->answer(false, 'Your scan has expired. Please scan your QR code again.');
        }

        $appointment = Appointment::query()->find((int) ($ticket['appointment_id'] ?? 0));
        $checkin = AppointmentCheckin::find((int) ($ticket['checkin_id'] ?? 0));

        if ($appointment === null || $checkin === null || $checkin->result !== 'processing') {
            $this->abandonTicket($ticket);

            return $this->answer(false, 'Please scan your QR code again.');
        }

        if (! $confirmed) {
            return $this->notCorrect($appointment, $checkin);
        }

        // Re-validate under the appointment lock before touching HOMIS; the
        // reservation row stays "processing" throughout.
        $state = DB::transaction(function () use ($appointment, $checkin): string {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->first();

            if ($locked === null) {
                return 'missing';
            }

            $message = $this->validateForScan($locked);

            if ($message !== null) {
                return $message['reason'];
            }

            $fresh = AppointmentCheckin::query()->whereKey($checkin->id)->value('result');

            return $fresh === 'processing' ? 'ok' : 'stale';
        });

        if ($state !== 'ok') {
            $message = $this->confirmStateMessage($state, $appointment);

            $checkin->forceFill([
                'result' => 'failed',
                'reason' => $state,
                'message' => mb_substr($message, 0, 191),
            ])->save();

            session()->forget('kiosk_scan');

            return $this->answer(false, $message);
        }

        $registration = $this->homis->registerOpd(
            (array) $ticket['person'],
            (string) $ticket['dob'],
            (string) $ticket['tscode'],
            (string) $ticket['diagtxt'],
            (string) $ticket['enccode'],
        );

        if ($registration['status'] !== 'ok') {
            $checkin->forceFill([
                'result' => 'failed',
                'reason' => mb_substr('homis-registration-failed: '.($registration['message'] ?? ''), 0, 255),
                'message' => 'Unable to complete registration in HOMIS',
            ])->save();

            session()->forget('kiosk_scan');

            return $this->answer(
                false,
                'Unable to complete your registration in the hospital records system. Please proceed to the OPD encoder.',
            );
        }

        $message = 'Appointment completed, You have been Automatically Added to OPD list for today, Please wait for your batch to be called';

        // T2: HOMIS succeeded, so the local status may safely become Completed.
        DB::transaction(function () use ($appointment, $checkin, $registration, $message): void {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->first();

            if ($locked !== null && strcasecmp(trim((string) $locked->status), 'Completed') !== 0) {
                $locked->status = 'Completed';
                $locked->save();
            }

            $checkin->forceFill([
                'result' => 'completed',
                'reason' => 'registered',
                'message' => mb_substr($message, 0, 191),
                'confirmed_at' => Carbon::now(),
                'homis_encounter_code' => $registration['enccode'],
            ])->save();

            // Scans other terminals left behind are resolved by this completion.
            AppointmentCheckin::query()
                ->where('appointment_id', $appointment->id)
                ->where('id', '!=', $checkin->id)
                ->where('result', 'processing')
                ->update(['result' => 'failed', 'reason' => 'superseded']);
        });

        session()->forget('kiosk_scan');

        return $this->answer(true, $message);
    }

    /**
     * "Not Correct": complete the appointment locally without any HOMIS write
     * and hand the patient to the OPD encoder, like the legacy kiosk.
     */
    private function notCorrect(Appointment $appointment, AppointmentCheckin $checkin): JsonResponse
    {
        $message = 'Please Proceed to OPD encoder to Update Incorrect Data and for Manual Log in the iHOMIS.';

        DB::transaction(function () use ($appointment, $checkin, $message): void {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->first();

            if ($locked !== null && strcasecmp(trim((string) $locked->status), 'Completed') !== 0) {
                $locked->status = 'Completed';
                $locked->save();
            }

            $checkin->forceFill([
                'result' => 'completed',
                'reason' => 'not-correct',
                'message' => mb_substr($message, 0, 191),
                'confirmed_at' => Carbon::now(),
            ])->save();

            AppointmentCheckin::query()
                ->where('appointment_id', $appointment->id)
                ->where('id', '!=', $checkin->id)
                ->where('result', 'processing')
                ->update(['result' => 'failed', 'reason' => 'superseded']);
        });

        session()->forget('kiosk_scan');

        return $this->answer(true, $message);
    }

    /**
     * Shared status/date validation for an appointment being scanned or
     * confirmed. Returns null when the appointment may proceed, otherwise a
     * reason + patient-friendly message pair.
     *
     * @return array{reason: string, message: string}|null
     */
    private function validateForScan(Appointment $appointment): ?array
    {
        $status = trim((string) $appointment->status);

        if (strcasecmp($status, 'Completed') === 0) {
            return [
                'reason' => 'already-completed',
                'message' => 'This appointment has already been completed. Please check the schedule in your portal.',
            ];
        }

        if (strcasecmp($status, 'Cancelled') === 0) {
            return ['reason' => 'cancelled', 'message' => 'This appointment has been cancelled.'];
        }

        if (! Appointment::hasActiveStatus($status)) {
            return ['reason' => 'inactive', 'message' => 'This appointment is not active.'];
        }

        $today = Carbon::now($this->timezone())->toDateString();
        $scheduled = $appointment->date->toDateString();
        $scheduledDisplay = $appointment->date->format('F d, Y');

        if ($scheduled < $today) {
            return [
                'reason' => 'expired',
                'message' => "Your schedule dated {$scheduledDisplay} has already expired, please login to Patient "
                    .'Appointment System, cancel your expired appointment then select new appointment.',
            ];
        }

        if ($scheduled > $today) {
            return [
                'reason' => 'not-today',
                'message' => "Your schedule is on {$scheduledDisplay}. We only process those who have schedule for today.",
            ];
        }

        return null;
    }

    /**
     * Drop a scan ticket that cannot be honoured and release its reservation
     * so the terminal (or any other) can scan again immediately.
     */
    private function abandonTicket($ticket, string $reason = 'abandoned'): void
    {
        if (is_array($ticket)) {
            AppointmentCheckin::query()
                ->whereKey((int) ($ticket['checkin_id'] ?? 0))
                ->where('result', 'processing')
                ->update(['result' => 'failed', 'reason' => $reason]);
        }

        session()->forget('kiosk_scan');
    }

    /**
     * Patient-friendly wording for a confirmation-time refusal.
     */
    private function confirmStateMessage(string $state, Appointment $appointment): string
    {
        return match ($state) {
            'already-completed' => 'This appointment has already been completed. Please check the schedule in your portal.',
            'cancelled' => 'This appointment has been cancelled.',
            'expired' => "Your schedule dated {$appointment->date->format('F d, Y')} has already expired. Please scan your QR code again.",
            'not-today' => "Your schedule is on {$appointment->date->format('F d, Y')}. We only process those who have schedule for today.",
            'stale' => 'This scan is no longer active. Please scan your QR code again.',
            default => 'This appointment could not be verified. Please scan your QR code again.',
        };
    }

    /**
     * Encounter code for today's registration of this appointment. Reused from
     * an earlier scan of the same appointment (same day) so every retry works
     * with the same HOMIS idempotency key instead of creating a new one.
     */
    private function encounterCode(Appointment $appointment, string $hospitalNumber): string
    {
        $timezone = $this->timezone();
        $dayStart = Carbon::now($timezone)->startOfDay()->setTimezone('UTC');
        $prior = AppointmentCheckin::query()
            ->where('appointment_id', $appointment->id)
            ->whereNotNull('homis_encounter_code')
            ->where('scanned_at', '>=', $dayStart)
            ->where('scanned_at', '<', $dayStart->copy()->addDay())
            ->orderByDesc('id')
            ->first();

        $existing = trim((string) $prior?->homis_encounter_code);

        if ($existing !== '') {
            return $existing;
        }

        $fhud = (string) config('kiosk.homis.fhud', '0001818');

        return $fhud.$hospitalNumber.Carbon::now($timezone)->format('YmdHis').sprintf('%06d', $appointment->id);
    }

    /**
     * HOMIS service code (tscode) for the appointment's service, resolved the
     * way the legacy kiosk joined services / services_tele by mode.
     */
    private function tscode(Appointment $appointment): string
    {
        $service = $appointment->mode === 'TELE' ? $appointment->serviceTele : $appointment->service;

        return trim((string) ($service?->homis_code ?? ''));
    }

    /**
     * Free-text diagnosis handed to HOMIS: complaint details, else complaint.
     */
    private function diagnosis(Appointment $appointment): string
    {
        $details = trim((string) $appointment->complaint_details);

        return $details !== '' ? $details : trim((string) $appointment->complaint);
    }

    private function patientName(Patient $patient): string
    {
        return trim(implode(' ', array_filter([
            $patient->first_name,
            $patient->middlename,
            $patient->last_name,
        ])));
    }

    private function displayBirthDate(string $birthDate): string
    {
        $birthDate = trim($birthDate);

        if ($birthDate === '') {
            return '';
        }

        $timestamp = strtotime($birthDate);

        return $timestamp === false
            ? $birthDate
            : Carbon::createFromTimestamp($timestamp, $this->timezone())->format('F d, Y');
    }

    /**
     * Record a refused scan and return the patient-friendly failure.
     */
    private function refuse(Request $request, ?Appointment $appointment, ?string $token, string $reason, string $message): JsonResponse
    {
        $patient = $appointment?->patient;

        AppointmentCheckin::create([
            'appointment_id' => $appointment?->id,
            'token_digest' => $token === null ? null : hash('sha256', $token),
            'kiosk_name' => (string) config('kiosk.name'),
            'ip_address' => $request->ip(),
            'scan_id' => (string) Str::uuid(),
            'result' => 'failed',
            'reason' => $reason,
            'patient_name' => $patient === null ? null : $this->patientName($patient),
            'hospital_number' => $patient === null ? null : (trim((string) $patient->hospital_number) ?: null),
            'session_hash' => $this->sessionHash(),
            'scanned_at' => Carbon::now(),
            'message' => mb_substr($message, 0, 191),
        ]);

        return $this->answer(false, $message);
    }

    private function answer(bool $success, string $message): JsonResponse
    {
        return response()->json(['success' => $success, 'message' => $message]);
    }

    /**
     * Digest of this session's id, recorded on each scan as audit metadata.
     */
    private function sessionHash(): string
    {
        return hash('sha256', (string) session()->getId());
    }

    /**
     * Check-in rows this kiosk session is allowed to treat as its own
     * reservations (stored in the session, so it survives session id rotation
     * and is invisible to other terminals).
     *
     * @return list<int>
     */
    private function reservedCheckinIds(): array
    {
        $ids = session('kiosk_reserved');

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_map('intval', $ids));
    }

    private function timezone(): string
    {
        return (string) config('app.display_timezone', 'Asia/Manila');
    }
}
