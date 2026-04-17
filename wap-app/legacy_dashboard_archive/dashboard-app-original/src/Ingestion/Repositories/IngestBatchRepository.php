<?php

declare(strict_types=1);

namespace App\Ingestion\Repositories;

use App\Core\Support\JsonFileStore;

final class IngestBatchRepository
{
    public function create(array $record): void
    {
        $records = JsonFileStore::all('ingest_batches');
        $record['id'] = JsonFileStore::nextId('ingest_batches');
        $records[] = $record;
        JsonFileStore::write('ingest_batches', $records);
    }

    public function latest(int $limit = 10): array
    {
        $records = JsonFileStore::all('ingest_batches');
        usort($records, static fn (array $a, array $b): int => strcmp($b['received_at'], $a['received_at']));
        return array_slice($records, 0, $limit);
    }
}
