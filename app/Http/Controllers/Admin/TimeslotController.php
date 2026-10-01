<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
 *
 * The unavailable tables (unavailable_timeslots / unavailable_timeslots_tele)
 * have their column names detected automatically from the candidates below.
 * If detection fails, the error message lists the columns that were found.
 */
class TimeslotController extends Controller
{
    // services tables
    private const SERVICE_NAME_COLUMN = 'service_name';

    // service_timeslots tables
    private const SLOT_SERVICE_COLUMN = 'service_id';
    private const SLOT_LABEL_COLUMN = 'time_slot';

    // Candidate column names in the unavailable tables (first match wins)
    private const UNAVAILABLE_SLOT_CANDIDATES = ['timeslot_id', 'service_timeslot_id', 'service_timeslots_id', 'slot_id'];
    private const UNAVAILABLE_DATE_CANDIDATES = ['unavailable_date', 'date', 'blocked_date', 'holiday_date'];
    private const UNAVAILABLE_REASON_CANDIDATES = ['reason', 'remarks', 'description'];

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
        $cols = $this->unavailableColumns($config);

        $timeslots = $this->baseQuery($config, $cols)
            ->orderByDesc('u.'.$cols['date'])
            ->orderBy('st.'.self::SLOT_LABEL_COLUMN)
            ->get([
                'u.id as id',
                's.id as service_id',
                's.'.self::SERVICE_NAME_COLUMN.' as service_name',
                'st.id as timeslot_id',
                'st.'.self::SLOT_LABEL_COLUMN.' as time_label',
                'u.'.$cols['date'].' as date',
                $cols['reason'] ? 'u.'.$cols['reason'].' as reason' : DB::raw('NULL as reason'),
            ])
            ->map(fn ($row) => [
                'id' => $row->id,
                'service_id' => $row->service_id,
                'service_name' => $row->service_name,
                'timeslot_id' => $row->timeslot_id,
                'time_label' => $row->time_label,
                'date' => Carbon::parse($row->date)->toDateString(),
                'reason' => $row->reason,
            ])
            ->values();

        return response()->json(['timeslots' => $timeslots]);
    }

    /**
     * JSON: the time slots that belong to one service (fills the dropdown).
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
            ->get(['id', self::SLOT_LABEL_COLUMN.' as label'])
            ->map(fn ($slot) => ['id' => $slot->id, 'label' => $slot->label])
            ->values();

        return response()->json(['slots' => $slots]);
    }

    /**
     * Create an unavailable timeslot.
     */
    public function store(Request $request, string $type): JsonResponse
    {
        $config = $this->typeConfig($type);
        $cols = $this->unavailableColumns($config);

        $data = $request->validate([
            'service_id' => ['required', 'integer', Rule::exists($config['services_table'], 'id')],
            'unavailable_date' => ['required', 'date'],
            'timeslot_id' => [
                'required',
                'integer',
                Rule::exists($config['slots_table'], 'id')->where(self::SLOT_SERVICE_COLUMN, $request->input('service_id')),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->ensureNotDuplicate($config, $cols, (int) $data['timeslot_id'], $data['unavailable_date']);

        $row = [
            $cols['slot'] => $data['timeslot_id'],
            $cols['date'] => $data['unavailable_date'],
        ];

        if ($cols['reason']) {
            $row[$cols['reason']] = $data['reason'] ?? null;
        }

        if ($cols['created_at']) {
            $row['created_at'] = now();
        }

        if ($cols['updated_at']) {
            $row['updated_at'] = now();
        }

        DB::table($config['unavailable_table'])->insert($row);

        return response()->json(['message' => 'Unavailable timeslot added.'], 201);
    }

    /**
     * Update an unavailable timeslot (date, time slot and reason).
     */
    public function update(Request $request, string $type, int $timeslot): JsonResponse
    {
        $config = $this->typeConfig($type);
        $cols = $this->unavailableColumns($config);

        $current = $this->baseQuery($config, $cols)
            ->where('u.id', $timeslot)
            ->first(['u.id as id', 'st.'.self::SLOT_SERVICE_COLUMN.' as service_id']);

        abort_if($current === null, 404);

        $data = $request->validate([
            'unavailable_date' => ['required', 'date'],
            'timeslot_id' => [
                'required',
                'integer',
                Rule::exists($config['slots_table'], 'id')->where(self::SLOT_SERVICE_COLUMN, $current->service_id),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->ensureNotDuplicate($config, $cols, (int) $data['timeslot_id'], $data['unavailable_date'], $timeslot);

        $row = [
            $cols['slot'] => $data['timeslot_id'],
            $cols['date'] => $data['unavailable_date'],
        ];

        if ($cols['reason']) {
            $row[$cols['reason']] = $data['reason'] ?? null;
        }

        if ($cols['updated_at']) {
            $row['updated_at'] = now();
        }

        DB::table($config['unavailable_table'])->where('id', $timeslot)->update($row);

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
     * Unavailable rows joined to their time slot and service, using the table
     * set for this type so Face to Face never touches Telemedicine rows.
     */
    private function baseQuery(array $config, array $cols)
    {
        return DB::table($config['unavailable_table'].' as u')
            ->join($config['slots_table'].' as st', 'st.id', '=', 'u.'.$cols['slot'])
            ->join($config['services_table'].' as s', 's.id', '=', 'st.'.self::SLOT_SERVICE_COLUMN);
    }

    /**
     * Reject the same time slot being blocked twice on the same date.
     */
    private function ensureNotDuplicate(array $config, array $cols, int $timeslotId, string $date, ?int $ignoreId = null): void
    {
        $query = DB::table($config['unavailable_table'])
            ->where($cols['slot'], $timeslotId)
            ->whereDate($cols['date'], $date);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'timeslot_id' => 'This time slot is already marked unavailable on that date.',
            ]);
        }
    }

    /**
     * Work out which columns the unavailable table actually uses.
     *
     * @return array{slot: string, date: string, reason: ?string, created_at: bool, updated_at: bool}
     */
    private function unavailableColumns(array $config): array
    {
        $table = $config['unavailable_table'];
        $existing = Schema::getColumnListing($table);

        $pick = function (array $candidates, string $what, bool $required) use ($existing, $table): ?string {
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $existing, true)) {
                    return $candidate;
                }
            }

            if ($required) {
                throw new RuntimeException(
                    "Table `{$table}` has no {$what} column. Columns found: ".implode(', ', $existing)
                    .'. Expected one of: '.implode(', ', $candidates).'.'
                );
            }

            return null;
        };

        return [
            'slot' => $pick(self::UNAVAILABLE_SLOT_CANDIDATES, 'time slot reference', true),
            'date' => $pick(self::UNAVAILABLE_DATE_CANDIDATES, 'date', true),
            'reason' => $pick(self::UNAVAILABLE_REASON_CANDIDATES, 'reason', false),
            'created_at' => in_array('created_at', $existing, true),
            'updated_at' => in_array('updated_at', $existing, true),
        ];
    }

    private function typeConfig(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }
}