<?php

use App\ConsultationReason;
use App\Models\Admin;
use App\Models\Appointment;
use App\Models\Holiday;
use App\Models\HolidayTele;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\ServiceTimeslot;
use App\Models\ServiceTimeslotTele;
use App\Support\Telemed;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function triagerScheduleTables(): void
{
    if (! Schema::hasTable('appointments')) {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->integer('service_id')->nullable();
            $table->string('complaint')->nullable();
            $table->string('consultation_reason', 100)->nullable();
            $table->json('symptoms')->nullable();
            $table->text('complaint_details')->nullable();
            $table->date('date')->nullable();
            $table->string('time_slot')->nullable();
            $table->string('status')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->char('qr_code_token', 64)->nullable()->unique();
            $table->boolean('reminder_sent')->nullable();
            $table->string('mode')->nullable();
            $table->string('request_mode', 10)->nullable();
            $table->string('meeting_link')->nullable();
            $table->boolean('room_opened')->nullable();
            $table->integer('opened_by')->nullable();
            $table->string('room_opened_by', 20)->nullable();
            $table->string('triager_status', 30)->default('Pending');
            $table->string('triager_action', 50)->nullable();
            $table->text('triager_remarks')->nullable();
            $table->integer('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->integer('staff_id')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services')) {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('availability_day')->nullable();
            $table->string('homis_code')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('services_tele')) {
        Schema::create('services_tele', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('availability_day')->nullable();
            $table->string('homis_code')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('service_timeslots_tele')) {
        Schema::create('service_timeslots_tele', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('service_id');
            $table->string('time_slot');
            $table->integer('slots')->default(1);
        });
    }

    if (! Schema::hasTable('unavailable_timeslots_tele')) {
        Schema::create('unavailable_timeslots_tele', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('service_id');
            $table->date('date');
            $table->string('time_slot');
            $table->string('reason')->nullable();
        });
    }

    if (! Schema::hasTable('holidays_tele')) {
        Schema::create('holidays_tele', function (Blueprint $table) {
            $table->increments('id');
            $table->date('holiday_date');
            $table->string('description')->nullable();
        });
    }

    if (! Schema::hasTable('service_timeslots')) {
        Schema::create('service_timeslots', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('service_id');
            $table->string('time_slot');
            $table->integer('slots')->default(1);
        });
    }

    if (! Schema::hasTable('unavailable_timeslots')) {
        Schema::create('unavailable_timeslots', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('service_id');
            $table->date('date');
            $table->string('time_slot');
            $table->string('reason')->nullable();
        });
    }

    if (! Schema::hasTable('holidays')) {
        Schema::create('holidays', function (Blueprint $table) {
            $table->increments('id');
            $table->date('holiday_date');
            $table->string('description')->nullable();
        });
    }
}

function triagerScheduleAdmin(array $overrides = []): Admin
{
    return Admin::factory()->create(array_merge(['role' => 'triager'], $overrides));
}

function triagerScheduleService(): ServiceTele
{
    return ServiceTele::create([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
        'homis_code' => 'FAM',
    ]);
}

function triagerScheduleFaceService(): Service
{
    return Service::create([
        'service_name' => 'EHWC',
        'availability_day' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
    ]);
}

test('the schedule calendar endpoints are restricted to triagers', function () {
    triagerScheduleTables();
    $month = Carbon::now()->format('Y-m');

    $urls = [
        route('triager.calendar.telemed', [
            'service_id' => triagerScheduleService()->id,
            'month' => $month,
        ]),
        route('triager.calendar.face', [
            'service_id' => triagerScheduleFaceService()->id,
            'month' => $month,
        ]),
    ];

    foreach ($urls as $url) {
        $this->getJson($url)->assertUnauthorized();
    }

    foreach ($urls as $url) {
        $this->actingAs(Admin::factory()->create(['role' => 'admin']), 'admin')
            ->getJson($url)
            ->assertRedirect(route('admin.dashboard'));
    }
});

test('the telemed calendar endpoint marks holidays and open days for the month', function () {
    triagerScheduleTables();
    $service = triagerScheduleService();

    ServiceTimeslotTele::create([
        'service_id' => $service->id,
        'time_slot' => '08:00 - 10:00',
        'slots' => 2,
    ]);

    // Anchor both dates to the first days of next month so the holiday and
    // the open day always land in the same calendar month.
    $nextMonthStart = Carbon::today()->addMonthNoOverflow()->startOfMonth();
    $holidayDate = $nextMonthStart->copy();
    HolidayTele::create([
        'holiday_date' => $holidayDate->toDateString(),
        'description' => 'Founding Anniversary',
    ]);

    $month = $holidayDate->format('Y-m');
    $response = $this->actingAs(triagerScheduleAdmin(), 'admin')
        ->getJson(route('triager.calendar.telemed', [
            'service_id' => $service->id,
            'month' => $month,
        ]))
        ->assertOk()
        ->assertJsonPath('month', $month);

    $days = collect($response->json('days'));
    $holiday = $days->firstWhere('date', $holidayDate->toDateString());
    $openDate = $nextMonthStart->copy()->addDays(3);
    $open = $days->firstWhere('date', $openDate->toDateString());

    expect($holiday)
        ->not->toBeNull()
        ->and($holiday['holiday'])->toBeTrue()
        ->and($holiday['available'])->toBeFalse()
        ->and($holiday['reason'])->toBe('Founding Anniversary')
        ->and($open)
        ->not->toBeNull()
        ->and($open['holiday'])->toBeFalse()
        ->and($open['available'])->toBeTrue();
});

