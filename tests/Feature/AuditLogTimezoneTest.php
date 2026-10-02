<?php

use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function auditLogAdmin(): Admin
{
    return Admin::create([
        'firstname' => 'Reniel',
        'lastname' => 'Montejo',
        'username' => 'audit-tz-admin',
        'password' => 'Password@123',
        'email' => 'audit-tz-admin@example.com',
        'contact_no' => '09293470606',
    ]);
}

test('the display timezone is Asia/Manila while storage stays on UTC', function () {
    expect(config('app.timezone'))->toBe('UTC')
        ->and(config('app.display_timezone'))->toBe('Asia/Manila');

    expect(Carbon::parse('2026-10-02 06:00:00', 'UTC')
        ->timezone(config('app.display_timezone'))
        ->format('Y-m-d H:i:s'))->toBe('2026-10-02 14:00:00');
});

test('staff audit log timestamps render in Asia/Manila', function () {
    $admin = auditLogAdmin();

    AuditLog::create([
        'user_id' => $admin->id,
        'username' => 'audit-tz-admin',
        'user_role' => 'Admin',
        'action' => 'Update',
        'module' => 'Appointments',
        'record_id' => null,
        'created_at' => Carbon::parse('2026-10-02 06:00:00', 'UTC'),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.audit-logs'))
        ->assertOk()
        ->assertSee('Oct 02, 2026')
        ->assertSee('02:00:00 PM')
        ->assertDontSee('06:00:00 AM');
});

test('patient login and logout timestamps render in Asia/Manila', function () {
    $admin = auditLogAdmin();
    $patient = makePatient();

    AuditLog::create([
        'user_id' => $patient->id,
        'username' => 'juan',
        'user_role' => 'Patient',
        'action' => 'Login',
        'module' => 'Authentication',
        'record_id' => null,
        'created_at' => Carbon::parse('2026-10-02 06:00:00', 'UTC'),
    ]);

    AuditLog::create([
        'user_id' => $patient->id,
        'username' => 'juan',
        'user_role' => 'Patient',
        'action' => 'Logout',
        'module' => 'Authentication',
        'record_id' => null,
        'created_at' => Carbon::parse('2026-10-02 07:30:00', 'UTC'),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.audit-logs', ['type' => 'patients']))
        ->assertOk()
        ->assertSee('02:00:00 PM')   // 06:00 UTC login
        ->assertSee('03:30:00 PM')   // 07:30 UTC logout
        ->assertDontSee('06:00:00 AM')
        ->assertDontSee('07:30:00 AM');
});

test('the audit log timestamp stays stored in UTC', function () {
    $admin = auditLogAdmin();

    $log = AuditLog::create([
        'user_id' => $admin->id,
        'username' => 'audit-tz-admin',
        'user_role' => 'Admin',
        'action' => 'Update',
        'module' => 'Appointments',
        'record_id' => null,
        'created_at' => Carbon::parse('2026-10-02 06:00:00', 'UTC'),
    ]);

    expect($log->fresh()->getRawOriginal('created_at'))->toStartWith('2026-10-02 06:00:00');
});