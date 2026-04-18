<?php

declare(strict_types=1);

namespace App\Ingestion\Repositories;

use App\Core\Support\JsonFileStore;

final class CorrectionLogRepository
{
    public function create(array $record): void
    {
        $records = JsonFileStore::all('correction_log');
        $record['id'] = JsonFileStore::nextId('correction_log');
        $records[] = $record;
        JsonFileStore::write('correction_log', $records);
    }

    public function latest(int $limit = 20): array
    {
        $records = JsonFileStore::all('correction_log');
        usort($records, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return array_slice($records, 0, $limit);
    }
}
