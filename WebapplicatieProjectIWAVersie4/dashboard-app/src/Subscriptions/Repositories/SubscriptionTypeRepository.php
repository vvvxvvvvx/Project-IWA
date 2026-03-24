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

    public function create(array $fields): array
    {
        $types = JsonFileStore::all('subscription_types');
        $type = [
            'id' => JsonFileStore::nextId('subscription_types'),
            'name' => trim((string)($fields['name'] ?? 'Nieuw type')),
            'description' => trim((string)($fields['description'] ?? '')),
            'nr_stations' => trim((string)($fields['nr_stations'] ?? '')) !== '' ? (int)$fields['nr_stations'] : null,
            'frequency_in_hours' => trim((string)($fields['frequency_in_hours'] ?? '')) !== '' ? (int)$fields['frequency_in_hours'] : null,
            'frequency_in_days' => trim((string)($fields['frequency_in_days'] ?? '')) !== '' ? (int)$fields['frequency_in_days'] : null,
            'continuous' => isset($fields['continuous']) ? (int)$fields['continuous'] : 0,
            'price_per_station' => isset($fields['price_per_station']) ? (float)$fields['price_per_station'] : 0,
            'valid_through' => trim((string)($fields['valid_through'] ?? '')) ?: null,
        ];
        $types[] = $type;
        JsonFileStore::write('subscription_types', $types);
        return $type;
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
            $type['nr_stations'] = trim((string)($fields['nr_stations'] ?? ($type['nr_stations'] ?? ''))) !== '' ? (int)$fields['nr_stations'] : null;
            $type['frequency_in_hours'] = trim((string)($fields['frequency_in_hours'] ?? '')) !== '' ? (int)$fields['frequency_in_hours'] : null;
            $type['frequency_in_days'] = trim((string)($fields['frequency_in_days'] ?? '')) !== '' ? (int)$fields['frequency_in_days'] : null;
            $type['continuous'] = isset($fields['continuous']) ? (int)$fields['continuous'] : (int)($type['continuous'] ?? 0);
            $type['valid_through'] = trim((string)($fields['valid_through'] ?? ($type['valid_through'] ?? ''))) ?: null;
            JsonFileStore::write('subscription_types', $types);
            return $type;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $types = JsonFileStore::all('subscription_types');
        $filtered = array_values(array_filter($types, static fn(array $type): bool => (int)$type['id'] !== $id));
        if (count($filtered) === count($types)) {
            return false;
        }
        JsonFileStore::write('subscription_types', $filtered);
        return true;
    }
}
