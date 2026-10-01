<?php

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Insert a patient for auth tests (the `patients` table is created by
 * database/migrations/*_create_patients_table_when_missing.php on sqlite).
 */
function makePatient(array $overrides = []): Patient
{
    return Patient::create(array_merge([
        'first_name' => 'JUAN',
        'middlename' => 'S',
        'last_name' => 'DELA CRUZ',
        'dob' => '1990-01-15',
        'gender' => 'Male',
        'contact_number' => '09171234567',
        'address' => '',
        'username' => 'juan',
        'password' => 'secret123',
        'status' => 'Active',
        'hospital_number' => null,
        'otp_code' => null,
        'otp_generated_at' => null,
    ], $overrides));
}
