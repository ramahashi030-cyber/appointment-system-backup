<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$errors = new ViewErrorBag;
$errors->put('createPatient', new MessageBag);

$view = view('admin.patients', [
    'patients' => new LengthAwarePaginator([], 0, 20),
    'patientStats' => ['total' => 0, 'active' => 0, 'pending' => 0],
    'filters' => ['search' => '', 'status' => '', 'gender' => ''],
    'patientModal' => null,
    'patient' => null,
    'errors' => $errors,
]);

$html = $view->render();

echo str_contains($html, 'id="createPatientModal"') ? "createPatientModal: FOUND\n" : "createPatientModal: NOT FOUND\n";
echo str_contains($html, 'data-patient-filters') ? "filters: FOUND\n" : "filters: NOT FOUND\n";

if (preg_match('/<main class="admin-main">(.*?)<\/main>/s', $html, $matches)) {
    echo str_contains($matches[1], 'createPatientModal') ? "modal inside admin-main: YES\n" : "modal inside admin-main: NO\n";
    echo str_contains($matches[1], 'data-patient-filters') ? "filters inside admin-main: YES\n" : "filters inside admin-main: NO\n";
}

$modalPos = strpos($html, 'id="createPatientModal"');
$mainOpenPos = strpos($html, '<main class="admin-main">');
$mainClosePos = strpos($html, '</main>');
echo "modal position: $modalPos\n";
echo "main open: $mainOpenPos, main close: $mainClosePos\n";
echo ($modalPos > $mainOpenPos && $modalPos < $mainClosePos) ? "modal between main tags: YES\n" : "modal between main tags: NO\n";

// Simulate AJAX navigation extracting admin-main inner HTML like jQuery .find('.admin-main').html()
$dom = new DOMDocument;
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);
$main = $xpath->query("//*[contains(@class,'admin-main')]")->item(0);
if ($main) {
    $inner = '';
    foreach ($main->childNodes as $child) {
        $inner .= $dom->saveHTML($child);
    }
    echo str_contains($inner, 'createPatientModal') ? "ajax extracted modal: YES\n" : "ajax extracted modal: NO\n";
}
