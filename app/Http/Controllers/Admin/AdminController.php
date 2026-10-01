<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientRequest;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Administrative overview and module entry points.
 */
class AdminController extends Controller
{
    /**
     * GET /admin/dashboard.
     */
    public function dashboard(): View
    {
        $today = Carbon::today();
        $stats = $this->dashboardStats($today);

        return view('admin.dashboard', [
            'statCards' => $this->statCards($stats),
            'pendingRequests' => $this->pendingRequests(),
            'quickLinks' => $this->quickLinks(),
        ]);
    }

    public function doctors(): View
    {
        return $this->modulePage(
            'Doctors & Staff',
            'Manage medical staff accounts, roles, and duty information.',
            'bi-person-badge-fill'
        );
    }

    public function appointments(): View
    {
        return $this->modulePage(
            'Appointments',
            'Review appointment requests, upcoming visits, and consultation history.',
            'bi-calendar2-week-fill'
        );
    }

    public function records(): View
    {
        return $this->modulePage(
            'Records',
            'Access patient medical records, prescriptions, and related documents.',
            'bi-file-earmark-medical-fill'
        );
    }

    public function reports(): View
    {
        return $this->modulePage(
            'Reports',
            'Review operational reports and appointment performance trends.',
            'bi-bar-chart-fill'
        );
    }

    public function settings(): View
    {
        return redirect()->route('admin.settings');
    }

    /**
     * @return array<string, int>
     */
    private function dashboardStats(Carbon $today): array
    {
        $appointmentsAvailable = Schema::hasTable('appointments');

        return [
            'totalPatients' => Schema::hasTable('patients') ? Patient::count() : 0,
            'totalStaff' => Schema::hasTable('staff') ? Staff::count() : 0,
            'todayAppointments' => $appointmentsAvailable
                ? Appointment::whereDate('date', $today->toDateString())->count()
                : 0,
            'upcomingAppointments' => $appointmentsAvailable
                ? Appointment::where('status', 'Booked')
                    ->whereDate('date', '>=', $today->toDateString())
                    ->count()
                : 0,
            'completedAppointments' => $appointmentsAvailable
                ? Appointment::where('status', 'Completed')->count()
                : 0,
            'cancelledAppointments' => $appointmentsAvailable
                ? Appointment::where('status', 'Cancelled')->count()
                : 0,
            'telemedicineAppointments' => $appointmentsAvailable
                ? Appointment::where('mode', 'TELE')->count()
                : 0,
            'familyMedicineAppointments' => $this->familyMedicineAppointmentCount(),
        ];
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<int, array{label: string, value: int, icon: string, tone: string}>
     */
    private function statCards(array $stats): array
    {
        return [
            ['label' => 'Total Registered Patients', 'value' => $stats['totalPatients'], 'icon' => 'bi-people-fill', 'tone' => 'blue'],
            ['label' => 'Doctors / Medical Staff', 'value' => $stats['totalStaff'], 'icon' => 'bi-person-plus-fill', 'tone' => 'purple'],
            ['label' => "Today's Appointments", 'value' => $stats['todayAppointments'], 'icon' => 'bi-journal-text', 'tone' => 'green'],
            ['label' => 'Upcoming Appointments', 'value' => $stats['upcomingAppointments'], 'icon' => 'bi-clock-fill', 'tone' => 'orange'],
            ['label' => 'Completed Appointments', 'value' => $stats['completedAppointments'], 'icon' => 'bi-check-lg', 'tone' => 'green'],
            ['label' => 'Cancelled Appointments', 'value' => $stats['cancelledAppointments'], 'icon' => 'bi-x-lg', 'tone' => 'red'],
            ['label' => 'Telemedicine Appointments', 'value' => $stats['telemedicineAppointments'], 'icon' => 'bi-camera-video-fill', 'tone' => 'cyan'],
            ['label' => 'Family Medicine Appointments', 'value' => $stats['familyMedicineAppointments'], 'icon' => 'bi-plus-square-fill', 'tone' => 'purple'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function pendingRequests(): array
    {
        if (! Schema::hasTable('request')) {
            return [];
        }

        return PatientRequest::with('patient')
            ->whereRaw('LOWER(status) = ?', ['pending'])
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (PatientRequest $request): array {
                $patientName = $this->patientName($request->patient);

                return [
                    'initials' => $this->initials($patientName),
                    'patient_name' => $patientName ?: 'Unknown patient',
                    'service' => (string) ($request->consult_type ?: 'General consultation'),
                    'requested' => $this->relativeDate($request->created_at),
                    'status' => ucfirst((string) $request->status),
                ];
            })
            ->all();
    }

    private function familyMedicineAppointmentCount(): int
    {
        if (! Schema::hasTable('appointments')) {
            return 0;
        }

        $faceServiceIds = Schema::hasTable('services')
            ? Service::whereRaw('LOWER(service_name) LIKE ?', ['%family medicine%'])->pluck('id')
            : collect();
        $teleServiceIds = Schema::hasTable('services_tele')
            ? DB::table('services_tele')
                ->whereRaw('LOWER(service_name) LIKE ?', ['%family medicine%'])
                ->pluck('id')
            : collect();

        if ($faceServiceIds->isEmpty() && $teleServiceIds->isEmpty()) {
            return 0;
        }

        return Appointment::query()
            ->where(function (Builder $query) use ($faceServiceIds, $teleServiceIds): void {
                if ($faceServiceIds->isNotEmpty()) {
                    $query->orWhere(function (Builder $faceQuery) use ($faceServiceIds): void {
                        $faceQuery->where('mode', 'FACE')->whereIn('service_id', $faceServiceIds);
                    });
                }

                if ($teleServiceIds->isNotEmpty()) {
                    $query->orWhere(function (Builder $teleQuery) use ($teleServiceIds): void {
                        $teleQuery->where('mode', 'TELE')->whereIn('service_id', $teleServiceIds);
                    });
                }
            })
            ->count();
    }

    private function patientName(?Patient $patient): string
    {
        if ($patient === null) {
            return '';
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

    private function relativeDate(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $today = Carbon::today();

        if ($date->isSameDay($today)) {
            return 'Today';
        }

        if ($date->isSameDay($today->copy()->subDay())) {
            return 'Yesterday';
        }

        return $date->format('M j, Y');
    }

    /**
     * @return array<int, array{route: string, label: string, description: string, icon: string, tone: string}>
     */
    private function quickLinks(): array
    {
        return [
            ['route' => 'admin.patients', 'label' => 'Manage Patients', 'description' => 'Add, edit, or view records', 'icon' => 'bi-people-fill', 'tone' => 'blue'],
            ['route' => 'admin.doctors', 'label' => 'Manage Doctors', 'description' => 'Staff roster and schedules', 'icon' => 'bi-person-badge-fill', 'tone' => 'purple'],
            ['route' => 'admin.appointments', 'label' => 'Appointments', 'description' => 'Review and approve requests', 'icon' => 'bi-calendar2-week-fill', 'tone' => 'green'],
            ['route' => 'admin.records', 'label' => 'Records', 'description' => 'Prescriptions & results', 'icon' => 'bi-file-earmark-medical-fill', 'tone' => 'orange'],
        ];
    }

    private function modulePage(string $title, string $description, string $icon): View
    {
        return view('admin.module', [
            'moduleTitle' => $title,
            'moduleDescription' => $description,
            'moduleIcon' => $icon,
        ]);
    }
}
