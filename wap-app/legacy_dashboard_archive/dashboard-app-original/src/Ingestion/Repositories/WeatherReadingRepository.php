<?php

declare(strict_types=1);

namespace App\Ingestion\Repositories;

use App\Core\Support\JsonFileStore;
use App\Stations\Repositories\StationRepository;

final class WeatherReadingRepository
{
    public function create(array $record): void
    {
        $records = JsonFileStore::all('weather_readings');
        $record['id'] = JsonFileStore::nextId('weather_readings');
        $records[] = $record;
        JsonFileStore::write('weather_readings', $records);
    }

    public function latestByStation(string $stn, int $limit = 50): array
    {
        $stationRepo = new StationRepository();
        $station = $stationRepo->findByStn($stn);
        if ($station === null) {
            return [];
        }
        $stationId = (int) $station['id'];
        $readings = array_values(array_filter(JsonFileStore::all('weather_readings'), static fn (array $row): bool => (int) $row['station_id'] === $stationId));
        usort($readings, static fn (array $a, array $b): int => strcmp((string) ($b['measured_at'] ?? ''), (string) ($a['measured_at'] ?? '')));
        $readings = array_slice($readings, 0, $limit);
        foreach ($readings as &$reading) {
            $reading['stn'] = $stn;
            $reading['name'] = $station['name'];
            $reading['location_label'] = $station['location_label'] ?? 'Onbekend';
        }
        return $readings;
    }

    public function recentValuesByStationField(int $stationId, string $field, int $limit = 30): array
    {
        $readings = array_values(array_filter(
            JsonFileStore::all('weather_readings'),
            static fn (array $row): bool => (int) ($row['station_id'] ?? 0) === $stationId && array_key_exists($field, $row) && $row[$field] !== null
        ));
        usort($readings, static fn (array $a, array $b): int => strcmp((string) ($b['measured_at'] ?? ''), (string) ($a['measured_at'] ?? '')));
        $readings = array_slice($readings, 0, $limit);
        return array_values(array_map(static fn(array $row) => (float) $row[$field], array_reverse($readings)));
    }

    public function recentAverageTemperatureByBatchWindow(int $limit = 24): array
    {
        $groups = [];
        foreach (JsonFileStore::all('weather_readings') as $row) {
            if (!isset($row['measured_at']) || $row['temp'] === null) {
                continue;
            }
            $bucket = substr((string) $row['measured_at'], 0, 16);
            $groups[$bucket][] = (float) $row['temp'];
        }
        krsort($groups);
        $groups = array_slice($groups, 0, $limit, true);
        $result = [];
        foreach (array_reverse($groups, true) as $bucket => $temps) {
            $result[] = ['bucket' => $bucket, 'average_temp' => round(array_sum($temps) / max(1, count($temps)), 2)];
        }
        return $result;
    }

    public function overview(): array
    {
        $readings = JsonFileStore::all('weather_readings');
        $stations = JsonFileStore::all('stations');
        $temps = array_values(array_filter(array_map(static fn (array $row) => $row['temp'] ?? null, $readings), static fn ($value) => $value !== null));
        $moments = array_values(array_filter(array_map(static fn (array $row) => $row['measured_at'] ?? null, $readings)));
        return [
            'reading_count' => count($readings),
            'station_count' => count($stations),
            'peak_count' => count(array_filter($readings, static fn (array $row): bool => (int) ($row['is_temp_peak'] ?? 0) === 1)),
            'missing_count' => count(array_filter($readings, static fn (array $row): bool => (int) ($row['has_missing_data'] ?? 0) === 1)),
            'average_temp' => $temps === [] ? null : round(array_sum($temps) / count($temps), 2),
            'min_temp' => $temps === [] ? null : min($temps),
            'max_temp' => $temps === [] ? null : max($temps),
            'latest_measured_at' => $moments === [] ? null : max($moments),
        ];
    }

    public function latestReadingsTable(int $limit = 12): array
    {
        $stationsById = [];
        foreach (JsonFileStore::all('stations') as $station) {
            $stationsById[(int) $station['id']] = $station;
        }
        $readings = JsonFileStore::all('weather_readings');
        usort($readings, static fn (array $a, array $b): int => strcmp((string) ($b['measured_at'] ?? ''), (string) ($a['measured_at'] ?? '')));
        $readings = array_slice($readings, 0, $limit);
        $rows = [];
        foreach ($readings as $reading) {
            $station = $stationsById[(int) $reading['station_id']] ?? ['stn' => '-', 'name' => 'Unknown', 'location_label' => 'Onbekend'];
            $rows[] = [
                'stn' => $station['stn'],
                'name' => $station['name'],
                'location_label' => $station['location_label'] ?? 'Onbekend',
                'measured_at' => $reading['measured_at'],
                'temp' => $reading['temp'],
                'dewp' => $reading['dewp'],
                'visib' => $reading['visib'] ?? null,
                'wdsp' => $reading['wdsp'],
                'prcp' => $reading['prcp'],
                'has_missing_data' => $reading['has_missing_data'],
                'is_temp_peak' => $reading['is_temp_peak'],
            ];
        }
        return $rows;
    }

    public function byStationPeriod(string $stn, ?string $fromDate = null, ?string $toDate = null, int $limit = 500): array
    {
        $rows = $this->latestByStation($stn, 5000);
        $filtered = array_values(array_filter($rows, static function(array $row) use ($fromDate, $toDate): bool {
            $date = substr((string)($row['measured_at'] ?? ''), 0, 10);
            if ($fromDate !== null && $fromDate !== '' && $date < $fromDate) return false;
            if ($toDate !== null && $toDate !== '' && $date > $toDate) return false;
            return true;
        }));
        return array_slice($filtered, 0, $limit);
    }

    public function latestFlaggedReadings(int $limit = 12): array
    {
        $stationsById = [];
        foreach (JsonFileStore::all('stations') as $station) {
            $stationsById[(int) $station['id']] = $station;
        }
        $readings = array_values(array_filter(JsonFileStore::all('weather_readings'), static fn (array $row): bool => (int) ($row['has_missing_data'] ?? 0) === 1 || (int) ($row['is_temp_peak'] ?? 0) === 1));
        usort($readings, static fn (array $a, array $b): int => strcmp((string) ($b['measured_at'] ?? ''), (string) ($a['measured_at'] ?? '')));
        $readings = array_slice($readings, 0, $limit);
        return array_map(static function (array $reading) use ($stationsById): array {
            $station = $stationsById[(int) $reading['station_id']] ?? ['stn' => '-', 'name' => 'Unknown'];
            return [
                'stn' => $station['stn'],
                'name' => $station['name'],
                'measured_at' => $reading['measured_at'],
                'temp' => $reading['temp'],
                'has_missing_data' => $reading['has_missing_data'],
                'is_temp_peak' => $reading['is_temp_peak'],
            ];
        }, $readings);
    }
}
