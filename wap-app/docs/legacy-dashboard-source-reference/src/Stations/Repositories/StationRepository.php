<?php

declare(strict_types=1);

namespace App\Stations\Repositories;

use App\Core\Support\JsonFileStore;
use App\Stations\Services\StationDataSnapshot;

final class StationRepository
{
    public function __construct(
        private readonly StationMetadataRepository $metadata = new StationMetadataRepository(),
        private readonly StationDataSnapshot $snapshot = new StationDataSnapshot(),
    ) {}

    public function findOrCreateByStn(string $stn): array
    {
        $stations = JsonFileStore::all('stations');
        foreach ($stations as &$station) {
            if (($station['stn'] ?? null) === $stn) {
                $station['last_seen_at'] = gmdate('c');
                $station = $this->applyMetadata($station);
                JsonFileStore::write('stations', $stations);
                return $station;
            }
        }

        $station = $this->applyMetadata([
            'id' => JsonFileStore::nextId('stations'),
            'stn' => $stn,
            'name' => 'Weerstation ' . $stn,
            'location_label' => 'Onbekend (alleen STN aanwezig)',
            'first_seen_at' => gmdate('c'),
            'last_seen_at' => gmdate('c'),
        ]);
        $stations[] = $station;
        JsonFileStore::write('stations', $stations);
        return $station;
    }

    public function findByStn(string $stn): ?array
    {
        return $this->snapshot->stationByStn($stn);
    }

    public function updateLocationLabel(string $stn, string $locationLabel): void
    {
        $stations = JsonFileStore::all('stations');
        foreach ($stations as &$station) {
            if (($station['stn'] ?? null) === $stn) {
                $station['location_label'] = trim($locationLabel) !== '' ? trim($locationLabel) : ($station['location_label'] ?? 'Onbekend');
            }
        }
        JsonFileStore::write('stations', $stations);
    }

    public function latestWithSummaries(int $limit = 25): array
    {
        return $this->snapshot->summarizedStations($limit);
    }

    public function topByReadingCount(int $limit = 5): array
    {
        return $this->snapshot->topStations($limit);
    }

    private function applyMetadata(array $station): array
    {
        $metadata = $this->metadata->findByStn((string)($station['stn'] ?? ''));
        if ($metadata === null) {
            return $station;
        }

        $parts = array_values(array_filter([
            $metadata['name'] ?? null,
            $metadata['administrative_region1'] ?? null,
            $metadata['country_name'] ?? ($metadata['country'] ?? null),
        ], static fn($value): bool => $value !== null && trim((string)$value) !== ''));
        if ($parts !== []) {
            $station['location_label'] = implode(', ', array_unique($parts));
        }
        $station['lat'] = $metadata['latitude'] ?? ($metadata['lat'] ?? null);
        $station['lon'] = $metadata['longitude'] ?? ($metadata['lon'] ?? null);
        $station['country'] = $metadata['country_name'] ?? ($metadata['country'] ?? null);
        return $station;
    }
}
