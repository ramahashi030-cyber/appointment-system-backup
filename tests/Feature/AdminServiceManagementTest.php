<?php

use App\Models\Admin;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\ServiceTimeslot;
use App\Models\ServiceTimeslotTele;
use App\Models\UnavailableTimeslotTele;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Schema for the service tables on sqlite.
 *
 * `availability_day` is a SET column on MySQL but has no sqlite equivalent, so
 * it is stored as the same comma separated string. The canonical ordering that
 * the trait guarantees is therefore exercised here too.
 */
function serviceManagementTables(): void
{
    Schema::create('services', function (Blueprint $table): void {
        $table->id();
        $table->string('service_name');
        $table->string('availability_day')->nullable();
        $table->string('homis_code')->nullable();
        $table->timestamp('created_at')->nullable();
    });

    Schema::create('services_tele', function (Blueprint $table): void {
        $table->id();
        $table->string('service_name');
        $table->string('availability_day')->nullable();
        $table->string('homis_code')->nullable();
        $table->timestamp('created_at')->nullable();
    });

    Schema::create('service_timeslots', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('service_id');
        $table->string('time_slot', 50);
        $table->integer('slots');
    });

    Schema::create('service_timeslots_tele', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('service_id');
        $table->string('time_slot', 50);
        $table->integer('slots');
    });

    Schema::create('unavailable_timeslots', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('service_id');
        $table->date('date');
        $table->string('time_slot', 20);
        $table->string('reason')->nullable();
    });

    Schema::create('unavailable_timeslots_tele', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('service_id');
        $table->date('date');
        $table->string('time_slot', 20);
        $table->string('reason')->nullable();
    });

    Schema::create('appointments', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('patient_id')->nullable();
        $table->unsignedBigInteger('service_id')->nullable();
        $table->string('mode')->nullable();
        $table->date('date')->nullable();
        $table->string('time_slot')->nullable();
        $table->string('status')->default('Booked');
        $table->timestamps();
    });
}

function serviceAdmin(): Admin
{
    return Admin::factory()->create();
}

function makeFaceService(array $overrides = []): Service
{
    return Service::create(array_merge([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri',
    ], $overrides));
}

function makeTeleService(array $overrides = []): ServiceTele
{
    return ServiceTele::create(array_merge([
        'service_name' => 'Telemedicine Consult',
        'availability_day' => 'Mon,Tue',
    ], $overrides));
}

function addTimeslot(Service|ServiceTele $service, string $timeSlot, int $slots): void
{
    $timeslotClass = $service instanceof Service ? ServiceTimeslot::class : ServiceTimeslotTele::class;

    $timeslotClass::create([
        'service_id' => $service->id,
        'time_slot' => $timeSlot,
        'slots' => $slots,
    ]);
}

function validServicePayload(array $overrides = []): array
{
    return array_merge([
        'service_name' => 'Family Medicine',
        'homis_code' => 'MFAM',
        'availability_day' => ['Mon', 'Wed', 'Fri'],
        'timeslots' => [
            ['time_slot' => '08:00 - 10:00', 'slots' => 6],
            ['time_slot' => '10:00 - 12:00', 'slots' => 4],
        ],
    ], $overrides);
}

/* ---------------------------------------------------------------- listings */

test('the face to face listing shows id, name, available days and timeslot slots', function (): void {
    serviceManagementTables();

    $service = makeFaceService(['service_name' => 'EHWC', 'availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun']);
    addTimeslot($service, '08:00 - 10:00', 6);

    $this->actingAs(serviceAdmin(), 'admin')
        ->get(route('admin.services.face-to-face'))
        ->assertOk()
        ->assertSee('EHWC')
        ->assertSee('Mon')
        ->assertSee('Sun')
        ->assertSee('08:00 - 10:00')
        ->assertSee('>ID<', false)
        ->assertSee('Available Days', false)
        ->assertSee('Timeslot Slots', false)
        ->assertSee('Add Service', false)
        ->assertSee('id="addServiceModal"', false)
        ->assertSee('id="editServiceModal"', false)
        ->assertSee('id="deleteServiceModal"', false);
});

