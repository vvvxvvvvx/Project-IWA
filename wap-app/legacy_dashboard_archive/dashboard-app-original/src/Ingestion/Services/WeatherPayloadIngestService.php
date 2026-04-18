<?php

declare(strict_types=1);

namespace App\Ingestion\Services;

use App\Ingestion\Repositories\CorrectionLogRepository;
use App\Ingestion\Repositories\IngestBatchRepository;
use App\Ingestion\Repositories\OriginalMeasurementRepository;
use App\Stations\Repositories\StationRepository;
use App\Ingestion\Repositories\WeatherReadingRepository;

final class WeatherPayloadIngestService
{
    public function __construct(
        private readonly StationRepository $stations = new StationRepository(),
        private readonly WeatherReadingRepository $readings = new WeatherReadingRepository(),
        private readonly IngestBatchRepository $batches = new IngestBatchRepository(),
        private readonly OriginalMeasurementRepository $originals = new OriginalMeasurementRepository(),
        private readonly CorrectionLogRepository $corrections = new CorrectionLogRepository(),
    ) {}

    public function ingest(array $payload): array
    {
        $weatherData = $payload['WEATHERDATA'] ?? [];
        if (!is_array($weatherData) || $weatherData === []) {
            throw new \InvalidArgumentException('Payload does not contain a WEATHERDATA array.');
        }

        $now = gmdate('c');
        $peakCount = 0;
        $missingCount = 0;
        $correctionCount = 0;
        $tempValues = [];
        $stationCount = 0;

        foreach ($weatherData as $row) {
            if (!is_array($row) || !isset($row['STN'], $row['DATE'], $row['TIME'])) {
                continue;
            }
            $station = $this->stations->findOrCreateByStn((string) $row['STN']);
            $stationCount++;
            $normalized = $this->normalizeRow($row, $now, $station);
            $peakCount += $normalized['record']['is_temp_peak'] ? 1 : 0;
            $missingCount += $normalized['record']['has_missing_data'] ? 1 : 0;
            $correctionCount += count($normalized['corrections']);
            if ($normalized['record']['temp'] !== null) {
                $tempValues[] = $normalized['record']['temp'];
            }
            $this->readings->create(['station_id' => (int) $station['id']] + $normalized['record']);
            foreach ($normalized['corrections'] as $correction) {
                $this->originals->create([
                    'stn' => (string) $station['stn'],
                    'field' => $correction['field'],
                    'original_value' => $correction['original_value'],
                    'corrected_value' => $correction['corrected_value'],
                    'created_at' => $now,
                ]);
                $this->corrections->create([
                    'stn' => (string) $station['stn'],
                    'field' => $correction['field'],
                    'reason' => $correction['reason'],
                    'original_value' => $correction['original_value'],
                    'corrected_value' => $correction['corrected_value'],
                    'created_at' => $now,
                ]);
            }
        }

        $averageTemp = $tempValues === [] ? null : round(array_sum($tempValues) / count($tempValues), 2);
        $this->batches->create([
            'received_at' => $now,
            'station_count' => $stationCount,
            'missing_value_count' => $missingCount,
            'peak_count' => $peakCount,
            'correction_count' => $correctionCount,
            'average_temp' => $averageTemp,
            'raw_payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);

        return [
            'received_at' => $now,
            'station_count' => $stationCount,
            'missing_value_count' => $missingCount,
            'peak_count' => $peakCount,
            'correction_count' => $correctionCount,
            'average_temp' => $averageTemp,
        ];
    }

    private function normalizeRow(array $row, string $createdAt, array $station): array
    {
        $stationId = (int) ($station['id'] ?? 0);
        $corrections = [];
        $hadMissingData = false;

        $extrapolated = [
            'TEMP' => $this->extrapolateField($stationId, 'temp'),
            'DEWP' => $this->extrapolateField($stationId, 'dewp'),
            'STP' => $this->extrapolateField($stationId, 'stp'),
            'SLP' => $this->extrapolateField($stationId, 'slp'),
            'VISIB' => $this->extrapolateField($stationId, 'visib'),
            'WDSP' => $this->extrapolateField($stationId, 'wdsp'),
            'PRCP' => $this->extrapolateField($stationId, 'prcp'),
            'SNDP' => $this->extrapolateField($stationId, 'sndp'),
            'CLDC' => $this->extrapolateField($stationId, 'cldc'),
            'WNDDIR' => $this->extrapolateField($stationId, 'wnddir'),
        ];

        $temperature = $this->normalizeTemperatureField($row['TEMP'] ?? null, $extrapolated['TEMP'], $corrections, $hadMissingData);
        $dewpoint = $this->normalizeNumericField('DEWP', $row['DEWP'] ?? null, -100, 50, $corrections, $extrapolated['DEWP'], $hadMissingData);
        $stp = $this->normalizeNumericField('STP', $row['STP'] ?? null, 800, 1100, $corrections, $extrapolated['STP'], $hadMissingData);
        $slp = $this->normalizeNumericField('SLP', $row['SLP'] ?? null, 800, 1100, $corrections, $extrapolated['SLP'], $hadMissingData);
        $visib = $this->normalizeNumericField('VISIB', $row['VISIB'] ?? null, 0, 100, $corrections, $extrapolated['VISIB'], $hadMissingData);
        $wdsp = $this->normalizeNumericField('WDSP', $row['WDSP'] ?? null, 0, 150, $corrections, $extrapolated['WDSP'], $hadMissingData);
        $prcp = $this->normalizeNumericField('PRCP', $row['PRCP'] ?? null, 0, 500, $corrections, $extrapolated['PRCP'], $hadMissingData);
        $sndp = $this->normalizeNumericField('SNDP', $row['SNDP'] ?? null, 0, 1000, $corrections, $extrapolated['SNDP'], $hadMissingData);
        $cldc = $this->normalizeNumericField('CLDC', $row['CLDC'] ?? null, 0, 100, $corrections, $extrapolated['CLDC'], $hadMissingData);
        $wnddir = $this->normalizeNumericField('WNDDIR', $row['WNDDIR'] ?? null, 0, 360, $corrections, $extrapolated['WNDDIR'], $hadMissingData, true);
        $frshtt = $this->isMissing($row['FRSHTT'] ?? null) ? null : (string) $row['FRSHTT'];
        if ($frshtt !== null && !preg_match('/^[01]{6}$/', $frshtt)) {
            $corrections[] = ['field' => 'FRSHTT', 'reason' => 'Ongeldig formaat, teruggezet naar null', 'original_value' => $frshtt, 'corrected_value' => null];
            $frshtt = null;
        }

        return [
            'record' => [
                'measured_at' => sprintf('%sT%s+00:00', $row['DATE'], $row['TIME']),
                'temp' => $temperature,
                'dewp' => $dewpoint,
                'stp' => $stp,
                'slp' => $slp,
                'visib' => $visib,
                'wdsp' => $wdsp,
                'prcp' => $prcp,
                'sndp' => $sndp,
                'frshtt' => $frshtt,
                'cldc' => $cldc,
                'wnddir' => $wnddir,
                'is_temp_peak' => $this->looksLikePeakTemperature($temperature, $dewpoint),
                'has_missing_data' => $hadMissingData,
                'raw_payload_json' => json_encode($row, JSON_UNESCAPED_SLASHES),
                'created_at' => $createdAt,
            ],
            'corrections' => $corrections,
        ];
    }

    private function normalizeTemperatureField(mixed $value, ?float $extrapolated, array &$corrections, bool &$hadMissingData): ?float
    {
        if ($this->isMissing($value)) {
            $hadMissingData = true;
            if ($extrapolated !== null) {
                $corrected = round($extrapolated, 2);
                $corrections[] = [
                    'field' => 'TEMP',
                    'reason' => 'Ontbrekende temperatuur opgevuld via extrapolatie op basis van de 30 voorafgaande metingen',
                    'original_value' => null,
                    'corrected_value' => $corrected,
                ];
                return $corrected;
            }
            return null;
        }
        if (!is_numeric($value)) {
            $corrections[] = ['field' => 'TEMP', 'reason' => 'Niet-numerieke temperatuur, teruggezet naar null', 'original_value' => $value, 'corrected_value' => null];
            return null;
        }
        $floatValue = (float) $value;
        if ($extrapolated !== null) {
            $margin = max(0.1, abs($extrapolated) * 0.2);
            $deviation = abs($floatValue - $extrapolated);
            if ($deviation >= $margin) {
                $corrected = max($extrapolated - $margin, min($floatValue, $extrapolated + $margin));
                $corrected = round($corrected, 2);
                $corrections[] = [
                    'field' => 'TEMP',
                    'reason' => 'Temperatuur week 20% of meer af van de geëxtrapoleerde waarde en is begrensd tot geëxtrapoleerde waarde ±20%',
                    'original_value' => $floatValue,
                    'corrected_value' => $corrected,
                ];
                return $corrected;
            }
        }
        return round($floatValue, 2);
    }

    private function normalizeNumericField(string $field, mixed $value, float $min, float $max, array &$corrections, ?float $extrapolated, bool &$hadMissingData, bool $roundInteger = false): float|int|null
    {
        if ($this->isMissing($value)) {
            $hadMissingData = true;
            if ($extrapolated !== null) {
                $corrected = $roundInteger ? (int) round($extrapolated) : round($extrapolated, 2);
                $corrections[] = [
                    'field' => $field,
                    'reason' => 'Ontbrekende waarde opgevuld via extrapolatie op basis van de 30 voorafgaande metingen',
                    'original_value' => null,
                    'corrected_value' => $corrected,
                ];
                return $corrected;
            }
            return null;
        }
        if (!is_numeric($value)) {
            $corrections[] = ['field' => $field, 'reason' => 'Niet-numerieke waarde, teruggezet naar null', 'original_value' => $value, 'corrected_value' => null];
            return null;
        }
        $floatValue = (float) $value;
        $corrected = max($min, min($max, $floatValue));
        if ($corrected !== $floatValue) {
            $corrected = $roundInteger ? (int) round($corrected) : round($corrected, 2);
            $corrections[] = ['field' => $field, 'reason' => 'Waarde buiten verwacht bereik, begrensd', 'original_value' => $floatValue, 'corrected_value' => $corrected];
        }
        return $roundInteger ? (int) round($corrected) : round($corrected, 2);
    }

    private function extrapolateField(int $stationId, string $field): ?float
    {
        $values = $this->readings->recentValuesByStationField($stationId, $field, 30);
        if ($values === []) {
            return null;
        }
        if (count($values) === 1) {
            return round((float) $values[0], 2);
        }
        $first = (float) $values[0];
        $last = (float) $values[count($values) - 1];
        $avgStep = ($last - $first) / max(1, count($values) - 1);
        return round($last + $avgStep, 2);
    }

    private function isMissing(mixed $value): bool
    {
        if ($value === null) return true;
        if (is_string($value)) {
            $normalized = trim($value, " \t\n\r\0\x0B\"");
            return $normalized === '' || strcasecmp($normalized, 'none') === 0;
        }
        return false;
    }

    private function looksLikePeakTemperature(?float $temperature, ?float $dewpoint): bool
    {
        if ($temperature === null) return false;
        if ($temperature >= 45.0) return true;
        return $dewpoint !== null && ($temperature - $dewpoint) > 20.0;
    }
}
