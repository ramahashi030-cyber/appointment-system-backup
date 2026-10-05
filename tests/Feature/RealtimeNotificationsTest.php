<?php

use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceTele;
use App\Models\UserNotification;
use App\Notifications\AppointmentApproved;
use App\Notifications\AppointmentCancelled;
use App\Support\Notifications\AppointmentNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Tables these tests need. Named uniquely because Pest loads every test file
 * in a single PHP process, so helpers must never collide across files.
 */
function realtimeNotificationTables(): void
{
    if (! Schema::hasTable('appointments')) {
        Schema::create('appointments', function ($table): void {
            $table->id();
            $table->integer('patient_id')->nullable();
            $table->integer('staff_id')->nullable();
            $table->integer('service_id')->nullable();
            $table->string('complaint')->nullable();
            $table->date('date')->nullable();
            $table->string('time_slot')->nullable();
            $table->string('status')->nullable();
            $table->string('mode')->nullable();
            $table->string('request_mode', 10)->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('triager_status', 30)->default('Pending');
            $table->string('triager_action', 50)->nullable();
            $table->text('triager_remarks')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services')) {
        Schema::create('services', function ($table): void {
            $table->id();
            $table->string('service_name')->nullable();
            $table->string('availability_day')->nullable();
            $table->string('homis_code')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('services_tele')) {
        Schema::create('services_tele', function ($table): void {
            $table->id();
            $table->string('service_name')->nullable();
            $table->string('availability_day')->nullable();
            $table->string('homis_code')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('admins')) {
        Schema::create('admins', function ($table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();
        });
    }
}

/**
 * Store one unread real-time notification for a notifiable model.
 */
function realtimeNotificationFor(object $notifiable, array $data = [], array $overrides = []): UserNotification
{
    return UserNotification::create(array_merge([
        // The database channel normally supplies the notification's uuid id.
        'id' => (string) Str::uuid(),
        'type' => AppointmentApproved::class,
        'notifiable_type' => $notifiable->getMorphClass(),
        'notifiable_id' => $notifiable->getKey(),
        'data' => array_merge(['title' => 'Appointment Approved', 'message' => 'Your appointment was approved.'], $data),
        'read_at' => null,
    ], $overrides));
}

function actingAsPatient(object $patient): void
{
    test()->withSession(['user_type' => 'patient', 'patient_id' => $patient->getKey()]);
}

test('the notification feed only returns the signed-in patient\'s notifications', function () {
    realtimeNotificationTables();

    $mine = makePatient(['username' => 'feed-mine']);
    $other = makePatient(['username' => 'feed-other']);

    realtimeNotificationFor($mine, ['title' => 'Mine']);
    realtimeNotificationFor($other, ['title' => 'Theirs']);

    actingAsPatient($mine);

    $this->getJson('/notifications')
        ->assertOk()
        ->assertJsonPath('unread', 1)
        ->assertJsonCount(1, 'notifications')
        ->assertJsonPath('notifications.0.title', 'Mine')
        ->assertJsonPath('notifications.0.read', false);
});

test('reading the feed does not mark anything as read', function () {
    realtimeNotificationTables();

    $patient = makePatient(['username' => 'feed-reader']);
    realtimeNotificationFor($patient);
    realtimeNotificationFor($patient);

    actingAsPatient($patient);

    $this->getJson('/notifications')->assertOk()->assertJsonPath('unread', 2);
    // Opening the bell modal only ever issues this GET: nothing is written.
    $this->getJson('/notifications')->assertOk()->assertJsonPath('unread', 2);

    expect(UserNotification::query()->whereNotNull('read_at')->count())->toBe(0);
});

test('marking all as read only clears the caller\'s notifications', function () {
    realtimeNotificationTables();

    $mine = makePatient(['username' => 'read-all-mine']);
    $other = makePatient(['username' => 'read-all-other']);

    realtimeNotificationFor($mine);
    realtimeNotificationFor($mine);
    realtimeNotificationFor($other);

    actingAsPatient($mine);

    $this->postJson('/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('unread', 0)
        ->assertJsonCount(2, 'notifications');

    expect($other->realtimeNotifications()->unread()->count())->toBe(1);
});

test('marking one notification read does not touch another patient\'s notification', function () {
    realtimeNotificationTables();

    $mine = makePatient(['username' => 'read-one-mine']);
    $other = makePatient(['username' => 'read-one-other']);

    $own = realtimeNotificationFor($mine);
    $foreign = realtimeNotificationFor($other);

    actingAsPatient($mine);

    $this->postJson('/notifications/'.$own->getKey().'/read')
        ->assertOk()
        ->assertJsonPath('unread', 0);

    expect($own->fresh()->read_at)->not->toBeNull()
        ->and($foreign->fresh()->read_at)->toBeNull();

    $this->postJson('/notifications/'.$foreign->getKey().'/read')->assertNotFound();

    expect($foreign->fresh()->read_at)->toBeNull();
});

test('broadcast channel authorisation is limited to the caller\'s own private channel', function () {
    realtimeNotificationTables();

    $patient = makePatient(['username' => 'channel-self']);
    $other = makePatient(['username' => 'channel-other']);

    actingAsPatient($patient);

    $own = 'private-user.patient.'.$patient->getKey();
    $theirs = 'private-user.patient.'.$other->getKey();

    // A malformed channel, or somebody else's, is refused outright.
    $this->postJson('/notifications/auth', [
        'channel_name' => 'private-user.patient.'.$other->getKey(),
        'socket_id' => '123.456',
    ])->assertForbidden();

    $this->postJson('/notifications/auth', [
        'channel_name' => 'private-user.staff.'.$patient->getKey(),
        'socket_id' => '123.456',
    ])->assertForbidden();

    $this->postJson('/notifications/auth', [
        'channel_name' => 'private-broadcast-channel',
        'socket_id' => '123.456',
    ])->assertForbidden();

    // The caller's own channel is signed with the configured Reverb app.
    config(['broadcasting.default' => 'reverb']);

    $this->postJson('/notifications/auth', [
        'channel_name' => $own,
        'socket_id' => '123.456',
    ])->assertOk()->assertJsonStructure(['auth']);

    expect($theirs)->not->toBe($own);
});

test('the approval notification stores the real service read from the appointment', function () {
    realtimeNotificationTables();

    $patient = makePatient(['username' => 'service-reader']);
    $service = ServiceTele::create([
        'service_name' => 'Cardiology',
        'availability_day' => 'Mon',
        'homis_code' => 'CAR',
    ]);

    $appointment = Appointment::create([
        'patient_id' => $patient->getKey(),
        'service_id' => $service->getKey(),
        'mode' => 'TELE',
        'date' => Carbon::parse('2026-10-10'),
        'time_slot' => '10:00 - 12:00',
        'status' => 'Approved',
    ]);

    app(AppointmentNotifier::class)->approved($appointment);

    $notification = $patient->realtimeNotifications()
        ->where('type', AppointmentApproved::class)
        ->firstOrFail();

    expect($notification->data['service'])->toBe('Cardiology')
        ->and($notification->data['date'])->toBe('October 10, 2026')
        ->and($notification->data['time'])->toBe('10:00 - 12:00')
        ->and(collect($notification->data)->contains(fn ($value): bool => is_string($value) && str_contains($value, 'Face-to-Face')))->toBeFalse();
});

test('a cancelled appointment notification keeps the actual cancellation reason', function () {
    realtimeNotificationTables();

    $patient = makePatient(['username' => 'cancel-reader']);
    $service = Service::create([
        'service_name' => 'Family Medicine',
        'availability_day' => 'Mon,Tue',
        'homis_code' => 'FAM',
    ]);

    $appointment = Appointment::create([
        'patient_id' => $patient->getKey(),
        'service_id' => $service->getKey(),
        'mode' => 'FACE',
        'date' => Carbon::parse('2026-10-12'),
        'time_slot' => '08:00 - 10:00',
        'status' => 'Cancelled',
        'cancellation_reason' => 'The clinic needed to reschedule the slot.',
    ]);

    app(AppointmentNotifier::class)->cancelled($appointment);

    $notification = $patient->realtimeNotifications()
        ->where('type', AppointmentCancelled::class)
        ->firstOrFail();

    expect($notification->data['reason'])->toBe('The clinic needed to reschedule the slot.')
        ->and($notification->data['service'])->toBe('Family Medicine');
});
