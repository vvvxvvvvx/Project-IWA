<?php

require(__DIR__ . '/bootstrap/app.php');

use Illuminate\Support\Facades\DB;

// Check station table structure
echo "=== STATION TABLE COLUMNS ===\n";
$columns = DB::select("DESCRIBE station");
foreach ($columns as $col) {
    echo $col->Field . " (" . $col->Type . ")\n";
}

echo "\n=== SAMPLE STATION RECORDS ===\n";
$samples = DB::table('station')->limit(3)->get();
foreach ($samples as $sample) {
    echo json_encode($sample, JSON_PRETTY_PRINT) . "\n";
}

// Check for country-related fields
echo "\n=== Searching for country/location info ===\n";
$fields = array_keys((array)DB::table('station')->first());
echo "All fields in station: " . implode(", ", $fields) . "\n";

// Check if there's any non-NULL information
echo "\n=== Unique values in location-related fields ===\n";
if (in_array('location', $fields)) {
    $locations = DB::table('station')->distinct()->select('location')->get();
    echo "Unique locations: " . count($locations) . "\n";
}

// Check other tables that might have country info
echo "\n=== OTHER TABLES WITH 'country' IN NAME ===\n";
$tables = DB::select("SHOW TABLES");
foreach ($tables as $table) {
    $tableName = reset($table);
    if (stripos($tableName, 'country') !== false || stripos($tableName, 'location') !== false) {
        $count = DB::table($tableName)->count();
        echo "$tableName: $count records\n";
        
        if ($count > 0 && $count < 200) {
            $sample = DB::table($tableName)->first();
            echo "Sample: " . json_encode($sample, JSON_PRETTY_PRINT) . "\n";
        }
    }
}

// Check endpoint_activity for any geographic clues
echo "\n=== ENDPOINT_ACTIVITY TABLE (first 3 records) ===\n";
$activities = DB::table('endpoint_activity')->limit(3)->get();
foreach ($activities as $activity) {
    echo json_encode($activity, JSON_PRETTY_PRINT) . "\n";
}
