<?php

declare(strict_types=1);

namespace App\Ingestion\Repositories;

use App\Core\Support\JsonFileStore;

final class OriginalMeasurementRepository
{
    public function create(array $record): void
    {
        $records = JsonFileStore::all('original_measurements');
        $record['id'] = JsonFileStore::nextId('original_measurements');
        $records[] = $record;
        JsonFileStore::write('original_measurements', $records);
    }

    public function latest(int $limit = 20): array
    {
        $records = JsonFileStore::all('original_measurements');
        usort($records, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return array_slice($records, 0, $limit);
    }

    public function latestTemperatureCorrections(int $limit = 20): array
    {
        $records = array_values(array_filter(
            JsonFileStore::all('original_measurements'),
            static fn(array $row): bool => strtoupper((string) ($row['field'] ?? '')) === 'TEMP'
        ));
        usort($records, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        $records = array_slice($records, 0, $limit);
        $records = array_reverse($records);
        return array_map(static function(array $row): array {
            return [
                'bucket' => substr((string) ($row['created_at'] ?? ''), 0, 16),
                'original_temp' => $row['original_value'] ?? null,
                'corrected_temp' => $row['corrected_value'] ?? null,
                'stn' => $row['stn'] ?? null,
            ];
        }, $records);
    }
}
