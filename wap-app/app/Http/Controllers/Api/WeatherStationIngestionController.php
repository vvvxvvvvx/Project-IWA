<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Measurement;
use App\Models\OriginalMeasurement;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WeatherStationIngestionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $rows = $payload['WEATHERDATA'] ?? null;

        if (!is_array($rows) || $rows === []) {
            return response()->json([
                'message' => 'Expected a JSON object with a non-empty WEATHERDATA array.',
            ], 422);
        }

        $processed = 0;
        $rejected = [];

        foreach ($rows as $index => $entry) {
            if (!is_array($entry)) {
                $rejected[] = [
                    'index' => $index,
                    'reason' => 'Each WEATHERDATA item must be an object.',
                ];
                continue;
            }

            $validator = Validator::make($entry, [
                'STN' => ['required'],
                'DATE' => ['required', 'date'],
                'TIME' => ['required', 'date_format:H:i:s'],
            ]);

            if ($validator->fails()) {
                $rejected[] = [
                    'index' => $index,
                    'reason' => $validator->errors()->first(),
                ];
                continue;
            }

            $station = trim((string) $entry['STN']);
            if (!Station::where('name', $station)->exists()) {
                $rejected[] = [
                    'index' => $index,
                    'reason' => "Unknown station '{$station}'.",
                ];
                continue;
            }

            $rawTemperature = $this->toNullableFloat($entry['TEMP'] ?? null);
            $missingField = $this->firstMissingField($entry);
            $isInvalidTemperature = $rawTemperature !== null && ($rawTemperature < -89.4 || $rawTemperature > 58.0);

            $measurement = Measurement::create([
                'station' => $station,
                'date' => $entry['DATE'],
                'time' => $entry['TIME'],
                'temperature' => $isInvalidTemperature ? null : $rawTemperature,
                'dewpoint_temperature' => $this->toNullableFloat($entry['DEWP'] ?? null),
                'air_pressure_station' => $this->toNullableFloat($entry['STP'] ?? null),
                'air_pressure_sea_level' => $this->toNullableFloat($entry['SLP'] ?? null),
                'visibility' => $this->toNullableFloat($entry['VISIB'] ?? null),
                'wind_speed' => $this->toNullableFloat($entry['WDSP'] ?? null),
                'percipation' => $this->toNullableFloat($entry['PRCP'] ?? null),
                'snow_depth' => $this->toNullableFloat($entry['SNDP'] ?? null),
                'conditions' => $this->toNullableString($entry['FRSHTT'] ?? null),
                'cloud_cover' => $this->toNullableFloat($entry['CLDC'] ?? null),
                'wind_direction' => $this->toNullableInt($entry['WNDDIR'] ?? null),
            ]);

            if ($missingField !== null || $isInvalidTemperature) {
                OriginalMeasurement::create([
                    'corrected_measurement' => $measurement->id,
                    'missing_field' => $missingField,
                    'inavlid_temperature' => $isInvalidTemperature ? $rawTemperature : null,
                ]);
            }

            $processed++;
        }

        return response()->json([
            'message' => 'Weather station data processed.',
            'received' => count($rows),
            'processed' => $processed,
            'rejected' => $rejected,
        ], 201);
    }

    private function firstMissingField(array $entry): ?string
    {
        $fieldMap = [
            'DEWP' => 'dewpoint_temperature',
            'STP' => 'air_pressure_station',
            'SLP' => 'air_pressure_sea_level',
            'VISIB' => 'visibility',
            'WDSP' => 'wind_speed',
            'PRCP' => 'percipation',
            'SNDP' => 'snow_depth',
            'FRSHTT' => 'conditions',
            'CLDC' => 'cloud_cover',
            'WNDDIR' => 'wind_direction',
        ];

        foreach ($fieldMap as $sourceField => $databaseField) {
            if ($this->isNone($entry[$sourceField] ?? null)) {
                return $databaseField;
            }
        }

        return null;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($this->isNone($value)) {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($this->isNone($value)) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function toNullableString(mixed $value): ?string
    {
        if ($this->isNone($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function isNone(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return strcasecmp(trim((string) $value), 'None') === 0;
    }
}
