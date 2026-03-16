<?php

declare(strict_types=1);

namespace App\Stations\Repositories;

use App\Core\Support\JsonFileStore;

final class StationRepository
{
    public function __construct(private readonly StationMetadataRepository $metadata = new StationMetadataRepository()) {}

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
        foreach (JsonFileStore::all('stations') as $station) {
            if ((string)($station['stn'] ?? null) === (string)$stn) {
                return $this->applyMetadata($station);
            }
        }
        $metadata = $this->metadata->findByStn((string)$stn);
        if ($metadata !== null) {
            return $this->applyMetadata([
                'id' => 0,
                'stn' => (string)$stn,
                'name' => 'Weerstation ' . $stn,
                'location_label' => 'Onbekend (alleen STN aanwezig)',
                'first_seen_at' => null,
                'last_seen_at' => null,
            ]);
        }
        return null;
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
        $stations = JsonFileStore::all('stations');
        $readings = JsonFileStore::all('weather_readings');
        usort($stations, static fn (array $a, array $b): int => strcmp((string)($b['last_seen_at'] ?? ''), (string)($a['last_seen_at'] ?? '')));

        $result = [];
        foreach (array_slice($stations, 0, $limit) as $station) {
            $station = $this->applyMetadata($station);
            $stationReadings = array_values(array_filter($readings, static fn (array $reading): bool => (int)$reading['station_id'] === (int)$station['id']));
            usort($stationReadings, static fn (array $a, array $b): int => strcmp((string)($b['measured_at'] ?? ''), (string)($a['measured_at'] ?? '')));
            $latest = $stationReadings[0] ?? [];
            $temps = array_values(array_filter(array_map(static fn (array $row) => $row['temp'] ?? null, $stationReadings), static fn ($value) => $value !== null));
            $result[] = [
                'stn' => $station['stn'],
                'name' => $station['name'],
                'location_label' => $station['location_label'] ?? 'Onbekend (alleen STN aanwezig)',
                'lat' => $station['lat'] ?? null,
                'lon' => $station['lon'] ?? null,
                'country' => $station['country'] ?? null,
                'last_seen_at' => $station['last_seen_at'],
                'measured_at' => $latest['measured_at'] ?? null,
                'temp' => $latest['temp'] ?? null,
                'dewp' => $latest['dewp'] ?? null,
                'visib' => $latest['visib'] ?? null,
                'wdsp' => $latest['wdsp'] ?? null,
                'prcp' => $latest['prcp'] ?? null,
                'wnddir' => $latest['wnddir'] ?? null,
                'has_missing_data' => $latest['has_missing_data'] ?? 0,
                'is_temp_peak' => $latest['is_temp_peak'] ?? 0,
                'reading_count' => count($stationReadings),
                'avg_temp' => $temps === [] ? null : round(array_sum($temps) / count($temps), 2),
            ];
        }
        return $result;
    }

    public function topByReadingCount(int $limit = 5): array
    {
        $stations = JsonFileStore::all('stations');
        $readings = JsonFileStore::all('weather_readings');
        $rows = [];
        foreach ($stations as $station) {
            $station = $this->applyMetadata($station);
            $count = 0;
            foreach ($readings as $reading) {
                if ((int)$reading['station_id'] === (int)$station['id']) $count++;
            }
            $rows[] = ['stn' => $station['stn'], 'name' => $station['name'], 'reading_count' => $count];
        }
        usort($rows, static fn(array $a, array $b): int => $b['reading_count'] <=> $a['reading_count']);
        return array_slice($rows, 0, $limit);
    }

    private function applyMetadata(array $station): array
    {
        $metadata = $this->metadata->findByStn((string)$station['stn']);
        if ($metadata === null) return $station;
        $parts = array_filter([$metadata['name'] ?? null, $metadata['administrative_region1'] ?? null, $metadata['country_name'] ?? ($metadata['country'] ?? null)]);
        if ($parts !== []) {
            $station['location_label'] = trim(implode(', ', array_unique($parts))) !== '' ? implode(', ', array_unique($parts)) : ($station['location_label'] ?? 'Onbekend (alleen STN aanwezig)');
        }
        $station['lat'] = $metadata['latitude'] ?? ($metadata['lat'] ?? null);
        $station['lon'] = $metadata['longitude'] ?? ($metadata['lon'] ?? null);
        $station['country'] = $metadata['country_name'] ?? ($metadata['country'] ?? null);
        return $station;
    }
}
