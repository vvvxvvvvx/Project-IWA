<?php

declare(strict_types=1);

namespace App\Stations\Services;

use App\Core\Support\JsonFileStore;
use App\Stations\Repositories\StationMetadataRepository;

final class StationDataSnapshot
{
    private ?array $stations = null;
    private ?array $readings = null;
    private ?array $metadataByStn = null;
    private ?array $summariesSorted = null;

    public function __construct(
        private readonly StationMetadataRepository $metadataRepository = new StationMetadataRepository(),
    ) {}

    public function overview(): array
    {
        $readings = $this->readings();
        $temps = [];
        $peakCount = 0;
        $missingCount = 0;
        $latestMeasuredAt = null;

        foreach ($readings as $reading) {
            if (isset($reading['temp']) && $reading['temp'] !== null) {
                $temps[] = (float) $reading['temp'];
            }
            if ((int)($reading['is_temp_peak'] ?? 0) === 1) {
                $peakCount++;
            }
            if ((int)($reading['has_missing_data'] ?? 0) === 1) {
                $missingCount++;
            }
            $measuredAt = (string)($reading['measured_at'] ?? '');
            if ($measuredAt !== '' && ($latestMeasuredAt === null || $measuredAt > $latestMeasuredAt)) {
                $latestMeasuredAt = $measuredAt;
            }
        }

        return [
            'reading_count' => count($readings),
            'station_count' => count($this->stations()),
            'peak_count' => $peakCount,
            'missing_count' => $missingCount,
            'average_temp' => $temps === [] ? null : round(array_sum($temps) / count($temps), 2),
            'min_temp' => $temps === [] ? null : min($temps),
            'max_temp' => $temps === [] ? null : max($temps),
            'latest_measured_at' => $latestMeasuredAt,
        ];
    }

    public function summarizedStations(int $limit = 25): array
    {
        return array_slice($this->summariesSorted(), 0, $limit);
    }

    public function summarizedStationsAll(): array
    {
        return $this->summariesSorted();
    }

    public function topStations(int $limit = 5): array
    {
        $rows = array_map(static fn(array $station): array => [
            'stn' => $station['stn'],
            'name' => $station['name'],
            'reading_count' => (int)($station['reading_count'] ?? 0),
        ], $this->summariesSorted());

        usort($rows, static fn(array $a, array $b): int => $b['reading_count'] <=> $a['reading_count']);

        return array_slice($rows, 0, $limit);
    }

    public function latestReadings(int $limit = 12): array
    {
        $readings = $this->readings();
        usort($readings, static fn(array $a, array $b): int => strcmp((string)($b['measured_at'] ?? ''), (string)($a['measured_at'] ?? '')));

        $rows = [];
        $stationsById = $this->stationsById();
        foreach (array_slice($readings, 0, $limit) as $reading) {
            $station = $stationsById[(int)($reading['station_id'] ?? 0)] ?? null;
            $rows[] = [
                'stn' => $station['stn'] ?? '-',
                'name' => $station['name'] ?? 'Onbekend station',
                'location_label' => $station['location_label'] ?? 'Onbekend',
                'measured_at' => $reading['measured_at'] ?? null,
                'temp' => $reading['temp'] ?? null,
                'dewp' => $reading['dewp'] ?? null,
                'visib' => $reading['visib'] ?? null,
                'wdsp' => $reading['wdsp'] ?? null,
                'prcp' => $reading['prcp'] ?? null,
                'has_missing_data' => $reading['has_missing_data'] ?? 0,
                'is_temp_peak' => $reading['is_temp_peak'] ?? 0,
            ];
        }

        return $rows;
    }

    public function latestFlaggedReadings(int $limit = 12): array
    {
        $readings = array_values(array_filter($this->readings(), static fn(array $row): bool =>
            (int)($row['has_missing_data'] ?? 0) === 1 || (int)($row['is_temp_peak'] ?? 0) === 1
        ));

        usort($readings, static fn(array $a, array $b): int => strcmp((string)($b['measured_at'] ?? ''), (string)($a['measured_at'] ?? '')));
        $stationsById = $this->stationsById();

        return array_map(static function (array $reading) use ($stationsById): array {
            $station = $stationsById[(int)($reading['station_id'] ?? 0)] ?? null;

            return [
                'stn' => $station['stn'] ?? '-',
                'name' => $station['name'] ?? 'Onbekend station',
                'measured_at' => $reading['measured_at'] ?? null,
                'temp' => $reading['temp'] ?? null,
                'has_missing_data' => $reading['has_missing_data'] ?? 0,
                'is_temp_peak' => $reading['is_temp_peak'] ?? 0,
            ];
        }, array_slice($readings, 0, $limit));
    }