test('the telemedicine listing uses the same design and reads only its own table', function (): void {
    serviceManagementTables();

    $tele = makeTeleService(['service_name' => 'Tele EHWC', 'availability_day' => 'Sat,Sun']);
    addTimeslot($tele, '20:00 - 21:00', 15);
    makeFaceService(['service_name' => 'Face Only Service']);

    $this->actingAs(serviceAdmin(), 'admin')
        ->get(route('admin.services.telemedicine'))
        ->assertOk()
        ->assertSee('Tele EHWC')
        ->assertSee('20:00 - 21:00')
        ->assertDontSee('Face Only Service')
        ->assertSee('Available Days', false)
        ->assertSee('Timeslot Slots', false);
});

/* ----------------------------------------------------------------- sidebar */

test('the admin sidebar exposes one services dropdown with both children', function (): void {
    serviceManagementTables();

    $this->actingAs(serviceAdmin(), 'admin')
        ->get(route('admin.services.telemedicine'))
        ->assertOk()
        ->assertSee('admin-sidebar-group', false)
        ->assertSee('>Services<', false)
        ->assertSee(route('admin.services.face-to-face'), false)
        ->assertSee(route('admin.services.telemedicine'), false)
        ->assertSee('admin-sidebar-sublink active', false);
});

test('the services dropdown stays collapsed on unrelated admin pages', function (): void {
    serviceManagementTables();

    $this->actingAs(serviceAdmin(), 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('admin-sidebar-group-toggle', false)
        ->assertSee('aria-expanded="false"', false);
});

/* -------------------------------------------------------------------- add */

test('an admin can add a face to face service with days and timeslot capacities', function (): void {
    serviceManagementTables();

    $this->actingAs(serviceAdmin(), 'admin')
        ->post(route('admin.services.face-to-face.store'), validServicePayload([
            'service_name' => 'New Face Service',
            'availability_day' => ['Fri', 'Mon', 'Wed'],
        ]))
        ->assertRedirect(route('admin.services.face-to-face'));

    $service = Service::where('service_name', 'New Face Service')->sole();

    // Days are stored in canonical week order regardless of submission order.
    expect($service->availability_day)->toBe('Mon,Wed,Fri')
        ->and($service->homis_code)->toBe('MFAM')
        ->and($service->timeslots)->toHaveCount(2)
        ->and($service->timeslots->sum('slots'))->toBe(10);

    expect(ServiceTele::count())->toBe(0);
});

test('an admin can add a telemedicine service to the telemedicine tables only', function (): void {
    serviceManagementTables();

    $this->actingAs(serviceAdmin(), 'admin')
        ->post(route('admin.services.telemedicine.store'), validServicePayload([
            'service_name' => 'New Tele Service',
        ]))
        ->assertRedirect(route('admin.services.telemedicine'));

    $service = ServiceTele::where('service_name', 'New Tele Service')->sole();

    expect($service->availability_day)->toBe('Mon,Wed,Fri')
        ->and($service->timeslots)->toHaveCount(2)
        ->and(Service::count())->toBe(0)
        ->and(ServiceTimeslot::count())->toBe(0);
});

/* -------------------------------------------------------------------- edit */

test('an admin can edit a service name, available days and timeslot capacities', function (): void {
    serviceManagementTables();

    $service = makeFaceService();
    addTimeslot($service, '08:00 - 10:00', 6);
    addTimeslot($service, '10:00 - 12:00', 6);

    $this->actingAs(serviceAdmin(), 'admin')
        ->put(route('admin.services.face-to-face.update', $service), validServicePayload([
            'service_name' => 'Renamed Service',
            'homis_code' => '',
            'availability_day' => ['Sun', 'Tue'],
            'timeslots' => [
                ['time_slot' => '08:00 - 10:00', 'slots' => 9],
                ['time_slot' => '14:00 - 16:00', 'slots' => 2],
            ],
        ]))
        ->assertRedirect(route('admin.services.face-to-face'))
        ->assertSessionHas('success');

    $service->refresh();

    expect($service->service_name)->toBe('Renamed Service')
        ->and($service->availability_day)->toBe('Tue,Sun')
        // A blank HOMIS code clears the column rather than keeping stale data.
        ->and($service->homis_code)->toBeNull()
        ->and($service->timeslots)->toHaveCount(2)
        ->and($service->timeslots->firstWhere('time_slot', '08:00 - 10:00')?->slots)->toBe(9)
        ->and($service->timeslots->firstWhere('time_slot', '14:00 - 16:00'))->not->toBeNull()
        ->and($service->timeslots->firstWhere('time_slot', '10:00 - 12:00'))->toBeNull();
});

test('editing keeps existing timeslot rows instead of recreating them', function (): void {
    serviceManagementTables();

    $service = makeFaceService();
    addTimeslot($service, '08:00 - 10:00', 6);

    $originalId = $service->timeslots()->sole()->id;

    $this->actingAs(serviceAdmin(), 'admin')
        ->put(route('admin.services.face-to-face.update', $service), validServicePayload([
            'timeslots' => [['time_slot' => '08:00 - 10:00', 'slots' => 3]],
        ]));

    expect($service->timeslots()->sole()->id)->toBe($originalId)
        ->and($service->timeslots()->sole()->slots)->toBe(3);
});

test('editing a telemedicine service leaves the face to face table untouched', function (): void {
    serviceManagementTables();

    $face = makeFaceService(['service_name' => 'Face Service']);
    $tele = makeTeleService(['service_name' => 'Tele Service']);

    // Both tables deliberately share an id here.
    expect($face->id)->toBe($tele->id);

    $this->actingAs(serviceAdmin(), 'admin')
        ->put(route('admin.services.telemedicine.update', $tele), validServicePayload([
            'service_name' => 'Tele Service Renamed',
        ]))
        ->assertRedirect(route('admin.services.telemedicine'));

    expect($tele->refresh()->service_name)->toBe('Tele Service Renamed')
        ->and($face->refresh()->service_name)->toBe('Face Service');
});

/* ------------------------------------------------------------------ delete */

test('an admin can delete an unused service along with its timeslots', function (): void {
    serviceManagementTables();

    $service = makeFaceService();
    addTimeslot($service, '08:00 - 10:00', 6);

    $this->actingAs(serviceAdmin(), 'admin')
        ->delete(route('admin.services.face-to-face.destroy', $service))
        ->assertRedirect(route('admin.services.face-to-face'))
        ->assertSessionHas('success');

    expect(Service::count())->toBe(0)
        ->and(ServiceTimeslot::where('service_id', $service->id)->count())->toBe(0);
});

test('deleting a service also removes its unavailable timeslot rows', function (): void {
    serviceManagementTables();

    $service = makeTeleService();
    UnavailableTimeslotTele::create([
        'service_id' => $service->id,
        'date' => '2026-10-05',
        'time_slot' => '08:00 - 10:00',
        'reason' => 'Provider away',
    ]);

    $this->actingAs(serviceAdmin(), 'admin')
        ->delete(route('admin.services.telemedicine.destroy', $service))
        ->assertRedirect(route('admin.services.telemedicine'));

    // unavailable_timeslots* is ON DELETE NO ACTION, so these must be removed
    // explicitly or the parent delete is rejected.
    expect(ServiceTele::count())->toBe(0)
        ->and(UnavailableTimeslotTele::where('service_id', $service->id)->count())->toBe(0);
});

test('a service referenced by appointments is never deleted', function (): void {
    serviceManagementTables();

    $service = makeFaceService();
    addTimeslot($service, '08:00 - 10:00', 6);

    $appointment = Appointment::create([
        'service_id' => $service->id,
        'mode' => 'FACE',
        'date' => '2026-10-05',
        'time_slot' => '08:00 - 10:00',
    ]);

    $this->actingAs(serviceAdmin(), 'admin')
        ->delete(route('admin.services.face-to-face.destroy', $service))
        ->assertRedirect(route('admin.services.face-to-face'))
        ->assertSessionHas('error');

    expect(Service::whereKey($service->id)->exists())->toBeTrue()
        ->and(Appointment::whereKey($appointment->id)->exists())->toBeTrue();
});

test('deleting a face service does not affect telemedicine appointments sharing its id', function (): void {
    serviceManagementTables();

    $face = makeFaceService(['service_name' => 'Face Service']);
    $tele = makeTeleService(['service_name' => 'Tele Service']);

    // An id present in both tables, booked in both modes.
    $teleAppointment = Appointment::create([
        'service_id' => $face->id,
        'mode' => 'TELE',
        'date' => '2026-10-05',
        'time_slot' => '08:00 - 10:00',
    ]);

    $this->actingAs(serviceAdmin(), 'admin')
        ->delete(route('admin.services.face-to-face.destroy', $face))
        ->assertRedirect(route('admin.services.face-to-face'));

    // The face service had no FACE appointments, so it is removed; the TELE
    // appointment that reuses the same id must survive untouched.
    expect(Service::whereKey($face->id)->exists())->toBeFalse()
        ->and(ServiceTele::whereKey($tele->id)->exists())->toBeTrue()
        ->and(Appointment::whereKey($teleAppointment->id)->exists())->toBeTrue();
});

/* ------------------------------------------------------------- validation */

test('creating a service requires a name, at least one day and at least one timeslot', function (): void {
    serviceManagementTables();

    $this->actingAs(serviceAdmin(), 'admin')
        ->post(route('admin.services.face-to-face.store'), [
            'service_name' => '',
            'availability_day' => [],
            'timeslots' => [],
        ])
        ->assertSessionHasErrors(['service_name', 'availability_day', 'timeslots']);

    expect(Service::count())->toBe(0)
        ->and(ServiceTimeslot::count())->toBe(0);
});

test('timeslot capacities cannot be negative and time slots must be unique', function (): void {
    serviceManagementTables();

    $this->actingAs(serviceAdmin(), 'admin')
        ->post(route('admin.services.face-to-face.store'), validServicePayload([
            'timeslots' => [
                ['time_slot' => '08:00 - 10:00', 'slots' => -1],
                ['time_slot' => '08:00 - 10:00', 'slots' => 2],
            ],
        ]))
        ->assertSessionHasErrors(['timeslots.0.slots', 'timeslots.1.time_slot']);

    expect(Service::count())->toBe(0);
});

test('a service with a duplicated name stays editable', function (): void {
    serviceManagementTables();

    // The production table already contains two rows named FAMILY MEDICINE, so a
    // unique rule would make the second one impossible to save.
    $first = makeFaceService(['service_name' => 'FAMILY MEDICINE']);
    $second = makeFaceService(['service_name' => 'FAMILY MEDICINE']);

    $this->actingAs(serviceAdmin(), 'admin')
        ->put(route('admin.services.face-to-face.update', $second), validServicePayload([
            'service_name' => 'FAMILY MEDICINE',
            'availability_day' => ['Mon', 'Tue'],
        ]))
        ->assertRedirect(route('admin.services.face-to-face'))
        ->assertSessionHasNoErrors();

    expect($second->refresh()->availability_day)->toBe('Mon,Tue')
        ->and($first->refresh()->availability_day)->toBe('Mon,Tue,Wed,Thu,Fri');
});

test('unknown days are rejected', function (): void {
    serviceManagementTables();

    $this->actingAs(serviceAdmin(), 'admin')
        ->post(route('admin.services.face-to-face.store'), validServicePayload([
            'availability_day' => ['Mon', 'NotADay'],
        ]))
        ->assertSessionHasErrors('availability_day.1');

    expect(Service::count())->toBe(0);
});

/* ------------------------------------------------------------- protection */

test('the service pages require an authenticated admin', function (): void {
    serviceManagementTables();

    $this->get(route('admin.services.face-to-face'))->assertRedirect();
    $this->get(route('admin.services.telemedicine'))->assertRedirect();
    $this->post(route('admin.services.face-to-face.store'), validServicePayload())->assertRedirect();
});

test('an unknown service id returns a 404 rather than touching another table', function (): void {
    serviceManagementTables();

    makeFaceService();
    makeTeleService();

    $this->actingAs(serviceAdmin(), 'admin')
        ->delete(route('admin.services.face-to-face.destroy', 9999))
        ->assertNotFound();

    expect(Service::count())->toBe(1)
        ->and(ServiceTele::count())->toBe(1);
});

/* ------------------------------------------------------ availability days */

test('availability days decode to canonical week order', function (): void {
    $service = new Service(['service_name' => 'Test']);

    expect($service->availableDays())->toBe([]);

    $service->setAvailableDays(['Sun', 'Wed', 'Mon', 'Wed', 'Nonsense']);

    expect($service->availability_day)->toBe('Mon,Wed,Sun')
        ->and($service->availableDays())->toBe(['Mon', 'Wed', 'Sun']);
});
