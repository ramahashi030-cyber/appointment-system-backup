<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\SmsLog;
use App\Support\Sms;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Admin SMS Module.
 *
 * Lists the patients who currently own a booked appointment (the same
 * "Booked" status the admin dashboard uses), lets an admin filter/search them,
 * and sends an SMS to each selected patient's stored contact number through the
 * existing App\Support\Sms gateway. Every accepted message is written to the
 * existing sms_logs table via the SmsLog model.
 */
class SmsController extends Controller
{
    /**
     * Appointments with this status are what the application treats as booked
     * (patient bookings start as "Pending" and the triager promotes them).
     */
    private const BOOKED_STATUS = 'Booked';

    /** Route `mode` values and their service tables. */
    private const SERVICE_MODES = ['FACE', 'TELE'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $service = trim((string) $request->query('service', ''));
        $fromDate = $this->normaliseDate((string) $request->query('from_date', ''));
        $toDate = $this->normaliseDate((string) $request->query('to_date', ''));

        [$mode, $serviceId] = $this->parseServiceFilter($service);

        $dateError = null;
        if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
            $dateError = 'Date From cannot be later than Date To.';
        }

        return view('admin.sms', [
            'appointments' => $dateError === null
                ? $this->bookedPatients($search, $mode, $serviceId, $fromDate, $toDate)
                : collect(),
            'serviceOptions' => $this->serviceOptions(),
            'recentMessages' => $this->recentMessages(),
            'filters' => [
                'search' => $search,
                'service' => $service,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'dateError' => $dateError,
            'timezone' => (string) config('app.display_timezone', 'Asia/Manila'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'appointments' => ['nullable', 'array'],
            'appointments.*' => ['integer'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $ids = collect($validated['appointments'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'Please select at least one patient.');
        }

        $message = trim((string) ($validated['message'] ?? ''));

        if ($message === '') {
            return back()->with('error', 'Please enter a message.');
        }

        // Re-validate every selected id against the database instead of trusting
        // the browser, then keep one row per patient so nobody is texted twice.
        $appointments = Appointment::query()
            ->whereIn('id', $ids)
            ->where('status', self::BOOKED_STATUS)
            ->with('patient')
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get()
            ->filter(fn (Appointment $appointment): bool => $appointment->patient !== null)
            ->unique('patient_id')
            ->values();

        if ($appointments->isEmpty()) {
            return back()->with('error', 'The selected patients could not be found.');
        }

        $logEnabled = Schema::hasTable('sms_logs');
        $sent = 0;
        $failed = 0;
        $skipped = [];

        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;
            $contact = $this->normaliseContact($patient->contact_number);

            if ($contact === null) {
                $skipped[] = $this->patientName($patient);

                continue;
            }

            $body = str_ireplace('patientname', $this->patientName($patient), $message);

            if (Sms::send($contact, $body)) {
                $sent++;

                if ($logEnabled) {
                    SmsLog::create([
                        'recipient' => $contact,
                        'message' => $body,
                        'sent_to_all' => false,
                    ]);
                }
            } else {
                $failed++;
            }
        }

        if ($sent === 0 && $failed === 0 && $skipped !== []) {
            return back()->with(
                'error',
                'One or more selected patients do not have a valid contact number. Skipped: '.implode(', ', $skipped).'.'
            );
        }

        $parts = [];

        if ($sent > 0) {
            $parts[] = "Message sent to {$sent} patient".($sent === 1 ? '' : 's').'.';
        }

        if ($failed > 0) {
            $parts[] = "{$failed} message".($failed === 1 ? '' : 's').' could not be delivered.';
        }

        if ($skipped !== []) {
            $parts[] = 'Skipped (no valid contact number): '.implode(', ', $skipped).'.';
        }

        $summary = $parts === [] ? 'No messages were sent.' : implode(' ', $parts);

        return $sent > 0
            ? back()->with('success', $summary)
            : back()->with('error', $summary);
    }

    /**
     * Delete one or more entries from the recently-sent message log.
     *
     * The ids are re-checked against the database rather than trusted from the
     * browser, and the reported count is the number of rows that actually went
     * away, so a stale page can never claim more deletions than were made.
     */
    public function destroyMessages(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*' => ['integer'],
        ]);

        $message = 'The message log is not available.';
        $status = 404;

        if (Schema::hasTable('sms_logs')) {
            $ids = collect($validated['messages'])
                ->map(fn ($id): int => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $deleted = $ids->isEmpty()
                ? 0
                : SmsLog::query()->whereIn('id', $ids)->delete();

            if ($deleted > 0) {
                $message = "{$deleted} message".($deleted === 1 ? '' : 's').' deleted.';
                $status = 200;
            } else {
                $message = 'The selected messages could not be found.';
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->with($status < 400 ? 'success' : 'error', $message);
    }

    /**
     * Booked appointments joined to their patient, newest filters applied.
     *
     * Service names are resolved by `mode` because `service_id` is shared
     * between the `services` (FACE) and `services_tele` (TELE) tables.
     *
     * @return Collection<int, Appointment>
     */
    private function bookedPatients(
        string $search,
        ?string $mode,
        ?int $serviceId,
        string $fromDate,
        string $toDate,
    ): Collection {
        $faceServices = Service::query()->pluck('service_name', 'id');
        $teleServices = ServiceTele::query()->pluck('service_name', 'id');

        return Appointment::query()
            ->where('status', self::BOOKED_STATUS)
            ->with('patient')
            ->when($mode !== null && $serviceId !== null, function (Builder $query) use ($mode, $serviceId): void {
                $query->where('mode', $mode)->where('service_id', $serviceId);
            })
            ->when($fromDate !== '', fn (Builder $query): Builder => $query->whereDate('date', '>=', $fromDate))
            ->when($toDate !== '', fn (Builder $query): Builder => $query->whereDate('date', '<=', $toDate))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('patient', function (Builder $patient) use ($search): void {
                    $patient->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get()
            ->each(function (Appointment $appointment) use ($faceServices, $teleServices): void {
                $appointment->setAttribute('service_name', $appointment->mode === 'TELE'
                    ? $teleServices->get($appointment->service_id)
                    : $faceServices->get($appointment->service_id));
            });
    }

    /**
     * Service options for the Type of Service filter, grouped by mode.
     *
     * @return array<string, Collection<int|string, string>>
     */
    private function serviceOptions(): array
    {
        return [
            'FACE' => Service::query()->orderBy('service_name')->pluck('service_name', 'id'),
            'TELE' => ServiceTele::query()->orderBy('service_name')->pluck('service_name', 'id'),
        ];
    }

    /** @return Collection<int, SmsLog> */
    private function recentMessages(): Collection
    {
        if (! Schema::hasTable('sms_logs')) {
            return collect();
        }

        return SmsLog::query()
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    /**
     * Turn a "MODE:id" filter value into [mode, id], ignoring anything else.
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function parseServiceFilter(string $value): array
    {
        if (! str_contains($value, ':')) {
            return [null, null];
        }

        [$mode, $id] = explode(':', $value, 2);

        if (! in_array($mode, self::SERVICE_MODES, true) || ! ctype_digit($id)) {
            return [null, null];
        }

        return [$mode, (int) $id];
    }

    private function normaliseDate(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    /** Digits only, or null when the stored number cannot be a real mobile. */
    private function normaliseContact(?string $contact): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $contact);

        if ($digits === null || strlen($digits) < 10) {
            return null;
        }

        return $digits;
    }

    private function patientName(Patient $patient): string
    {
        return trim(implode(' ', array_filter([
            $patient->first_name,
            $patient->middlename,
            $patient->last_name,
        ]))) ?: 'Patient #'.$patient->id;
    }
}
