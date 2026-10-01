<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\ServiceTimeslot;
use App\Models\ServiceTimeslotTele;
use App\Models\UnavailableTimeslot;
use App\Models\UnavailableTimeslotTele;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin management of bookable services.
 *
 * One controller drives both service listings because the two tables are
 * identical in shape — `services` + `service_timeslots` for Face to Face and
 * `services_tele` + `service_timeslots_tele` for Telemedicine. A `type` segment
 * selects which pair of tables an action applies to.
 *
 * Two data-model quirks drive most of the care taken below:
 *
 *  1. `services` and `services_tele` have overlapping primary keys (both hold
 *     ids 87 and 88), and `appointments.service_id` points at whichever table
 *     the row's `mode` implies. An id therefore never identifies a service on
 *     its own, which is why the type segment is mandatory on every action and
 *     why service lookups are always resolved against the typed table.
 *  2. `service_timeslots*` rows have no foreign key at all, and
 *     `unavailable_timeslots*.service_id` is `ON DELETE NO ACTION`. Child rows
 *     must therefore be removed by hand before the parent service can be
 *     deleted, or the delete is rejected by MySQL.
 */
class ServiceController extends Controller
{
    /**
     * Per-type configuration. `mode` is the value `appointments.mode` carries
     * for this service type and is what disambiguates `appointments.service_id`.
     *
     * @var array<string, array<string, mixed>>
     */
    private const TYPES = [
        'face' => [
            'label' => 'Face to Face',
            'title' => 'Face to Face Services',
            'route' => 'admin.services.face-to-face',
            'service' => Service::class,
            'timeslot' => ServiceTimeslot::class,
            'unavailable' => UnavailableTimeslot::class,
            'mode' => 'FACE',
            'icon' => 'bi-people-fill',
            'banner_icon' => 'bi-person-video3',
            'description' => 'Consultations booked for in-person visits at the clinic.',
            'empty_hint' => 'Add a face-to-face service to start accepting in-person bookings.',
        ],
        'tele' => [
            'label' => 'Telemedicine',
            'title' => 'Telemedicine Services',
            'route' => 'admin.services.telemedicine',
            'service' => ServiceTele::class,
            'timeslot' => ServiceTimeslotTele::class,
            'unavailable' => UnavailableTimeslotTele::class,
            'mode' => 'TELE',
            'icon' => 'bi-camera-video-fill',
            'banner_icon' => 'bi-camera-video-fill',
            'description' => 'Consultations patients book remotely through video calls.',
            'empty_hint' => 'Add a telemedicine service to start accepting remote bookings.',
        ],
    ];

    /**
     * GET /admin/services/{face-to-face|telemedicine} — service listing.
     */
    public function index(Request $request): View
    {
        $config = $this->config($request->route('type'));

        $services = $config['service']::query()
            ->with('timeslots')
            ->orderBy('service_name')
            ->get();

        $appointmentCounts = Appointment::query()
            ->where('mode', $config['mode'])
            ->whereIn('service_id', $services->pluck('id'))
            ->selectRaw('service_id, COUNT(*) as total')
            ->groupBy('service_id')
            ->pluck('total', 'service_id');

        return view('admin.services', [
            'config' => $config,
            'services' => $services,
            'appointmentCounts' => $appointmentCounts,
            'createMode' => $request->boolean('create'),
            'editServiceId' => $request->integer('edit') ?: null,
            'weekDays' => Service::WEEK_DAYS,
        ]);
    }

    /**
     * POST /admin/services/{type} — create a service and its timeslots.
     */
    public function store(Request $request): RedirectResponse
    {
        $config = $this->config($request->route('type'));

        $validated = $request->validate($this->rules(), [], $this->attributes());

        DB::transaction(function () use ($config, $validated): void {
            $service = new $config['service'];
            $service->service_name = $validated['service_name'];
            $service->homis_code = $validated['homis_code'] ?? null;
            $service->setAvailableDays($validated['availability_day']);
            $service->save();

            $this->syncTimeslots($config, $service, $validated['timeslots'], insertOnly: true);
        });

        return redirect()
            ->route($config['route'])
            ->with('success', $config['label'].' service "'.$validated['service_name'].'" created successfully.');
    }

    /**
     * PUT /admin/services/{type}/{service} — update a service and its timeslots.
     *
     * `type` and `service` are read off the route rather than taken as
     * positional arguments: `type` arrives through route defaults, which
     * Laravel appends after the URI parameters, so positional binding would
     * silently swap the two.
     */
    public function update(Request $request): RedirectResponse
    {
        $config = $this->config($request->route('type'));

        $model = $this->findOrFail($config, $this->serviceId($request));

        $validated = $request->validate($this->rules(), [], $this->attributes());

        DB::transaction(function () use ($config, $model, $validated): void {
            $model->service_name = $validated['service_name'];
            $model->homis_code = $validated['homis_code'] ?? null;
            $model->setAvailableDays($validated['availability_day']);
            $model->save();

            $this->syncTimeslots($config, $model, $validated['timeslots']);
        });

        return redirect()
            ->route($config['route'])
            ->with('success', $config['label'].' service "'.$validated['service_name'].'" updated successfully.');
    }