    public function stationByStn(string $stn): ?array
    {
        foreach ($this->stations() as $station) {
            if ((string)($station['stn'] ?? '') === (string)$stn) {
                return $station;
            }
        }

        $metadata = $this->metadataByStn()[(string)$stn] ?? null;
        if ($metadata === null) {
            return null;
        }

        return $this->decorateStation([
            'id' => 0,
            'stn' => (string)$stn,
            'name' => 'Weerstation ' . $stn,
            'location_label' => 'Onbekend (alleen STN aanwezig)',
            'first_seen_at' => null,
            'last_seen_at' => null,
        ]);
    }

    public function stationsById(): array
    {
        $map = [];
        foreach ($this->stations() as $station) {
            $map[(int)($station['id'] ?? 0)] = $station;
        }
        return $map;
    }

    private function stations(): array
    {
        if ($this->stations !== null) {
            return $this->stations;
        }

        $stations = array_map(fn(array $station): array => $this->decorateStation($station), JsonFileStore::all('stations'));
        usort($stations, static fn(array $a, array $b): int => strcmp((string)($b['last_seen_at'] ?? ''), (string)($a['last_seen_at'] ?? '')));
        $this->stations = $stations;

        return $this->stations;
    }

    private function readings(): array
    {
        return $this->readings ??= JsonFileStore::all('weather_readings');
    }

    private function metadataByStn(): array
    {
        if ($this->metadataByStn !== null) {
            return $this->metadataByStn;
        }

        $map = [];
        foreach (JsonFileStore::all('station_metadata') as $key => $value) {
            if (is_string($key) && is_array($value)) {
                $map[$key] = $value;
            }
        }
        $this->metadataByStn = $map;

        return $this->metadataByStn;
    }

    private function decorateStation(array $station): array
    {
        $metadata = $this->metadataByStn()[(string)($station['stn'] ?? '')] ?? $this->metadataRepository->findByStn((string)($station['stn'] ?? ''));
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

    private function summariesSorted(): array
    {
        if ($this->summariesSorted !== null) {
            return $this->summariesSorted;
        }

        $stationMap = [];
        foreach ($this->stations() as $station) {
            $stationId = (int)($station['id'] ?? 0);
            $stationMap[$stationId] = [
                'stn' => $station['stn'] ?? '',
                'name' => $station['name'] ?? ('Weerstation ' . ($station['stn'] ?? '-')),
                'location_label' => $station['location_label'] ?? 'Onbekend (alleen STN aanwezig)',
                'lat' => $station['lat'] ?? null,
                'lon' => $station['lon'] ?? null,
                'country' => $station['country'] ?? null,
                'last_seen_at' => $station['last_seen_at'] ?? null,
                'measured_at' => null,
                'temp' => null,
                'dewp' => null,
                'visib' => null,
                'wdsp' => null,
                'prcp' => null,
                'wnddir' => null,
                'has_missing_data' => 0,
                'is_temp_peak' => 0,
                'reading_count' => 0,
                'avg_temp_sum' => 0.0,
                'avg_temp_count' => 0,
            ];
        }

        foreach ($this->readings() as $reading) {
            $stationId = (int)($reading['station_id'] ?? 0);
            if (!isset($stationMap[$stationId])) {
                continue;
            }

            $summary = &$stationMap[$stationId];
            $summary['reading_count']++;

            if (isset($reading['temp']) && $reading['temp'] !== null) {
                $summary['avg_temp_sum'] += (float)$reading['temp'];
                $summary['avg_temp_count']++;
            }

            $measuredAt = (string)($reading['measured_at'] ?? '');
            if ($measuredAt !== '' && (($summary['measured_at'] ?? null) === null || $measuredAt > (string)$summary['measured_at'])) {
                $summary['measured_at'] = $reading['measured_at'] ?? null;
                $summary['temp'] = $reading['temp'] ?? null;
                $summary['dewp'] = $reading['dewp'] ?? null;
                $summary['visib'] = $reading['visib'] ?? null;
                $summary['wdsp'] = $reading['wdsp'] ?? null;
                $summary['prcp'] = $reading['prcp'] ?? null;
                $summary['wnddir'] = $reading['wnddir'] ?? null;
                $summary['has_missing_data'] = $reading['has_missing_data'] ?? 0;
                $summary['is_temp_peak'] = $reading['is_temp_peak'] ?? 0;
            }
            unset($summary);
        }

        $summaries = [];
        foreach ($stationMap as $summary) {
            $summary['avg_temp'] = $summary['avg_temp_count'] > 0
                ? round($summary['avg_temp_sum'] / $summary['avg_temp_count'], 2)
                : null;
            unset($summary['avg_temp_sum'], $summary['avg_temp_count']);
            $summaries[] = $summary;
        }

        usort($summaries, static fn(array $a, array $b): int => strcmp((string)($b['measured_at'] ?? $b['last_seen_at'] ?? ''), (string)($a['measured_at'] ?? $a['last_seen_at'] ?? '')));

        $this->summariesSorted = $summaries;
        return $this->summariesSorted;
    }
}
