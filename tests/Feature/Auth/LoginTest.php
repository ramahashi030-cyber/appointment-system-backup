<?php

use App\Models\Admin;
use App\Models\Service;
use App\Models\Staff;
use Database\Seeders\AdminSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * The legacy `services` table only exists in the shared MySQL database —
 * create it on sqlite so the tscode deep link can be asserted.
 */
function loginTestServicesTable(): void
{
    if (Schema::hasTable('services')) {
        return;
    }

    Schema::create('services', function ($table) {
        $table->id();
        $table->string('service_name')->nullable();
        $table->string('availability_day')->nullable();
        $table->string('homis_code')->nullable();
        $table->timestamp('created_at')->nullable();
    });
}

function loginTestStaffTable(): void
{
    if (Schema::hasTable('staff')) {
        return;
    }

    Schema::create('staff', function (Blueprint $table) {
        $table->id();
        $table->string('username')->nullable();
        $table->string('password')->nullable();
        $table->string('LastName')->nullable();
        $table->string('FirstName')->nullable();
        $table->string('MiddleName')->nullable();
        $table->string('contactno')->nullable();
        $table->string('otp')->nullable();
        $table->boolean('is_verified')->default(false);
        $table->string('site')->nullable();
        $table->timestamps();
    });
}

function loginTestAdminTable(): void
{
    if (Schema::hasTable('admin')) {
        return;
    }

    Schema::create('admin', function (Blueprint $table): void {
        $table->id();
        $table->string('firstname');
        $table->string('lastname');
        $table->string('username')->unique();
        $table->string('password');
        $table->string('email')->unique();
        $table->string('contact_no');
        $table->timestamps();
    });
}

test('the login screen renders', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Login page')
        ->assertSee('Signup')
        ->assertSee('Reset Password')
        ->assertSee(route('login.attempt'), false);
});

test('the unified login screen has no separate admin form', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Patient or administrator login')
        ->assertSee(route('login.attempt'), false)
        ->assertDontSee('Admin login');

    $this->get('/admin/login')->assertNotFound();
});

test('wrong credentials show the legacy error', function () {
    makePatient();

    $this->post('/login', ['username' => 'juan', 'password' => 'wrong-password'])
        ->assertSessionHasErrors('login', 'Invalid username or password');
});

test('an unknown username shows the legacy error', function () {
    $this->post('/login', ['username' => 'nobody', 'password' => 'whatever'])
        ->assertSessionHasErrors('login', 'Invalid username or password');
});

test('an active patient signs in and lands on the patient hub', function () {
    $patient = makePatient();

    $this->post('/login', ['username' => 'juan', 'password' => 'secret123'])
        ->assertRedirect('/telemed')
        ->assertSessionHas('patient_id', $patient->id)
        ->assertSessionHas('username', 'juan');
});

test('administrator credentials use the unified login', function () {
    loginTestAdminTable();

    $admin = Admin::create([
        'firstname' => 'Reniel',
        'lastname' => 'Montejo',
        'username' => 'renz',
        'password' => 'Password@123',
        'email' => 'renzmontejo17@gmail.com',
        'contact_no' => '09293470606',
    ]);

    $this->post('/login', ['username' => 'renz', 'password' => 'Password@123'])
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('admin')->check())->toBeTrue()
        ->and(Auth::guard('admin')->id())->toBe($admin->id);
});

test('patient login clears an existing administrator session', function () {
    $admin = Admin::factory()->create();
    $patient = makePatient();

    $this->actingAs($admin, 'admin')
        ->post('/login', ['username' => 'juan', 'password' => 'secret123'])
        ->assertRedirect('/telemed');

    expect(Auth::guard('admin')->check())->toBeFalse()
        ->and(session('patient_id'))->toBe($patient->id);
});

test('an administrator signs in and lands on the admin dashboard', function () {
    loginTestAdminTable();

    $admin = Admin::create([
        'firstname' => 'Reniel',
        'lastname' => 'Montejo',
        'username' => 'renz',
        'password' => 'Password@123',
        'email' => 'renzmontejo17@gmail.com',
        'contact_no' => '09293470606',
    ]);

    makePatient(['username' => 'renz']);

    $this->post('/login', ['username' => 'renz', 'password' => 'Password@123'])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionMissing('patient_id');

    expect(Auth::guard('admin')->check())->toBeTrue()
        ->and(Auth::guard('admin')->id())->toBe($admin->id)
        ->and(Auth::guard('web')->check())->toBeFalse()
        ->and(Auth::guard('admin')->user())
        ->toMatchArray([
            'firstname' => 'Reniel',
            'lastname' => 'Montejo',
            'username' => 'renz',
            'email' => 'renzmontejo17@gmail.com',
            'contact_no' => '09293470606',
        ]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Reniel Montejo');
});

test('an administrator can log out of the admin panel', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.logout'))
        ->assertRedirect(route('auth.login'));

    expect(Auth::guard('admin')->check())->toBeFalse();
});

test('the seeded administrator can sign in with the configured credentials', function () {
    loginTestAdminTable();

    $this->seed(AdminSeeder::class);

    $admin = Admin::where('username', 'renz')->firstOrFail();

    expect($admin->password)->not->toBe('Password@123')
        ->and(Hash::check('Password@123', $admin->password))->toBeTrue();

    $this->post('/login', ['username' => 'renz', 'password' => 'Password@123'])
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('admin')->check())->toBeTrue()
        ->and(Auth::guard('admin')->id())->toBe($admin->id);
});

test('staff records cannot sign in to the admin panel', function () {
    loginTestStaffTable();

    Staff::create([
        'username' => 'staff-user',
        'password' => 'secret123',
        'is_verified' => true,
    ]);

    $this->post('/login', ['username' => 'staff-user', 'password' => 'secret123'])
        ->assertSessionHasErrors('login', 'Invalid username or password');

    expect(Auth::guard('admin')->check())->toBeFalse();
});

