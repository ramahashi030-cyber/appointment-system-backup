<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentCheckin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Admin listing of every kiosk scan (valid, refused and failed alike).
 */
class KioskHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $result = (string) $request->query('result', '');
        $date = (string) $request->query('date', '');

        $logs = AppointmentCheckin::query()
            ->with('appointment.patient')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('hospital_number', 'like', "%{$search}%")
                        ->orWhere('kiosk_name', 'like', "%{$search}%");
                });
            })
            ->when($result !== '', fn (Builder $query): Builder => $query->where('result', $result))
            ->when($this->isDate($date), fn (Builder $query): Builder => $query->whereBetween('scanned_at', $this->dayRange($date)))
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends(['search' => $search, 'result' => $result, 'date' => $date]);

        return view('admin.kiosk-history', [
            'logs' => $logs,
            'filters' => ['search' => $search, 'result' => $result, 'date' => $date],
            'results' => ['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed'],
        ]);
    }

    private function isDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }

    /**
     * The scanned day as a UTC range, since the date filter means a Manila
     * calendar day while scanned_at is stored in UTC.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dayRange(string $date): array
    {
        $day = Carbon::createFromFormat('Y-m-d', $date, (string) config('app.display_timezone', 'Asia/Manila'))->startOfDay();

        return [$day->copy()->setTimezone('UTC'), $day->copy()->addDay()->setTimezone('UTC')];
    }
}
