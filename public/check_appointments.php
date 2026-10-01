<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$appointments = DB::table('appointments')
    ->select('id', 'patient_id', 'status', 'triager_status', 'request_mode', 'mode', 'date', 'time_slot', 'created_at')
    ->get();

echo 'Total appointments: '.$appointments->count()."\n\n";

foreach ($appointments as $appt) {
    echo "ID: {$appt->id} | Patient: {$appt->patient_id} | Status: {$appt->status} | Triager Status: {$appt->triager_status} | Request Mode: {$appt->request_mode} | Mode: {$appt->mode} | Date: {$appt->date} | Time: {$appt->time_slot}\n";
}