test('the telemed calendar endpoint flags fully booked days', function () {
    triagerScheduleTables();
    $service = triagerScheduleService();

    ServiceTimeslotTele::create([
        'service_id' => $service->id,
        'time_slot' => '08:00 - 10:00',
        'slots' => 1,
    ]);

    $date = Carbon::today()->addDays(2)->startOfDay();
    Appointment::create([
        'patient_id' => 1,
        'service_id' => $service->id,
        'complaint' => 'Fully booked',
        'date' => $date->toDateString(),
        'time_slot' => '08:00 - 10:00',
        'status' => 'Booked',
        'mode' => 'TELE',
    ]);

    $response = $this->actingAs(triagerScheduleAdmin(), 'admin')
        ->getJson(route('triager.calendar.telemed', [
            'service_id' => $service->id,
            'month' => $date->format('Y-m'),
        ]))
        ->assertOk();

    $day = collect($response->json('days'))->firstWhere('date', $date->toDateString());

    expect($day)
        ->not->toBeNull()
        ->and($day['holiday'])->toBeFalse()
        ->and($day['available'])->toBeFalse()
        ->and($day['fully_booked'])->toBeTrue();
});

test('the dashboard schedule button carries the patient details used by the modal', function () {
    triagerScheduleTables();

    $patient = makePatient([
        'dob' => '1980-05-04',
        'gender' => 'Male',
    ]);
    $service = triagerScheduleService();

    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'complaint' => 'Headache',
        'complaint_details' => 'Sakit ng ulo po',
        'request_mode' => 'TELE',
        'triager_status' => 'Approved',
        'date' => null,
        'time_slot' => null,
        'status' => 'Pending',
        'mode' => 'TELE',
    ]);

    $response = $this->actingAs(triagerScheduleAdmin(), 'admin')
        ->get(route('triager.dashboard'))
        ->assertOk()
        ->assertSee('Create Appointment Module');

    $age = Telemed::age('1980-05-04')['display'];

    $response
        ->assertSee('data-patient-age="'.$age.'"', false)
        ->assertSee('data-patient-gender="Male"', false)
        ->assertSee('data-patient-complaint="Sakit ng ulo po"', false);
});

test('the face-to-face calendar endpoint marks holidays and open days for the month', function () {
    triagerScheduleTables();
    $service = triagerScheduleFaceService();

    ServiceTimeslot::create([
        'service_id' => $service->id,
        'time_slot' => '08:00 - 10:00',
        'slots' => 2,
    ]);

    $nextMonthStart = Carbon::today()->addMonthNoOverflow()->startOfMonth();
    $holidayDate = $nextMonthStart->copy();
    Holiday::create([
        'holiday_date' => $holidayDate->toDateString(),
        'description' => 'Clinic Day',
    ]);

    $month = $holidayDate->format('Y-m');
    $response = $this->actingAs(triagerScheduleAdmin(), 'admin')
        ->getJson(route('triager.calendar.face', [
            'service_id' => $service->id,
            'month' => $month,
        ]))
        ->assertOk()
        ->assertJsonPath('service.name', 'EHWC')
        ->assertJsonPath('month', $month);

    $days = collect($response->json('days'));
    $holiday = $days->firstWhere('date', $holidayDate->toDateString());
    $openDate = $nextMonthStart->copy()->addDays(3);
    $open = $days->firstWhere('date', $openDate->toDateString());

    expect($holiday)
        ->not->toBeNull()
        ->and($holiday['holiday'])->toBeTrue()
        ->and($holiday['available'])->toBeFalse()
        ->and($holiday['reason'])->toBe('Clinic Day')
        ->and($open)
        ->not->toBeNull()
        ->and($open['holiday'])->toBeFalse()
        ->and($open['available'])->toBeTrue();
});

test('the dashboard face-to-face schedule button carries the patient details used by the modal', function () {
    triagerScheduleTables();

    $patient = makePatient([
        'dob' => '1991-07-12',
        'gender' => 'Female',
    ]);
    $service = triagerScheduleFaceService();

    Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'consultation_reason' => 'general_check_up',
        'request_mode' => 'FACE',
        'triager_status' => 'Approved',
        'date' => null,
        'time_slot' => null,
        'status' => 'Pending',
        'mode' => 'FACE',
    ]);

    $response = $this->actingAs(triagerScheduleAdmin(), 'admin')
        ->get(route('triager.dashboard'))
        ->assertOk()
        ->assertSee('Face-to-Face Appointment');

    $age = Telemed::age('1991-07-12')['display'];
    $reasonLabel = ConsultationReason::tryFrom('general_check_up')->label();

    $response
        ->assertSee('data-schedule-face="', false)
        ->assertSee('data-patient-age="'.$age.'"', false)
        ->assertSee('data-patient-gender="Female"', false)
        ->assertSee('data-patient-complaint="'.$reasonLabel.'"', false);
});
