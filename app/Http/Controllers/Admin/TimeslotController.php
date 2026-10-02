<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin: manage unavailable timeslots for Face to Face and Telemedicine.
 *
 * Like Services, each type has its own set of tables (`services` for face to
 * face, `services_tele` for telemedicine), so the type always comes from the
 * route's `type` default and is never inferred from an id.
 *
 * Confirmed from the database:
 *   services / services_tele                    id, service_name, availability_day, created_at, homis_code
 *   service_timeslots / service_timeslots_tele  id, service_id, time_slot ("08:00 - 10:00"), slots
 *   unavailable_timeslots / ..._tele            id, service_id, date, time_slot ("08:00 - 10:00"), reason
 *
 * The unavailable tables store the time slot as its text label (not an id), so
 * the page's `timeslot_id` field carries that label. The slots() endpoint
 * returns the label as each option's value to match.
 */
class TimeslotController extends Controller
{
    // services tables
    private const SERVICE_NAME_COLUMN = 'service_name';

    // service_timeslots tables
    private const SLOT_SERVICE_COLUMN = 'service_id';
    private const SLOT_LABEL_COLUMN = 'time_slot';

    /**
     * Route `type` segment => page text and the tables for that type.
     */
    private const TYPES = [
        'face-to-face' => [
            'title' => 'Unavailable Timeslots (Face to Face)',
            'description' => 'Block specific time slots on specific dates for face to face services.',
            'banner_icon' => 'bi-clock-fill',
            'icon' => 'bi-people-fill',
            'services_table' => 'services',
            'slots_table' => 'service_timeslots',
            'unavailable_table' => 'unavailable_timeslots',
        ],
        'telemedicine' => [
            'title' => 'Unavailable Timeslots (Telemedicine)',
            'description' => 'Block specific time slots on specific dates for telemedicine services.',
            'banner_icon' => 'bi-clock-fill',
            'icon' => 'bi-camera-video-fill',
            'services_table' => 'services_tele',
            'slots_table' => 'service_timeslots_tele',
            'unavailable_table' => 'unavailable_timeslots_tele',
        ],
    ];

    /**
     * Page.
     */
    public function index(string $type)
    {
        $config = $this->typeConfig($type);

        $services = DB::table($config['services_table'])
            ->orderBy(self::SERVICE_NAME_COLUMN)
            ->get(['id', self::SERVICE_NAME_COLUMN.' as name']);

        return view('admin.timeslots', [
            'config' => $config,
            'serviceType' => $type,
            'services' => $services,
        ]);
    }

    /**
     * JSON: all unavailable timeslots for this type.
     */
    public function data(string $type): JsonResponse
    {
        $config = $this->typeConfig($type);

        $timeslots = DB::table($config['unavailable_table'].' as u')
            ->leftJoin($config['services_table'].' as s', 's.id', '=', 'u.service_id')
            ->orderByDesc('u.date')
            ->orderBy('u.time_slot')
            ->get([
                'u.id as id',
                'u.service_id as service_id',
                's.'.self::SERVICE_NAME_COLUMN.' as service_name',
                'u.time_slot as time_label',
                'u.date as date',
                'u.reason as reason',
            ])
            ->map(fn ($row) => [
                'id' => $row->id,
                'service_id' => $row->service_id,
                'service_name' => $row->service_name ?? 'Unknown service',
                'timeslot_id' => $row->time_label,
                'time_label' => $row->time_label,
                'date' => Carbon::parse($row->date)->toDateString(),
                'reason' => $row->reason,
            ])
            ->values();

        return response()->json(['timeslots' => $timeslots]);
    }

    /**
     * JSON: the time slots that belong to one service (fills the dropdown).
     * The label is used as the option value because the unavailable tables
     * store the time slot text.
     */
    public function slots(Request $request, string $type): JsonResponse
    {
        $config = $this->typeConfig($type);

        $serviceId = (int) $request->query('service_id');

        abort_unless(
            DB::table($config['services_table'])->where('id', $serviceId)->exists(),
            404
        );

        $slots = DB::table($config['slots_table'])
            ->where(self::SLOT_SERVICE_COLUMN, $serviceId)
            ->orderBy(self::SLOT_LABEL_COLUMN)
            ->pluck(self::SLOT_LABEL_COLUMN)
            ->unique()
            ->map(fn ($label) => ['id' => $label, 'label' => $label])
            ->values();

        return response()->json(['slots' => $slots]);
    }

    /**
     * Create an unavailable timeslot.
     */
    public function store(Request $request, string $type): JsonResponse
    {
        $config = $this->typeConfig($type);

        $data = $request->validate([
            'service_id' => ['required', 'integer', Rule::exists($config['services_table'], 'id')],
            'unavailable_date' => ['required', 'date'],
            'timeslot_id' => [
                'required',
                'string',
                'max:50',
                Rule::exists($config['slots_table'], self::SLOT_LABEL_COLUMN)
                    ->where(self::SLOT_SERVICE_COLUMN, $request->input('service_id')),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $date = Carbon::parse($data['unavailable_date'])->toDateString();

        $this->ensureNotDuplicate($config, (int) $data['service_id'], $data['timeslot_id'], $date);

        DB::table($config['unavailable_table'])->insert([
            'service_id' => $data['service_id'],
            'date' => $date,
            'time_slot' => $data['timeslot_id'],
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json(['message' => 'Unavailable timeslot added.'], 201);
    }

    /**
     * Update an unavailable timeslot (date, time slot and reason).
     */
    public function update(Request $request, string $type, int $timeslot): JsonResponse
    {
        $config = $this->typeConfig($type);

        $current = DB::table($config['unavailable_table'])->where('id', $timeslot)->first(['id', 'service_id']);

        abort_if($current === null, 404);

        $data = $request->validate([
            'unavailable_date' => ['required', 'date'],
            'timeslot_id' => [
                'required',
                'string',
                'max:50',
                Rule::exists($config['slots_table'], self::SLOT_LABEL_COLUMN)
                    ->where(self::SLOT_SERVICE_COLUMN, $current->service_id),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $date = Carbon::parse($data['unavailable_date'])->toDateString();

        $this->ensureNotDuplicate($config, (int) $current->service_id, $data['timeslot_id'], $date, $timeslot);

        DB::table($config['unavailable_table'])->where('id', $timeslot)->update([
            'date' => $date,
            'time_slot' => $data['timeslot_id'],
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json(['message' => 'Unavailable timeslot updated.']);
    }

    /**
     * Delete an unavailable timeslot.
     */
    public function destroy(string $type, int $timeslot): JsonResponse
    {
        $config = $this->typeConfig($type);

        abort_unless(
            DB::table($config['unavailable_table'])->where('id', $timeslot)->exists(),
            404
        );

        DB::table($config['unavailable_table'])->where('id', $timeslot)->delete();

        return response()->json(['message' => 'Unavailable timeslot deleted.']);
    }

    /**
     * Reject the same service time slot being blocked twice on the same date.
     */
    private function ensureNotDuplicate(array $config, int $serviceId, string $timeSlot, string $date, ?int $ignoreId = null): void
    {
        $query = DB::table($config['unavailable_table'])
            ->where('service_id', $serviceId)
            ->where('time_slot', $timeSlot)
            ->whereDate('date', $date);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'timeslot_id' => 'This time slot is already marked unavailable on that date.',
            ]);
        }
    }

    private function typeConfig(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }
}