    /**
     * DELETE /admin/services/{type}/{service} — delete a service.
     *
     * Appointments are never deleted. Because `appointments.service_id` is
     * ambiguous, a service that still has appointments in its own mode is
     * refused outright rather than orphaned — the alternative would either lose
     * booking history or corrupt the other service type that shares the id.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $config = $this->config($request->route('type'));

        $model = $this->findOrFail($config, $this->serviceId($request));

        $appointmentCount = Appointment::query()
            ->where('service_id', $model->getKey())
            ->where('mode', $config['mode'])
            ->count();

        if ($appointmentCount > 0) {
            return redirect()
                ->route($config['route'])
                ->with('error', sprintf(
                    '"%s" cannot be deleted: %d appointment%s still reference it. Reassign or cancel those appointments first.',
                    $model->service_name,
                    $appointmentCount,
                    $appointmentCount === 1 ? '' : 's'
                ));
        }

        DB::transaction(function () use ($config, $model): void {
            // unavailable_timeslots* is ON DELETE NO ACTION, so these must go
            // first or MySQL rejects the parent delete.
            $config['unavailable']::query()->where('service_id', $model->getKey())->delete();

            // service_timeslots* carries no foreign key at all.
            $config['timeslot']::query()->where('service_id', $model->getKey())->delete();

            $model->delete();
        });

        return redirect()
            ->route($config['route'])
            ->with('success', $config['label'].' service "'.$model->service_name.'" deleted successfully.');
    }

    /**
     * Validation rules shared by store and update.
     *
     * `service_name` is deliberately not unique-validated: the live data already
     * contains duplicate names, so a unique rule would block editing them.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'service_name' => ['required', 'string', 'max:100'],
            'homis_code' => ['nullable', 'string', 'max:50'],
            'availability_day' => ['required', 'array', 'min:1'],
            'availability_day.*' => ['string', Rule::in(Service::WEEK_DAYS)],
            'timeslots' => ['required', 'array', 'min:1'],
            'timeslots.*.time_slot' => ['required', 'string', 'max:50', 'distinct'],
            'timeslots.*.slots' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * Readable field names so validation errors do not read
     * "the available_day field is required".
     *
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return [
            'service_name' => 'service name',
            'homis_code' => 'HOMIS code',
            'availability_day' => 'available days',
            'availability_day.*' => 'available day',
            'timeslots' => 'timeslots',
            'timeslots.*.time_slot' => 'time slot',
            'timeslots.*.slots' => 'slot capacity',
        ];
    }

    /**
     * Reconcile stored timeslot rows with the submitted set.
     *
     * Rows are matched on `time_slot` so existing primary keys survive an edit;
     * capacity-only changes update in place, renamed slots are inserted, and
     * slots the admin removed are deleted.
     *
     * @param  array<int, array{time_slot: string, slots: int|string}>  $timeslots
     */
    private function syncTimeslots(array $config, Model $service, array $timeslots, bool $insertOnly = false): void
    {
        /** @var Model $timeslotClass */
        $timeslotClass = $config['timeslot'];

        $existing = $insertOnly
            ? collect()
            : $timeslotClass::query()->where('service_id', $service->getKey())->get()->keyBy('time_slot');

        $keep = [];

        foreach ($timeslots as $row) {
            $label = trim((string) $row['time_slot']);
            $capacity = (int) $row['slots'];

            $current = $existing->get($label);

            if ($current) {
                if ((int) $current->slots !== $capacity) {
                    $current->update(['slots' => $capacity]);
                }

                $keep[] = $current->getKey();

                continue;
            }

            $created = $timeslotClass::create([
                'service_id' => $service->getKey(),
                'time_slot' => $label,
                'slots' => $capacity,
            ]);

            $keep[] = $created->getKey();
        }

        if ($insertOnly) {
            return;
        }

        $timeslotClass::query()
            ->where('service_id', $service->getKey())
            ->whereNotIn('id', $keep)
            ->delete();
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
     * Find a service on the table implied by the type.
     *
     * Never resolved through a shared route-model binding: the ids overlap
     * between the two service tables.
     */
    private function findOrFail(array $config, int $id): Model
    {
        $model = $config['service']::query()->find($id);

        abort_if($model === null, 404);

        return $model;
    }

    /**
     * The `{service}` segment, constrained to a positive integer.
     */
    private function serviceId(Request $request): int
    {
        $id = $request->route('service');

        abort_if(! is_numeric($id) || (int) $id < 1, 404);

        return (int) $id;
    }
}
