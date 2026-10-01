<?php

$host = 'localhost';
$db = 'patient_appointment';
$user = 'qalinga_user';
$pass = 'Qalinga123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SHOW COLUMNS FROM appointments WHERE Field IN ('status', 'mode', 'request_mode', 'triager_status')");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "Field: {$col['Field']}, Type: {$col['Type']}, Null: {$col['Null']}, Default: {$col['Default']}".PHP_EOL;
    }
} catch (PDOException $e) {
    echo 'Error: '.$e->getMessage().PHP_EOL;
}
