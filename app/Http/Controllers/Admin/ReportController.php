<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Filter option => actual database statuses.
     */
    private const STATUS_GROUPS = [
        'Booked' => ['Pending', 'Booked'],
        'Approved' => ['Approved', 'Confirmed', 'In Progress', 'Completed'],
        'Cancelled' => ['Cancelled'],
    ];

    public function index(Request $request): View
    {
        $filters = [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'month' => (string) $request->query('month', ''),
            'year' => (string) $request->query('year', ''),
            'doctor' => (string) $request->query('doctor', ''),
            'type' => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $query = $this->buildQuery($filters);

        $total = (clone $query)->count();
        $approved = (clone $query)->where('status', 'Approved')->count();
        $pending = (clone $query)->where('status', 'Pending')->count();
        $cancelled = (clone $query)->where('status', 'Cancelled')->count();
        $telemedicine = (clone $query)->where('mode', 'TELE')->count();
        $faceToFace = (clone $query)->where('mode', 'FACE')->count();

        $familyMedicine = 0;
        if (Schema::hasTable('services')) {
            $familyServiceIds = Service::whereRaw('LOWER(service_name) LIKE ?', ['%family medicine%'])->pluck('id');
            if ($familyServiceIds->isNotEmpty()) {
                $familyMedicine = (clone $query)->where('mode', 'FACE')->whereIn('service_id', $familyServiceIds)->count();
            }
        }

        $recentAppointments = (clone $query)
            ->with(['patient', 'staff', 'service'])
            ->orderByDesc('date')
            ->orderByDesc('time_slot')
            ->paginate(20);

        $doctorActivity = (clone $query)
            ->selectRaw('staff_id, COUNT(*) as total, SUM(CASE WHEN status = \'Approved\' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = \'Cancelled\' THEN 1 ELSE 0 END) as cancelled')
            ->whereNotNull('staff_id')
            ->groupBy('staff_id')
            ->orderByDesc('total')
            ->paginate(10)
            ->through(function ($row) {
                $staff = Staff::where('id', $row->staff_id)->first();
                $row->doctor_name = $staff
                    ? trim(($staff->FirstName ?? '').' '.($staff->LastName ?? ''))
                    : 'Unassigned';

                return $row;
            });

        return view('admin.reports', [
            'stats' => [
                'total' => $total,
                'approved' => $approved,
                'pending' => $pending,
                'cancelled' => $cancelled,
                'telemedicine' => $telemedicine,
                'face_to_face' => $faceToFace,
                'family_medicine' => $familyMedicine,
            ],
            'filters' => $filters,
            'doctors' => Staff::where('is_active', true)->orderBy('LastName')->orderBy('FirstName')->get(),
            'services' => Service::all(),
            'recentAppointments' => $recentAppointments,
            'doctorActivity' => $doctorActivity,
            'months' => [
                '01' => 'January', '02' => 'February', '03' => 'March',
                '04' => 'April', '05' => 'May', '06' => 'June',
                '07' => 'July', '08' => 'August', '09' => 'September',
                '10' => 'October', '11' => 'November', '12' => 'December',
            ],
            'years' => range(date('Y'), date('Y') - 5),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'month' => (string) $request->query('month', ''),
            'year' => (string) $request->query('year', ''),
            'doctor' => (string) $request->query('doctor', ''),
            'type' => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $appointments = $this->buildQuery($filters)
            ->with(['patient', 'staff', 'service'])
            ->orderByDesc('date')
            ->orderByDesc('time_slot')
            ->get();

        $response = new StreamedResponse(function () use ($appointments): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Patient', 'Doctor', 'Service', 'Date', 'Time', 'Mode', 'Status', 'Reason']);

            foreach ($appointments as $appointment) {
                fputcsv($handle, [
                    $appointment->patient->full_name ?? 'Unknown',
                    $appointment->staff->full_name ?? 'Unassigned',
                    $appointment->service->name ?? 'N/A',
                    $appointment->date?->format('Y-m-d') ?? '',
                    $appointment->time_slot ?? '',
                    $appointment->mode ?? '',
                    $appointment->status ?? '',
                    $appointment->consultation_reason ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="appointments-report-'.date('Y-m-d').'.csv"');

        return $response;
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function buildQuery(array $filters): Builder
    {
        return Appointment::query()
            ->when($filters['date_from'] !== '', fn (Builder $builder): Builder => $builder->whereDate('date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $builder): Builder => $builder->whereDate('date', '<=', $filters['date_to']))
            ->when($filters['month'] !== '', fn (Builder $builder): Builder => $builder->whereMonth('date', $filters['month']))
            ->when($filters['year'] !== '', fn (Builder $builder): Builder => $builder->whereYear('date', $filters['year']))
            ->when($filters['doctor'] !== '', fn (Builder $builder): Builder => $builder->where('staff_id', $filters['doctor']))
            ->when($filters['type'] !== '', function (Builder $builder) use ($filters): Builder {
                if ($filters['type'] === 'telemedicine') {
                    return $builder->where('mode', 'TELE');
                }

                return $builder->where('mode', 'FACE');
            })
            ->when(isset(self::STATUS_GROUPS[$filters['status']]), fn (Builder $builder): Builder => $builder->whereIn('status', self::STATUS_GROUPS[$filters['status']]));
    }
}
