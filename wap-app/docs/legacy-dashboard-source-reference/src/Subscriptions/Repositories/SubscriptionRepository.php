<?php

declare(strict_types=1);

namespace App\Subscriptions\Repositories;

use App\Companies\Repositories\CompanyRepository;
use App\Core\Support\JsonFileStore;
use App\Stations\Repositories\StationRepository;

final class SubscriptionRepository
{
    public function __construct(
        private readonly CompanyRepository $companies = new CompanyRepository(),
        private readonly SubscriptionTypeRepository $types = new SubscriptionTypeRepository(),
        private readonly StationRepository $stations = new StationRepository(),
    ) {}

    public function all(): array
    {
        $companyMap = [];
        foreach ($this->companies->all() as $company) {
            $companyMap[(int)$company['id']] = $company;
        }
        $typeMap = [];
        foreach ($this->types->all() as $type) {
            $typeMap[(int)$type['id']] = $type;
        }
        $stationMap = $this->stationsBySubscription();
        $subscriptions = JsonFileStore::all('subscriptions');
        usort($subscriptions, static fn(array $a, array $b): int => strcmp((string)$a['identifier'], (string)$b['identifier']));
        return array_map(static function(array $sub) use ($companyMap, $typeMap, $stationMap): array {
            $sub['company'] = $companyMap[(int)$sub['company_id']] ?? null;
            $sub['type'] = $typeMap[(int)$sub['type_id']] ?? null;
            $sub['stations'] = $stationMap[(int)$sub['id']] ?? [];
            $sub['station_count'] = count($sub['stations']);
            $sub['contract_title'] = 'Contract ' . $sub['identifier'];
            return $sub;
        }, $subscriptions);
    }

    public function findByIdentifier(string $identifier): ?array
    {
        foreach ($this->all() as $subscription) {
            if ((string)$subscription['identifier'] === $identifier) {
                return $subscription;
            }
        }
        return null;
    }

    public function allByTypeId(int $typeId): array
    {
        return array_values(array_filter($this->all(), static fn(array $subscription): bool => (int)($subscription['type_id'] ?? 0) === $typeId));
    }

    public function create(array $fields): array
    {
        $subscriptions = JsonFileStore::all('subscriptions');
        $identifier = strtoupper(trim((string)($fields['identifier'] ?? 'SUB' . JsonFileStore::nextId('subscriptions'))));
        $subscription = [
            'id' => JsonFileStore::nextId('subscriptions'),
            'company_id' => (int)($fields['company_id'] ?? 0),
            'type_id' => (int)($fields['type_id'] ?? 0),
            'start_date' => trim((string)($fields['start_date'] ?? gmdate('Y-m-d'))),
            'end_date' => trim((string)($fields['end_date'] ?? '')) ?: null,
            'price' => isset($fields['price']) ? (float)$fields['price'] : 0.0,
            'notes' => trim((string)($fields['notes'] ?? '')) ?: null,
            'identifier' => $identifier,
            'token' => trim((string)($fields['token'] ?? '')) ?: bin2hex(random_bytes(12)),
        ];
        $subscriptions[] = $subscription;
        JsonFileStore::write('subscriptions', $subscriptions);
        $this->syncStations((int)$subscription['id'], (string)($fields['stations'] ?? ''));
        return $this->findByIdentifier($identifier) ?? $subscription;
    }

