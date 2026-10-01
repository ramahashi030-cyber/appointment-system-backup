<?php

namespace App\Http\Controllers\Doctor;

use App\ConsultationReason;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ServiceTimeslotTele;
use App\Models\Staff;
use App\Support\AppointmentJitsiRoom;
use App\Support\AppointmentSchema;
use App\Symptom;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DoctorDashboardController extends Controller
{
    private const TIMEZONE = 'Asia/Manila';

    private const PROFILE_PIC_DIR = 'uploads/doctor-profiles';

    public function dashboard(Request $request): View
    {
        AppointmentSchema::ensureCompatibleColumns();
        $now = Carbon::now(self::TIMEZONE);
        $today = $now->copy()->startOfDay();
        $selectedDate = $this->selectedDate($request, $today);
        $range = $this->range($request);
        [$rangeStart, $rangeEnd] = $this->rangeBounds($range, $selectedDate);
        $calendarDays = $this->calendarDays($range, $rangeStart, $rangeEnd, $today);

        $rangeRows = $this->appointmentQuery()
            ->whereBetween('a.date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->orderBy('a.date')
            ->orderBy('a.time_slot')
            ->orderBy('a.id')
            ->get();
        $rangeAppointments = $this->presentAppointments($rangeRows);

        $todayAppointments = $this->presentAppointments(
            $this->appointmentQuery()
                ->whereDate('a.date', $today)
                ->orderBy('a.time_slot')
                ->orderBy('a.id')
                ->get()
        );

        $todayQuery = $this->appointmentQuery()->whereDate('a.date', $today);
        $totalToday = (clone $todayQuery)->count();
        $completedToday = (clone $todayQuery)->whereRaw('LOWER(a.status) = ?', ['completed'])->count();
        $cancelledToday = (clone $todayQuery)->whereRaw('LOWER(a.status) = ?', ['cancelled'])->count();
        $upcomingToday = (clone $todayQuery)->whereIn('a.status', Appointment::ACTIVE_STATUSES)->count();
        $progress = $totalToday > 0 ? (int) round(($completedToday / $totalToday) * 100) : 0;

        $activeUpcoming = $this->presentAppointments(
            $this->appointmentQuery()
                ->whereIn('a.status', Appointment::ACTIVE_STATUSES)
                ->whereDate('a.date', '>=', $today)
                ->orderBy('a.date')
                ->orderBy('a.time_slot')
                ->get()
        );
        $nextAppointment = $this->nextAppointment($activeUpcoming, $now);
        $notificationCount = $activeUpcoming->count();
        $timeSlots = $this->timeSlots();
        $scheduleByCell = $rangeAppointments
            ->groupBy(fn (array $appointment): string => $appointment['date'].'|'.$appointment['time_slot'])
            ->map(fn (Collection $appointments): array => $appointments->values()->all())
            ->all();
        [$previousDate, $nextDate] = $this->rangeNavigation($range, $selectedDate);

        return view('doctor.dashboard', [
            'doctor' => $this->doctorAccount(),
            'doctorNotificationCount' => $notificationCount,
            'greeting' => $this->greeting($now),
            'today' => $today,
            'range' => $range,
            'selectedDate' => $selectedDate,
            'dateLabel' => $this->rangeLabel($range, $rangeStart, $rangeEnd),
            'previousDate' => $previousDate,
            'nextDate' => $nextDate,
            'calendarDays' => $calendarDays,
            'timeSlots' => $timeSlots,
            'scheduleByCell' => $scheduleByCell,
            'rangeAppointments' => $rangeAppointments,
            'todayAppointments' => $todayAppointments,
            'nextAppointment' => $nextAppointment,
            'overview' => [
                'total' => $totalToday,
                'completed' => $completedToday,
                'upcoming' => $upcomingToday,
                'cancelled' => $cancelledToday,
                'progress' => $progress,
            ],
            'availability' => $this->availability($today, $totalToday, $completedToday, $cancelledToday),
        ]);
    }

    public function appointments(Request $request): View
    {
        AppointmentSchema::ensureCompatibleColumns();
        $query = $this->appointmentQuery();
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $date = trim((string) $request->query('date', ''));

        if ($search !== '') {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
            $query->where(function (Builder $patientQuery) use ($term): void {
                $patientQuery
                    ->where('p.first_name', 'like', $term)
                    ->orWhere('p.middlename', 'like', $term)
                    ->orWhere('p.last_name', 'like', $term)
                    ->orWhere('p.hospital_number', 'like', $term)
                    ->orWhere('s.service_name', 'like', $term)
                    ->orWhere('a.consultation_reason', 'like', $term);
            });
        }

        if (in_array($status, ['Booked', 'Pending', 'Confirmed', 'Completed', 'Cancelled'], true)) {
            $query->whereRaw('LOWER(a.status) = ?', [Str::lower($status)]);
        }

        if (Carbon::hasFormat($date, 'Y-m-d')) {
            $query->whereDate('a.date', $date);
        }

        $paginator = $query
            ->orderByDesc('a.date')
            ->orderByDesc('a.time_slot')
            ->orderByDesc('a.id')
            ->paginate(20)
            ->withQueryString();

        return view('doctor.appointments', [
            'appointments' => $this->presentAppointments(new Collection($paginator->items())),
            'appointmentPagination' => $paginator,
            'doctorNotificationCount' => $this->notificationCount(),
        ]);
    }

    public function patients(Request $request): View
    {
        $query = DB::table('appointments as a')
            ->join('patients as p', 'p.id', '=', 'a.patient_id')
            ->where('a.mode', 'TELE');
        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
            $query->where(function ($patientQuery) use ($term): void {
                $patientQuery
                    ->where('p.first_name', 'like', $term)
                    ->orWhere('p.middlename', 'like', $term)
                    ->orWhere('p.last_name', 'like', $term)
                    ->orWhere('p.hospital_number', 'like', $term)
                    ->orWhere('p.contact_number', 'like', $term);
            });
        }

        $paginator = $query
            ->select([
                'p.id as patient_id',
                'p.first_name',
                'p.middlename',
                'p.last_name',
                'p.hospital_number',
                'p.contact_number',
                'p.gender',
                'p.profile_pic',
                DB::raw('COUNT(a.id) as appointment_count'),
                DB::raw('MAX(a.date) as last_appointment'),
            ])
            ->groupBy([
                'p.id',
                'p.first_name',
                'p.middlename',
                'p.last_name',
                'p.hospital_number',
                'p.contact_number',
                'p.gender',
                'p.profile_pic',
            ])
            ->orderByDesc('last_appointment')
            ->paginate(20)
            ->withQueryString();

        $patients = $paginator->getCollection()->map(function (object $patient): array {
            $name = $this->patientName($patient);
            $profilePic = $patient->profile_pic;

            return [
                'id' => (int) $patient->patient_id,
                'name' => $name,
                'initials' => $this->initials($name),
                'hospital_number' => $patient->hospital_number,
                'contact_number' => $patient->contact_number,
                'gender' => $patient->gender,
                'profile_pic' => $profilePic
                    ? (Str::startsWith($profilePic, ['http://', 'https://']) ? $profilePic : asset($profilePic))
                    : null,
                'appointment_count' => (int) $patient->appointment_count,
                'last_appointment_display' => $patient->last_appointment
                    ? Carbon::parse($patient->last_appointment)->format('M j, Y')
                    : '—',
            ];
        })->values();
        $paginator->setCollection($patients);

        return view('doctor.patients', [
            'patients' => $patients,
            'patientPagination' => $paginator,
            'doctorNotificationCount' => $this->notificationCount(),
        ]);
    }

    public function notifications(): View
    {
        $today = Carbon::now(self::TIMEZONE)->startOfDay();
        $appointments = $this->presentAppointments(
            $this->appointmentQuery()
                ->whereIn('a.status', Appointment::ACTIVE_STATUSES)
                ->whereDate('a.date', '>=', $today)
                ->orderBy('a.date')
                ->orderBy('a.time_slot')
                ->get()
        );

        return view('doctor.notifications', [
            'notifications' => $appointments,
            'doctorNotificationCount' => $appointments->count(),
        ]);
    }

    public function profile(): View
    {
        return view('doctor.profile', [
            'doctor' => $this->doctorAccount(),
            'doctorNotificationCount' => $this->notificationCount(),
        ]);
    }

    /**
     * PUT /doctor/profile — save edits to the signed-in doctor's details and
     * optional new profile picture.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $doctor = Staff::query()
            ->activeDoctors()
            ->findOrFail((int) session('staff_id'));

        // Length limits mirror the staff column widths so a long value fails
        // validation instead of a MySQL 1406 at update time.
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('staff', 'email')->ignore($doctor->id),
            ],
            'contactno' => ['nullable', 'string', 'max:20'],
            'profile_pic' => ['nullable', 'image', 'max:2048'],
        ]);

        $replacement = $this->storeProfilePic($request);

        if ($replacement !== null) {
            $this->deleteProfilePic($doctor->profile_pic);
        }

        $doctor->fill(collect($validated)->except('profile_pic')->all());

        if ($replacement !== null) {
            $doctor->profile_pic = $replacement;
        }

        $doctor->save();

        // The doctor middleware re-checks the session email against the staff
        // record, so the session copy has to follow any email or name change.
        $request->session()->put([
            'doctor_email' => $doctor->email,
            'doctor_name' => $this->displayName($doctor),
            'doctor_profile_pic' => $this->profilePicUrl($doctor->profile_pic),
        ]);

        return redirect()->route('doctor.profile')->with(
            'success',
            'Your profile has been updated.'
        );
    }

    /**
     * PUT /doctor/profile/password — change the signed-in doctor's password
     * after confirming the current one.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $doctor = Staff::query()
            ->activeDoctors()
            ->findOrFail((int) session('staff_id'));

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'max:200'],
        ]);

        if (! Hash::check($validated['current_password'], $doctor->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $doctor->password = $validated['password'];
        $doctor->save();

        return redirect()->route('doctor.profile')->with(
            'success',
            'Your password has been updated.'
        );
    }

    /**
     * POST /doctor/appointments/{appointment}/open-room — create the shared Jitsi room.
     */
    public function openRoom(Request $request, Appointment $appointment, AppointmentJitsiRoom $rooms): RedirectResponse|JsonResponse
    {
        $result = $rooms->openForDoctor($appointment, (int) session('staff_id'));

        if ($result['created'] && $result['appointment']->patient !== null) {
            $result['appointment']->patient->notifications()->create([
                'message' => (string) $result['peer_notice'],
                'is_read' => false,
            ]);
        }

        $message = $result['created']
            ? 'Jitsi room created. You may now join the consultation.'
            : 'The Jitsi room is already available. You may join now.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'room' => $rooms->presentRoomState($result['appointment']->fresh(), AppointmentJitsiRoom::OPENED_BY_DOCTOR),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * GET /doctor/appointments/{appointment}/room — live Jitsi room state for polling.
     */
    public function roomStatus(Appointment $appointment, AppointmentJitsiRoom $rooms): JsonResponse
    {
        abort_unless($appointment->mode === 'TELE', 404);

        return response()->json([
            'room' => $rooms->presentRoomState($appointment->fresh(), AppointmentJitsiRoom::OPENED_BY_DOCTOR),
        ]);
    }

    private function appointmentQuery(): Builder
    {
        return Appointment::query()
            ->from('appointments as a')
            ->leftJoin('patients as p', 'p.id', '=', 'a.patient_id')
            ->leftJoin('services_tele as s', 's.id', '=', 'a.service_id')
            ->where('a.mode', 'TELE')
            ->select([
                'a.*',
                'p.first_name',
                'p.middlename',
                'p.last_name',
                'p.hospital_number',
                'p.contact_number',
                'p.profile_pic',
                's.service_name',
            ]);
    }

    /**
     * @param  Collection<int, Appointment>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function presentAppointments(Collection $rows): Collection
    {
        return $rows->map(function (Appointment $row): array {
            $patientName = $this->patientName($row);
            $status = (string) ($row->status ?: 'Booked');
            $statusClass = Str::lower((string) preg_replace('/[^A-Za-z]+/', '', $status)) ?: 'booked';
            $symptoms = is_array($row->symptoms) ? $row->symptoms : [];
            $symptomLabels = array_values(array_filter(array_map(
                fn (mixed $symptom): ?string => is_string($symptom)
                    ? Symptom::tryFrom($symptom)?->label()
                    : null,
                $symptoms,
            )));
            $isActive = Appointment::hasActiveStatus($status);
            $roomState = app(AppointmentJitsiRoom::class)->presentRoomState($row, AppointmentJitsiRoom::OPENED_BY_DOCTOR);

            return [
                'id' => $row->id,
                'patient_id' => $row->patient_id,
                'patient_name' => $patientName ?: 'Unknown patient',
                'patient_initials' => $this->initials($patientName),
                'hospital_number' => $row->hospital_number,
                'contact_number' => $row->contact_number,
                'profile_pic' => $row->profile_pic
                    ? (Str::startsWith($row->profile_pic, ['http://', 'https://']) ? $row->profile_pic : asset($row->profile_pic))
                    : null,
                'service_name' => $row->service_name ?: 'Telemedicine consultation',
                'date' => $row->date?->format('Y-m-d'),
                'date_display' => $row->date?->format('M j, Y') ?: '—',
                'time_slot' => $row->time_slot,
                'time_display' => $this->timeDisplay($row->time_slot),
                'status' => $row->status,
                'status_label' => ucfirst($status),
                'status_class' => $statusClass,
                'meeting_link' => $roomState['meeting_link'],
                'room_opened' => $roomState['room_opened'],
                'room_opened_by' => $roomState['room_opened_by'],
                'can_open_room' => $roomState['can_create_room'],
                'can_join' => $roomState['can_join'],
                'room_peer_notice' => $roomState['peer_notice'],
                'consultation_reason_label' => ConsultationReason::tryFrom((string) $row->consultation_reason)?->label(),
                'symptom_labels' => $symptomLabels,
                'complaint_details' => $row->complaint_details ?: $row->complaint,
                'is_active' => $isActive,
            ];
        });
    }

    private function selectedDate(Request $request, Carbon $today): Carbon
    {
        $date = (string) $request->query('date', $today->toDateString());

        return Carbon::hasFormat($date, 'Y-m-d')
            ? Carbon::parse($date, self::TIMEZONE)->startOfDay()
            : $today->copy();
    }

    private function range(Request $request): string
    {
        $range = Str::lower((string) $request->query('range', 'week'));

        return in_array($range, ['day', 'week', 'month'], true) ? $range : 'week';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangeBounds(string $range, Carbon $selectedDate): array
    {
        return match ($range) {
            'day' => [$selectedDate->copy(), $selectedDate->copy()],
            'month' => [
                $selectedDate->copy()->startOfMonth(),
                $selectedDate->copy()->endOfMonth(),
            ],
            default => [
                $selectedDate->copy()->startOfWeek(Carbon::MONDAY),
                $selectedDate->copy()->endOfWeek(Carbon::SUNDAY),
            ],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangeNavigation(string $range, Carbon $selectedDate): array
    {
        return match ($range) {
            'day' => [$selectedDate->copy()->subDay(), $selectedDate->copy()->addDay()],
            'month' => [
                $selectedDate->copy()->subMonthNoOverflow(),
                $selectedDate->copy()->addMonthNoOverflow(),
            ],
            default => [$selectedDate->copy()->subWeek(), $selectedDate->copy()->addWeek()],
        };
    }

    private function rangeLabel(string $range, Carbon $start, Carbon $end): string
    {
        return match ($range) {
            'day' => $start->format('l, F j, Y'),
            'month' => $start->format('F Y'),
            default => $start->isSameMonth($end)
                ? $start->format('M j').' – '.$end->format('j, Y')
                : $start->format('M j').' – '.$end->format('M j, Y'),
        };
    }

    /**
     * @return array<int, array<string, bool|string|int>>
     */
    private function calendarDays(string $range, Carbon $rangeStart, Carbon $rangeEnd, Carbon $today): array
    {
        $days = [];

        if ($range === 'month') {
            $cursor = $rangeStart->copy()->startOfWeek(Carbon::MONDAY);

            for ($index = 0; $index < 42; $index++) {
                $days[] = $this->calendarDay($cursor, $today, $rangeStart->month);
                $cursor->addDay();
            }

            return $days;
        }

        $count = $range === 'day' ? 1 : 7;
        $cursor = $rangeStart->copy();

        for ($index = 0; $index < $count; $index++) {
            $days[] = $this->calendarDay($cursor, $today, $rangeStart->month);
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @return array<string, bool|string|int>
     */
    private function calendarDay(Carbon $date, Carbon $today, int $currentMonth): array
    {
        return [
            'date' => $date->toDateString(),
            'day_name' => $date->format('D'),
            'day_number' => $date->day,
            'date_short' => $date->format('M j'),
            'is_today' => $date->isSameDay($today),
            'in_current_month' => $date->month === $currentMonth,
        ];
    }

    /**
     * @return array<int, array{value: string, display: string}>
     */
    private function timeSlots(): array
    {
        $slots = Schema::hasTable('service_timeslots_tele')
            ? ServiceTimeslotTele::query()->distinct()->orderBy('time_slot')->pluck('time_slot')
            : collect();

        if ($slots->isEmpty()) {
            $slots = collect(['08:00 - 10:00', '10:00 - 12:00', '12:00 - 14:00', '14:00 - 16:00']);
        }

        return $slots->map(fn (mixed $slot): array => [
            'value' => (string) $slot,
            'display' => $this->timeDisplay((string) $slot),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $appointments
     * @return array<string, mixed>|null
     */
    private function nextAppointment(Collection $appointments, Carbon $now): ?array
    {
        $future = $appointments->first(fn (array $appointment): bool => ($appointment['date'] ?? '') > $now->toDateString());

        if (is_array($future)) {
            return $future;
        }

        foreach ($appointments as $appointment) {
            $parts = preg_split('/\s*-\s*/', (string) ($appointment['time_slot'] ?? '')) ?: [];
            $end = $parts[1] ?? $parts[0] ?? null;

            if ($end !== null && Carbon::hasFormat($end, 'H:i')) {
                $endDate = Carbon::parse($appointment['date'].' '.$end, self::TIMEZONE);
                if ($now->lessThanOrEqualTo($endDate)) {
                    return $appointment;
                }
            }
        }

        $first = $appointments->first();

        return is_array($first) ? $first : null;
    }

    /**
     * @return array<int, array{label: string, count: int, hours: string, class: string}>
     */
    private function availability(Carbon $today, int $totalToday, int $completedToday, int $cancelledToday): array
    {
        $activeToday = (clone $this->appointmentQuery())
            ->whereDate('a.date', $today)
            ->whereIn('a.status', Appointment::ACTIVE_STATUSES)
            ->count();
        $totalCapacity = Schema::hasTable('service_timeslots_tele')
            ? max((int) ServiceTimeslotTele::query()->sum('slots'), $totalToday)
            : $totalToday;
        $available = max(0, $totalCapacity - $activeToday - $completedToday - $cancelledToday);
        $hours = fn (int $count): string => number_format($count * 1.5, 1).' hrs';

        return [
            ['label' => 'Available', 'count' => $available, 'hours' => $hours($available), 'class' => ''],
            ['label' => 'Booked', 'count' => $activeToday, 'hours' => $hours($activeToday), 'class' => 'booked'],
            ['label' => 'Completed', 'count' => $completedToday, 'hours' => $hours($completedToday), 'class' => 'completed'],
            ['label' => 'Cancelled', 'count' => $cancelledToday, 'hours' => $hours($cancelledToday), 'class' => 'cancelled'],
        ];
    }

    private function notificationCount(): int
    {
        $today = Carbon::now(self::TIMEZONE)->startOfDay();

        return $this->appointmentQuery()
            ->whereIn('a.status', Appointment::ACTIVE_STATUSES)
            ->whereDate('a.date', '>=', $today)
            ->count();
    }

    private function doctorAccount(): array
    {
        $doctor = Staff::query()
            ->activeDoctors()
            ->findOrFail((int) session('staff_id'));

        return [
            'name' => $this->displayName($doctor),
            'email' => $doctor->email,
            'specialty' => $doctor->specialty(),
            'initials' => $this->initials($doctor->full_name),
            'first_name' => $doctor->FirstName,
            'middle_name' => $doctor->MiddleName,
            'last_name' => $doctor->LastName,
            'contactno' => $doctor->contactno,
            'profile_pic' => $this->profilePicUrl($doctor->profile_pic),
        ];
    }

    private function displayName(Staff $doctor): string
    {
        $name = Str::headline(Str::lower($doctor->full_name));

        return str_starts_with(strtolower($name), 'dr.') ? $name : 'Dr. '.$name;
    }

    private function profilePicUrl(?string $path): ?string
    {
        if ($path === null || $path === '' || Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset($path);
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

        $name = Str::lower(Str::random(10)).'.'.$file->guessExtension();

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

    private function patientName(object $patient): string
    {
        $name = trim(implode(' ', array_filter([
            $patient->first_name ?? null,
            $patient->middlename ?? null,
            $patient->last_name ?? null,
        ])));

        return $name === '' ? '' : Str::headline(Str::lower($name));
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials ?: '?';
    }

    private function timeDisplay(?string $timeSlot): string
    {
        if ($timeSlot === null || trim($timeSlot) === '') {
            return '—';
        }

        $formatted = collect(preg_split('/\s*-\s*/', trim($timeSlot)) ?: [])
            ->map(function (string $time): string {
                return Carbon::hasFormat($time, 'H:i')
                    ? Carbon::createFromFormat('H:i', $time)->format('g:i A')
                    : $time;
            })
            ->filter()
            ->values();

        return $formatted->isEmpty() ? $timeSlot : $formatted->implode(' – ');
    }

    private function greeting(Carbon $now): string
    {
        return match (true) {
            $now->hour < 12 => 'Good morning',
            $now->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    }
}
