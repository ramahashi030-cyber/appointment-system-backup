<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * The navbar is patient-only. These cases render /telemed/my-appointments
 * with and without a patient session to verify the shared menu and avatar.
 */
function navbarTelemedTables(): void
{
    if (! Schema::hasTable('appointments')) {
        Schema::create('appointments', function ($table) {
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
            $table->string('meeting_link')->nullable();
            $table->boolean('room_opened')->nullable();
            $table->integer('opened_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('services_tele')) {
        Schema::create('services_tele', function ($table) {
            $table->increments('id');
            $table->string('service_name')->nullable();
            $table->string('availability_day')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('service_timeslots_tele')) {
        Schema::create('service_timeslots_tele', function ($table) {
            $table->increments('id');
            $table->integer('service_id')->nullable();
            $table->string('time_slot')->nullable();
            $table->integer('slots')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}

test('a patient keeps the patient menu', function () {
    navbarTelemedTables();

    $patient = makePatient();

    $this->withSession(['patient_id' => $patient->id])
        ->get('/telemed/my-appointments')
        ->assertOk()
        ->assertSee('href="/telemed/my-appointments"', false)
        ->assertSee('href="/records"', false)
        ->assertSee('href="/patients/profile"', false);
});

test('nobody signed in gets the patient menu', function () {
    navbarTelemedTables();

    $this->get('/telemed/my-appointments')
        ->assertOk()
        ->assertSee('href="/telemed"', false)
        ->assertSee('href="/telemed/my-appointments"', false)
        ->assertDontSee('href="/records"', false);
});
