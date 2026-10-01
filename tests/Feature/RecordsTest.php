<?php

use App\Models\MedicalRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A patient to own records, plus a second one used to prove the CRUD never
 * reaches across patients.
 */
function recordsTestPatients(): array
{
    $owner = makePatient();
    $other = makePatient([
        'username' => 'maria',
        'password' => 'secret123',
    ]);

    return [$owner, $other];
}

test('records requires a signed-in patient', function () {
    $this->get('/records')->assertRedirect(route('auth.login'));
});

test('the records page lists only the signed-in patient records', function () {
    [$owner, $other] = recordsTestPatients();

    MedicalRecord::create([
        'patient_id' => $owner->id,
        'record_type' => 'Laboratory Result',
        'description' => 'Complete Blood Count',
    ]);

    $otherRecord = MedicalRecord::create([
        'patient_id' => $other->id,
        'record_type' => 'Radiology / X-Ray',
        'description' => 'Chest X-Ray',
    ]);

    $this->withSession(['patient_id' => $owner->id])
        ->get('/records')
        ->assertOk()
        ->assertSee('Medical Records')
        ->assertSee('Laboratory Result')
        ->assertSee('Complete Blood Count')
        // The other patient's row must not leak in (the type names are also
        // datalist options on the form, so assert on the row itself).
        ->assertDontSee('Chest X-Ray')
        ->assertDontSee('data-id="'.$otherRecord->id.'"', false)
        // CRUD script lives outside the view, in public/js/records.js.
        ->assertSee('js/records.js', false);
});

test('a patient can add a record', function () {
    [$owner] = recordsTestPatients();

    $this->withSession(['patient_id' => $owner->id])
        ->post('/records', [
            'record_type' => 'Prescription',
            'description' => 'Amoxicillin 500mg',
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'record' => ['record_type' => 'Prescription'],
        ]);

    expect(MedicalRecord::where('patient_id', $owner->id)->count())->toBe(1);
});

test('adding a record without a type is rejected', function () {
    [$owner] = recordsTestPatients();

    $this->withSession(['patient_id' => $owner->id])
        ->from('/records')
        ->post('/records', ['description' => 'No type given'], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('record_type');

    expect(MedicalRecord::count())->toBe(0);
});

test('a patient can update their own record', function () {
    [$owner] = recordsTestPatients();

    $record = MedicalRecord::create([
        'patient_id' => $owner->id,
        'record_type' => 'Laboratory Result',
        'description' => 'Old description',
    ]);

    $this->withSession(['patient_id' => $owner->id])
        ->put('/records/'.$record->id, [
            'record_type' => 'Ultrasound',
            'description' => 'Abdominal ultrasound',
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJson(['success' => true, 'record' => ['record_type' => 'Ultrasound']]);

    expect($record->refresh()->description)->toBe('Abdominal ultrasound');
});

test('a patient cannot update someone elses record', function () {
    [$owner, $other] = recordsTestPatients();

    $record = MedicalRecord::create([
        'patient_id' => $other->id,
        'record_type' => 'Prescription',
    ]);

    $this->withSession(['patient_id' => $owner->id])
        ->put('/records/'.$record->id, ['record_type' => 'Hacked'], ['Accept' => 'application/json'])
        ->assertNotFound();

    expect($record->refresh()->record_type)->toBe('Prescription');
});

test('a patient can delete their own record', function () {
    [$owner] = recordsTestPatients();

    $record = MedicalRecord::create([
        'patient_id' => $owner->id,
        'record_type' => 'Medical Certificate',
    ]);

    $this->withSession(['patient_id' => $owner->id])
        ->delete('/records/'.$record->id, [], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(MedicalRecord::find($record->id))->toBeNull();
});

test('a patient cannot delete someone elses record', function () {
    [$owner, $other] = recordsTestPatients();

    $record = MedicalRecord::create([
        'patient_id' => $other->id,
        'record_type' => 'Prescription',
    ]);

    $this->withSession(['patient_id' => $owner->id])
        ->delete('/records/'.$record->id, [], ['Accept' => 'application/json'])
        ->assertNotFound();

    expect(MedicalRecord::find($record->id))->not->toBeNull();
});

test('the crud endpoints reject a signed-out browser', function () {
    $this->post('/records', ['record_type' => 'Prescription'])
        ->assertRedirect(route('auth.login'));

    $this->post('/records', ['record_type' => 'Prescription'], ['Accept' => 'application/json'])
        ->assertUnauthorized();
});
