<?php

declare(strict_types=1);

$base = dirname(__DIR__) . '/storage/data';
$datasets = [
    'stations',
    'weather_readings',
    'ingest_batches',
    'original_measurements',
    'correction_log',
];

foreach ($datasets as $dataset) {
    file_put_contents($base . '/' . $dataset . '.json', json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    echo "Leeggemaakt: {$dataset}
";
}

echo "Runtime datasets zijn leeggemaakt. Start nu de generator om nieuwe weerdata op te bouwen.
";