test('patient credentials use the unified patient flow', function () {
    $patient = makePatient();

    $this->post('/login', ['username' => 'juan', 'password' => 'secret123'])
        ->assertRedirect('/telemed')
        ->assertSessionHas('patient_id', $patient->id);

    expect(Auth::guard('admin')->check())->toBeFalse();
});

test('a pending patient is asked to verify the OTP first', function () {
    makePatient(['status' => 'Pending']);

    $this->post('/login', ['username' => 'juan', 'password' => 'secret123'])
        ->assertSessionHasErrors('login', 'Account not verified. Please verify OTP first.');
});

test('the tscode deep link picks the matching service', function () {
    loginTestServicesTable();

    $patient = makePatient();
    $service = Service::create(['service_name' => 'Teleconsult', 'homis_code' => 'TCON']);

    session(['tscode' => 'TCON']);

    $this->post('/login', ['username' => 'juan', 'password' => 'secret123'])
        ->assertRedirect('/telemed/book?service_id='.$service->id.'&tsid='.$service->id);

    expect(session('patient_id'))->toBe($patient->id)
        ->and(session('tscode'))->toBeNull();
});

test('default credentials force a password change', function () {
    // username = hospital number, password = birthdate (MMDDYYYY)
    $patient = makePatient([
        'username' => 'HN-0001',
        'hospital_number' => 'HN-0001',
        'password' => '01151990',
    ]);

    $this->post('/login', ['username' => 'HN-0001', 'password' => '01151990'])
        ->assertRedirect(route('password.force'))
        ->assertSessionHas('patient_id', $patient->id);
});

test('hospital number mode signs in on the local birthdate when HOMIS is unreachable', function () {
    $patient = makePatient([
        'username' => 'HN-0002',
        'hospital_number' => 'HN-0002',
        'password' => 'secret123',
    ]);

    $this->post('/login', [
        'username' => 'HN-0002',
        'password' => '01151990', // birthdate MMDDYYYY
        'use_hospital' => '1',
    ])
        ->assertRedirect('/telemed')
        ->assertSessionHas('patient_id', $patient->id);
});

test('hospital number mode rejects a wrong birthdate', function () {
    makePatient([
        'username' => 'HN-0003',
        'hospital_number' => 'HN-0003',
        'password' => 'secret123',
    ]);

    $this->post('/login', [
        'username' => 'HN-0003',
        'password' => '01011980',
        'use_hospital' => '1',
    ])->assertSessionHasErrors(
        'login',
        'Hospital number and Birthdate combination is not correct.'
    );
});

test('an unknown hospital number never signs anyone in', function () {
    $response = $this->from('/')->post('/login', [
        'username' => 'HN-9999',
        'password' => '01151990',
        'use_hospital' => '1',
    ]);

    $response->assertStatus(302);

    expect(session('patient_id'))->toBeNull();

    // HOMIS verified it → registration, HOMIS unreachable → an explicit error.
    if ($response->isRedirect(route('register', ['hn' => 'HN-9999']))) {
        expect(session('patient_id'))->toBeNull();
    } else {
        $response->assertSessionHasErrors('login');
    }
});

test('logout clears the patient session', function () {
    makePatient();

    $this->post('/login', ['username' => 'juan', 'password' => 'secret123']);

    $this->get('/logout')
        ->assertRedirect(route('auth.login'))
        ->assertSessionMissing('patient_id');
});

test('login trims surrounding whitespace from the username', function () {
    $patient = makePatient();

    $this->post('/login', ['username' => '  juan  ', 'password' => 'secret123'])
        ->assertRedirect('/telemed')
        ->assertSessionHas('patient_id', $patient->id);
});

test('a bcrypt hash produced by the legacy app is accepted', function () {
    $patient = makePatient();

    // Write the hash behind the model so the `hashed` cast (which enforces
    // this app's bcrypt cost) does not rewrite it — exactly what the rows
    // created by QALINGA1 look like.
    DB::table('patients')->where('id', $patient->id)->update([
        'password' => password_hash('legacy-pass', PASSWORD_BCRYPT),
    ]);

    $this->post('/login', ['username' => 'juan', 'password' => 'legacy-pass'])
        ->assertRedirect('/telemed');

    expect(Hash::check('legacy-pass', $patient->fresh()->password))->toBeTrue();
});

test('the forced password change screen needs a signed-in patient', function () {
    $this->get('/register/update-password')->assertRedirect(route('auth.login'));
});

test('a signed-in patient can replace default credentials', function () {
    $patient = makePatient([
        'username' => 'HN-0001',
        'hospital_number' => 'HN-0001',
        'password' => '01151990',
    ]);

    $this->post('/login', ['username' => 'HN-0001', 'password' => '01151990'])
        ->assertRedirect(route('password.force'));

    $this->post('/register/update-password', [
        'username' => ' juandelacruz ',
        'password' => 'brand-new-pass',
        'password_confirmation' => 'brand-new-pass',
    ])->assertRedirect('/telemed');

    $patient->refresh();

    expect($patient->username)->toBe('juandelacruz')
        ->and(Hash::check('brand-new-pass', $patient->password))->toBeTrue()
        ->and(Hash::check('01151990', $patient->password))->toBeFalse();
});

test('the update-password screen is shown to a signed-in patient', function () {
    $patient = makePatient([
        'username' => 'HN-0001',
        'hospital_number' => 'HN-0001',
        'password' => '01151990',
    ]);

    $this->post('/login', ['username' => 'HN-0001', 'password' => '01151990']);

    $this->get('/register/update-password')
        ->assertOk()
        ->assertSee('Set your password');
});
