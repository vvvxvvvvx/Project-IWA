<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check what's in the database
echo "=== GEOLOCATION TABLE ===" . PHP_EOL;
$geoCount = DB::table('geolocation')->count();
echo "Total records: " . $geoCount . PHP_EOL;

echo PHP_EOL . "=== STATION TABLE ===" . PHP_EOL;
$stationCount = DB::table('station')->count();
echo "Total records: " . $stationCount . PHP_EOL;

echo PHP_EOL . "=== SAMPLE GEOLOCATION RECORD ===" . PHP_EOL;
$sample = DB::table('geolocation')->first();
echo json_encode($sample, JSON_PRETTY_PRINT) . PHP_EOL;

echo PHP_EOL . "=== SAMPLE STATION RECORD ===" . PHP_EOL;
$sampleStn = DB::table('station')->first();
echo json_encode($sampleStn, JSON_PRETTY_PRINT) . PHP_EOL;

echo PHP_EOL . "=== JOIN TEST ===" . PHP_EOL;
$joined = DB::table('geolocation as g')
    ->join('station as s', 's.name', '=', 'g.station_name')
    ->count();
echo "Joined records: " . $joined . PHP_EOL;

echo PHP_EOL . "=== COUNTRY QUERY ===" . PHP_EOL;
$countries = DB::table('geolocation as g')
    ->join('station as s', 's.name', '=', 'g.station_name')
    ->select('g.country')
    ->distinct()
    ->limit(10)
    ->get();
echo "Countries found: " . count($countries) . PHP_EOL;
foreach ($countries as $c) {
    echo "  - " . $c->country . PHP_EOL;
}

echo PHP_EOL . "=== FULL QUERY ===" . PHP_EOL;
$result = DB::table('geolocation as g')
    ->join('station as s', 's.name', '=', 'g.station_name')
    ->select(
        'g.country as country_name',
        DB::raw('AVG(s.latitude) as lat'),
        DB::raw('AVG(s.longitude) as lng'),
        DB::raw('COUNT(DISTINCT g.station_name) as station_count')
    )
    ->whereNotNull('g.country')
    ->groupBy('g.country')
    ->orderByDesc('station_count')
    ->get();

echo "Result count: " . count($result) . PHP_EOL;
foreach ($result as $row) {
    echo json_encode($row, JSON_PRETTY_PRINT) . PHP_EOL;
}
