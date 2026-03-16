<?php

declare(strict_types=1);

namespace App\Subscriptions\Repositories;

use App\Core\Support\JsonFileStore;

final class SubscriptionTypeRepository
{
    public function all(): array
    {
        $types = JsonFileStore::all('subscription_types');
        usort($types, static fn(array $a, array $b): int => strcmp((string)$a['name'], (string)$b['name']));
        return $types;
    }

    public function findById(int $id): ?array
    {
        foreach (JsonFileStore::all('subscription_types') as $type) {
            if ((int)$type['id'] === $id) return $type;
        }
        return null;
    }

    public function update(int $id, array $fields): ?array
    {
        $types = JsonFileStore::all('subscription_types');
        foreach ($types as &$type) {
            if ((int)$type['id'] !== $id) {
                continue;
            }
            $type['name'] = trim((string)($fields['name'] ?? $type['name']));
            $type['description'] = trim((string)($fields['description'] ?? $type['description']));
            $type['price_per_station'] = isset($fields['price_per_station']) ? (float)$fields['price_per_station'] : $type['price_per_station'];
            $type['frequency_in_hours'] = $fields['frequency_in_hours'] !== '' ? (int)$fields['frequency_in_hours'] : null;
            $type['frequency_in_days'] = $fields['frequency_in_days'] !== '' ? (int)$fields['frequency_in_days'] : null;
            $type['continuous'] = isset($fields['continuous']) ? (int)$fields['continuous'] : $type['continuous'];
            JsonFileStore::write('subscription_types', $types);
            return $type;
        }
        return null;
    }
}
