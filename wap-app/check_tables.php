<?php

// Parse .env file
$env_file = __DIR__ . '/.env';
$env = [];
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, 'DB_') === 0) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
}

// Connect to database
try {
    $pdo = new PDO(
        "mysql:host=" . ($env['DB_HOST'] ?? 'localhost') . ";dbname=" . ($env['DB_DATABASE'] ?? 'laravel'),
        $env['DB_USERNAME'] ?? 'root',
        $env['DB_PASSWORD'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Check station table structure
echo "=== STATION TABLE COLUMNS ===\n";
$stmt = $pdo->query("DESCRIBE station");
while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n=== SAMPLE STATION RECORDS ===\n";
$stmt = $pdo->query("SELECT * FROM station LIMIT 3");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
}

// Check for any table with country/location
echo "\n=== TABLES WITH COUNTRY/LOCATION IN NAME ===\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    if (stripos($table, 'country') !== false || stripos($table, 'location') !== false || stripos($table, 'geoloc') !== false) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetch(PDO::FETCH_COLUMN);
        echo "$table: $count records\n";
        
        if ($count > 0 && $count < 20) {
            echo "Sample:\n";
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 1");
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
}

// Check what the country table looks like
echo "\n=== COUNTRY TABLE (if exists) ===\n";
try {
    $stmt = $pdo->query("SELECT * FROM project_web_country LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "project_web_country not found\n";
}

// Check if station has any location/region field
echo "\n=== CHECKING STATION FIELD VALUES ===\n";
$stmt = $pdo->query("SELECT DISTINCT SUBSTRING(name, 1, 2) as prefix FROM station LIMIT 10");
echo "Sample station name prefixes:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . $row['prefix'] . "\n";
}
