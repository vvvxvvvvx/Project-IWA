<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=iwa', 'root', 'MaanMoon03!');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== NEARESTLOCATION TABLE COLUMNS ===\n";
$stmt = $pdo->query('DESCRIBE nearestlocation');
while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n=== NEARESTLOCATION SAMPLE (3 RECORDS) ===\n";
$stmt = $pdo->query('SELECT * FROM nearestlocation LIMIT 3');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== COUNTRY TABLE COLUMNS ===\n";
$stmt = $pdo->query('DESCRIBE country');
while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n=== COUNTRY SAMPLE (first 5) ===\n";
$stmt = $pdo->query('SELECT * FROM country LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== TEST: Join nearestlocation + country ===\n";
$stmt = $pdo->query('
    SELECT nl.*, c.name as country_name 
    FROM nearestlocation nl
    LEFT JOIN country c ON nl.country_id = c.id
    LIMIT 3
');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
}