    public function updateByIdentifier(string $identifier, array $fields): ?array
    {
        $subscriptions = JsonFileStore::all('subscriptions');
        foreach ($subscriptions as &$subscription) {
            if ((string)$subscription['identifier'] !== $identifier) {
                continue;
            }
            $subscription['company_id'] = isset($fields['company_id']) && $fields['company_id'] !== '' ? (int)$fields['company_id'] : (int)$subscription['company_id'];
            $subscription['price'] = isset($fields['price']) && $fields['price'] !== '' ? (float)$fields['price'] : $subscription['price'];
            $subscription['notes'] = trim((string)($fields['notes'] ?? $subscription['notes'])) ?: null;
            $subscription['end_date'] = trim((string)($fields['end_date'] ?? '')) !== '' ? trim((string)$fields['end_date']) : null;
            $subscription['start_date'] = trim((string)($fields['start_date'] ?? $subscription['start_date'])) ?: $subscription['start_date'];
            if (isset($fields['type_id']) && $fields['type_id'] !== '') {
                $subscription['type_id'] = (int)$fields['type_id'];
            }
            if (isset($fields['identifier']) && trim((string)$fields['identifier']) !== '') {
                $subscription['identifier'] = strtoupper(trim((string)$fields['identifier']));
            }
            JsonFileStore::write('subscriptions', $subscriptions);
            $this->syncStations((int)$subscription['id'], (string)($fields['stations'] ?? ''));
            return $this->findByIdentifier((string)$subscription['identifier']);
        }
        return null;
    }

    public function deleteByIdentifier(string $identifier): bool
    {
        $subscriptions = JsonFileStore::all('subscriptions');
        $targetId = null;
        $filtered = [];
        foreach ($subscriptions as $subscription) {
            if ((string)$subscription['identifier'] === $identifier) {
                $targetId = (int)$subscription['id'];
                continue;
            }
            $filtered[] = $subscription;
        }
        if ($targetId === null) {
            return false;
        }
        JsonFileStore::write('subscriptions', $filtered);
        $this->syncStations($targetId, '');
        return true;
    }

    public function regenerateToken(string $identifier): ?array
    {
        $subscriptions = JsonFileStore::all('subscriptions');
        foreach ($subscriptions as &$subscription) {
            if ((string)$subscription['identifier'] === $identifier) {
                $subscription['token'] = bin2hex(random_bytes(12));
                JsonFileStore::write('subscriptions', $subscriptions);
                return $this->findByIdentifier($identifier);
            }
        }
        return null;
    }

    public function authenticate(string $identifier, ?string $token): ?array
    {
        if ($token === null || $token === '') return null;
        $subscription = $this->findByIdentifier($identifier);
        if ($subscription === null) return null;
        return hash_equals((string)$subscription['token'], $token) ? $subscription : null;
    }

    public function stationsBySubscription(): array
    {
        $stationLinks = JsonFileStore::all('subscription_station');
        $stationMeta = [];
        foreach ($this->stations->latestWithSummaries(1000) as $station) {
            $stationMeta[(string)$station['stn']] = $station;
        }
        foreach (JsonFileStore::all('station_metadata') as $stn => $metadata) {
            if (!isset($stationMeta[$stn])) {
                $stationMeta[$stn] = [
                    'stn' => $stn,
                    'name' => 'Weerstation ' . $stn,
                    'location_label' => trim(implode(', ', array_filter([$metadata['name'] ?? null, $metadata['administrative_region1'] ?? null, $metadata['country_name'] ?? null]))),
                    'lat' => $metadata['latitude'] ?? null,
                    'lon' => $metadata['longitude'] ?? null,
                ];
            }
        }
        $grouped = [];
        foreach ($stationLinks as $link) {
            $grouped[(int)$link['subscription_id']][] = $stationMeta[(string)$link['station']] ?? [
                'stn' => (string)$link['station'],
                'name' => 'Weerstation ' . $link['station'],
                'location_label' => 'Onbekend',
            ];
        }
        return $grouped;
    }

    private function syncStations(int $subscriptionId, string $stations): void
    {
        $existing = JsonFileStore::all('subscription_station');
        $filtered = array_values(array_filter($existing, static fn(array $link): bool => (int)$link['subscription_id'] !== $subscriptionId));
        $stationCodes = array_values(array_unique(array_filter(array_map(static function (string $value): string {
            return trim($value);
        }, preg_split('/[,\s]+/', $stations) ?: []), static fn(string $value): bool => $value !== '')));

        foreach ($stationCodes as $stationCode) {
            $filtered[] = ['subscription_id' => $subscriptionId, 'station' => $stationCode];
        }
        JsonFileStore::write('subscription_station', $filtered);
    }
}
