<?php

declare(strict_types=1);

namespace App\Subscriptions\Repositories;

use App\Core\Support\JsonFileStore;

final class EndpointActivityRepository
{
    public function create(array $record): void
    {
        $records = JsonFileStore::all('endpoint_activity');
        $record['id'] = JsonFileStore::nextId('endpoint_activity');
        $records[] = $record;
        JsonFileStore::write('endpoint_activity', $records);
    }

    public function latestByIdentifier(string $identifier, int $limit = 20): array
    {
        $rows = array_values(array_filter(
            JsonFileStore::all('endpoint_activity'),
            static fn(array $row): bool => (string)$row['identifier'] === $identifier
        ));
        usort($rows, static fn(array $a, array $b): int => strcmp(($b['activity_date'] ?? '') . ' ' . ($b['activity_time'] ?? ''), ($a['activity_date'] ?? '') . ' ' . ($a['activity_time'] ?? '')));
        return array_slice($rows, 0, $limit);
    }
}
