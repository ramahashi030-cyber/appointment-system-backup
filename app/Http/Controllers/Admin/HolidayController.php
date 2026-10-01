<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\HolidayTele;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin management of holidays.
 *
 * Holidays were already stored in two separate tables before this screen
 * existed — `holidays` for Face to Face and `holidays_tele` for Telemedicine
 * — and `Support\Telemed::holidaysMap()` already read `holidays_tele` for the
 * patient booking calendar. So rather than introduce a `service_type`
 * discriminator column, the `type` route segment selects which of the two
 * tables an action applies to, mirroring how ServiceController picks between
 * `services` and `services_tele`.
 *
 * That also satisfies the one-date-per-service-type rule structurally: each
 * service type owns its own table, so the same date can never collide within a
 * type while remaining perfectly legal in the other.
 *
 * `holidays` and `holidays_tele` carry overlapping primary keys, so a holiday id
 * never identifies a row on its own. Deletes are therefore resolved against the
 * table the type implies instead of through route-model binding, which would
 * always bind to `holidays` and silently target the wrong table for telemedicine.
 */
class HolidayController extends Controller
{
    /**
     * Per-type configuration. `holiday` is the model and `table` the table its
     * uniqueness is validated against; the URL segment is the array key.
     *
     * @var array<string, array<string, mixed>>
     */
    private const TYPES = [
        'telemedicine' => [
            'label' => 'Telemedicine',
            'title' => 'Manage Holidays (Telemedicine)',
            'holiday' => HolidayTele::class,
            'table' => 'holidays_tele',
            'icon' => 'bi-camera-video-fill',
            'banner_icon' => 'bi-camera-video-fill',
            'description' => 'Holidays for telemedicine services.',
        ],
        'face-to-face' => [
            'label' => 'Face to Face',
            'title' => 'Manage Holidays (Face to Face)',
            'holiday' => Holiday::class,
            'table' => 'holidays',
            'icon' => 'bi-people-fill',
            'banner_icon' => 'bi-person-video3',
            'description' => 'Holidays for face-to-face services.',
        ],
    ];

    /**
     * GET /admin/holidays/{telemedicine|face-to-face} — holiday listing.
     */
    public function index(Request $request): View
    {
        $segment = $request->route('type');
        $config = $this->config($segment);

        $holidays = $this->model($config)::query()
            ->orderBy('holiday_date')
            ->get();

        return view('admin.holidays.index', [
            'config' => $config,
            'serviceType' => $segment,
            'holidays' => $holidays,
        ]);
    }

    /**
     * GET /admin/holidays/{type}/data — AJAX holiday list for the current type.
     */
    public function data(Request $request): JsonResponse
    {
        $config = $this->config($request->route('type'));

        $holidays = $this->model($config)::query()
            ->orderBy('holiday_date')
            ->get(['id', 'holiday_date', 'description'])
            ->map(fn (Holiday|HolidayTele $holiday): array => [
                'id' => $holiday->id,
                'date' => $holiday->holiday_date->format('Y-m-d'),
                'date_formatted' => $holiday->holiday_date->format('M d, Y'),
                'description' => (string) $holiday->description,
            ]);

        return response()->json(['holidays' => $holidays]);
    }

    /**
     * POST /admin/holidays/{type} — add a holiday (AJAX).
     *
     * Uniqueness is checked against the type's own table only, so 2026-12-25
     * may exist once per service type but never twice within one.
     */
    public function store(Request $request): JsonResponse
    {
        $config = $this->config($request->route('type'));

        $validated = $request->validate([
            'holiday_date' => [
                'required',
                'date',
                Rule::unique($config['table'], 'holiday_date'),
            ],
            'description' => ['required', 'string', 'max:255'],
        ], [], [
            'holiday_date' => 'holiday date',
            'description' => 'description',
        ]);

        $holiday = $this->model($config)::create([
            'holiday_date' => $validated['holiday_date'],
            'description' => $validated['description'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Holiday added successfully.',
            'holiday' => [
                'id' => $holiday->id,
                'date' => $holiday->holiday_date->format('Y-m-d'),
                'date_formatted' => $holiday->holiday_date->format('M d, Y'),
                'description' => (string) $holiday->description,
            ],
        ]);
    }

    /**
     * DELETE /admin/holidays/{type}/{holiday} — delete a holiday (AJAX).
     *
     * `holiday` arrives as a raw id rather than a bound model: the two holiday
     * tables share primary keys, so only the type decides which row is meant.
     */
    public function destroy(Request $request): JsonResponse
    {
        $config = $this->config($request->route('type'));

        $holiday = $this->model($config)::query()->find($this->holidayId($request));

        if ($holiday === null) {
            return response()->json([
                'success' => false,
                'message' => 'Holiday not found.',
            ], 404);
        }

        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => 'Holiday deleted successfully.',
        ]);
    }

    /**
     * Resolve the type segment to its table configuration.
     *
     * @return array<string, mixed>
     */
    private function config(?string $type): array
    {
        abort_unless(is_string($type) && isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    /**
     * The model class for the type, as a class-string.
     *
     * @return class-string<Holiday|HolidayTele>
     */
    private function model(array $config): string
    {
        return $config['holiday'];
    }

    /**
     * The `{holiday}` segment, constrained to a positive integer.
     */
    private function holidayId(Request $request): int
    {
        $id = $request->route('holiday');

        abort_if(! is_numeric($id) || (int) $id < 1, 404);

        return (int) $id;
    }
}