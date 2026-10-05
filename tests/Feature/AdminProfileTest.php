<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function adminProfileAdmin(array $overrides = []): Admin
{
    return Admin::create(array_merge([
        'firstname' => 'Reniel',
        'lastname' => 'Montejo',
        'username' => 'renzel-admin',
        'password' => 'Password@123',
        'email' => 'renzel-admin@example.com',
        'contact_no' => '09293470606',
    ], $overrides));
}

test('an administrator can save a name made of letters and spaces', function (): void {
    $admin = adminProfileAdmin();

    $this->actingAs($admin, 'admin')
        ->put(route('admin.profile.update'), [
            'firstname' => 'Mary Ann',
            'lastname' => 'Dela Cruz',
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJson([
            'message' => 'Profile updated successfully.',
            'full_name' => 'Mary Ann Dela Cruz',
        ]);

    $admin->refresh();

    expect($admin->firstname)->toBe('Mary Ann')
        ->and($admin->lastname)->toBe('Dela Cruz');
});

test('the admin name fields refuse numbers and special characters', function (string $field, string $value): void {
    $admin = adminProfileAdmin();

    $payload = [
        'firstname' => 'Reniel',
        'lastname' => 'Montejo',
    ];
    $payload[$field] = $value;

    $this->actingAs($admin, 'admin')
        ->put(route('admin.profile.update'), $payload, ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors([$field])
        ->assertJsonValidationErrors([$field => 'letters and spaces']);

    $admin->refresh();

    expect($admin->firstname)->toBe('Reniel')
        ->and($admin->lastname)->toBe('Montejo');
})->with([
    'first name with a number' => ['firstname', 'Reniel2'],
    'first name with a symbol' => ['firstname', 'Reniel@Admin'],
    'last name with a number' => ['lastname', 'Montejo123'],
    'last name with a symbol' => ['lastname', 'Montejo!'],
    'last name with a slash' => ['lastname', 'Montejo/Dela'],
    'last name with an underscore' => ['lastname', 'Montejo_Cruz'],
    'first name with a hyphen' => ['firstname', 'Jean-Luc'],
]);

test('an administrator can change the password within the allowed length and character rules', function (string $password): void {
    $admin = adminProfileAdmin();

    $this->actingAs($admin, 'admin')
        ->put(route('admin.profile.password'), [
            'current_password' => 'Password@123',
            'password' => $password,
            'password_confirmation' => $password,
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJson(['message' => 'Password changed successfully.']);

    expect(Hash::check($password, $admin->refresh()->password))->toBeTrue();
})->with([
    'shortest mixed password without a symbol' => ['Pass1wd'],
    'eight characters without a symbol' => ['Passw0rd'],
    'over eight characters with a symbol' => ['Passw0rd!'],
    'fifteen characters with a symbol' => ['Passw0rd!Qmmc12'],
]);

test('the admin password change refuses passwords outside the rules', function (string $password): void {
    $admin = adminProfileAdmin();

    $this->actingAs($admin, 'admin')
        ->put(route('admin.profile.password'), [
            'current_password' => 'Password@123',
            'password' => $password,
            'password_confirmation' => $password,
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);

    expect(Hash::check('Password@123', $admin->refresh()->password))->toBeTrue();
})->with([
    'shorter than six characters' => 'Pw1d',
    'longer than fifteen characters' => 'Passw0rd!Qmmc12345',
    'no uppercase letter' => 'passw0rd',
    'no lowercase letter' => 'PASSW0RD',
    'no number' => 'Password!',
    'over eight characters without a symbol' => 'Passw0rdlong',
]);

test('the my profile password form advertises the new limits', function (): void {
    $admin = adminProfileAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('name="password" placeholder="New password" required minlength="6" maxlength="15"', false)
        ->assertSee('name="password_confirmation" placeholder="Confirm new password" required minlength="6" maxlength="15"', false)
        ->assertSee('6 to 15 characters, with uppercase and lowercase letters and a number (over 8 characters also needs a special character).', false);
});

test('the my profile modal only accepts letters in the name fields', function (): void {
    $admin = adminProfileAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('name="firstname" value="Reniel" placeholder="First name" required maxlength="100" pattern="[A-Za-z]+( +[A-Za-z]+)*" data-letters-only', false)
        ->assertSee('name="lastname" value="Montejo" placeholder="Last name" required maxlength="100" pattern="[A-Za-z]+( +[A-Za-z]+)*" data-letters-only', false)
        ->assertSee('Letters and spaces only', false);
});
