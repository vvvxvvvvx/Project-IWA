<?php

/*
|--------------------------------------------------------------------------
| MeasurementController
|--------------------------------------------------------------------------
|
| Verwerkt binnenkomende meetdata en schrijft die weg naar de meettabellen.
| Als je hier veldnamen of validatie wijzigt, controleer dan ook:
| - routes/web.php of routes/api.php voor de endpoint-koppeling
| - de measurement/original_measurement tabellen in de database
| - eventuele generator- of dashboardcode die deze velden verwacht
|
*/
namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Models\OriginalMeasurement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MeasurementController extends Controller
{
    // MAX weerstations per batch
    const MAX_STATIONS = 10;

    // vorige metingen voor extrapolatie
    const EXTRAPOLATION_COUNT = 30;

    // Maximale afwijking TEMP (20%)
    const TEMPERATURE_DEVIATION = 0.20;

    /**
     * Mapping van generator veldnamen naar database veldnamen.
     */
    private array $fieldMap = [
        'STN'    => 'station',
        'DATE'   => 'date',
        'TIME'   => 'time',
        'TEMP'   => 'temperature',
        'DEWP'   => 'dewpoint_temperature',
        'STP'    => 'air_pressure_station',
        'SLP'    => 'air_pressure_sea_level',
        'VISIB'  => 'visibility',
        'WDSP'   => 'wind_speed',
        'PRCP'   => 'percipation',
        'SNDP'   => 'snow_depth',
        'FRSHTT' => 'conditions',
        'CLDC'   => 'cloud_cover',
        'WNDDIR' => 'wind_direction',
    ];

    private function celsiusToKelvin(float $celsius): float
    {
        return $celsius + 273.15;
    }

    private function kelvinToCelsius(float $kelvin): float
    {
        return $kelvin - 273.15;
    }

    /**
     * Zet generator-formaat om naar intern formaat en vervang "None" strings door null.
     */
    private function normalizeData(array $raw): array
    {
        $normalized = [];

        foreach ($raw as $generatorKey => $value) {
            $dbKey = $this->fieldMap[$generatorKey] ?? strtolower($generatorKey);

            // Vervang "None" (string) door null
            if ($value === 'None' || $value === 'none') {
                $value = null;
            }

            $normalized[$dbKey] = $value;
        }

        return $normalized;
    }

    public function store(Request $request): JsonResponse
    {
        // FIX 1: Haal de WEATHERDATA array op uit het JSON object
        $batch = $request->json('WEATHERDATA');

        if (!is_array($batch) || count($batch) === 0) {
            return response()->json([
                'error' => 'Expected a JSON object with a WEATHERDATA array.'
            ], 422);
        }

        if (count($batch) > self::MAX_STATIONS) {
            return response()->json([
                'error' => 'Maximum of ' . self::MAX_STATIONS . ' stations allowed per batch.'
            ], 422);
        }

        $results = [];

        DB::transaction(function () use ($batch, &$results) {
            foreach ($batch as $rawData) {
                // FIX 2 & 3: Normaliseer veldnamen en vervang "None" door null
                $data   = $this->normalizeData($rawData);
                $result = $this->processMeasurement($data);
                $results[] = $result;
            }
        });

        return response()->json([
            'message'   => 'Batch processed.',
            'processed' => count($results),
            'results'   => $results,
        ], 201);
    }

    /**
     * Verwerk één meting: corrigeer ontbrekende waarden en controleer temperatuur.
     */
    private function processMeasurement(array $data): array
    {
        $station     = $data['station'] ?? null;
        $corrections = [];

        // Velden die geëxtrapoleerd kunnen worden
        $extrapolatableFields = [
            'temperature',
            'dewpoint_temperature',
            'air_pressure_station',
            'air_pressure_sea_level',
            'visibility',
            'wind_speed',
            'percipation',
            'snow_depth',
            'cloud_cover',
            'wind_direction',
        ];

        // Haal de 30 vorige metingen op voor dit station
        $previousMeasurements = Measurement::where('station', $station)
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->limit(self::EXTRAPOLATION_COUNT)
            ->get();

        // Stap 1: Extrapoleer ontbrekende waarden
        foreach ($extrapolatableFields as $field) {
            // FIX 3: null check werkt nu correct omdat "None" al omgezet is
            if (!isset($data[$field]) || $data[$field] === null) {

                if (in_array($field, ['temperature', 'dewpoint_temperature'])) {
                    $extrapolatedValue = $this->extrapolate($previousMeasurements, $field, true);
                } else {
                    $extrapolatedValue = $this->extrapolate($previousMeasurements, $field);
                }

                if ($extrapolatedValue !== null) {
                    $data[$field] = $extrapolatedValue;
                    $corrections[$field] = [
                        'reason'    => 'missing',
                        'original'  => null,
                        'corrected' => $extrapolatedValue,
                    ];
                }
            }
        }

        // Controleer temperatuurafwijking (berekening in Kelvin)
        if (isset($data['temperature']) && $previousMeasurements->count() > 0) {

            $extrapolatedCelsius = $this->extrapolate($previousMeasurements, 'temperature');

            if ($extrapolatedCelsius !== null) {
                $inputKelvin        = $this->celsiusToKelvin($data['temperature']);
                $extrapolatedKelvin = $this->celsiusToKelvin($extrapolatedCelsius);

                $deviation = abs($inputKelvin - $extrapolatedKelvin) / $extrapolatedKelvin;

                if ($deviation >= self::TEMPERATURE_DEVIATION) {
                    $originalCelsius = $data['temperature'];

                    $direction       = $inputKelvin > $extrapolatedKelvin ? 1 : -1;
                    $correctedKelvin = $extrapolatedKelvin * (1 + ($direction * self::TEMPERATURE_DEVIATION));

                    $data['temperature'] = $this->kelvinToCelsius($correctedKelvin);

                    $corrections['temperature'] = [
                        'reason'               => 'deviation_too_large',
                        'original_celsius'     => $originalCelsius,
                        'original_kelvin'      => round($inputKelvin, 4),
                        'corrected_celsius'    => round($data['temperature'], 4),
                        'corrected_kelvin'     => round($correctedKelvin, 4),
                        'extrapolated_kelvin'  => round($extrapolatedKelvin, 4),
                        'deviation_percentage' => round($deviation * 100, 2),
                    ];
                }
            }
        }

        // Sla de gecorrigeerde meting op
        $measurement = Measurement::create([
            'station'                => $data['station'],
            'date'                   => $data['date'],
            'time'                   => $data['time'],
            'temperature'            => $data['temperature'] ?? null,
            'dewpoint_temperature'   => $data['dewpoint_temperature'] ?? null,
            'air_pressure_station'   => $data['air_pressure_station'] ?? null,
            'air_pressure_sea_level' => $data['air_pressure_sea_level'] ?? null,
            'visibility'             => $data['visibility'] ?? null,
            'wind_speed'             => $data['wind_speed'] ?? null,
            'percipation'            => $data['percipation'] ?? null,
            'snow_depth'             => $data['snow_depth'] ?? null,
            'conditions'             => $data['conditions'] ?? null,
            'cloud_cover'            => $data['cloud_cover'] ?? null,
            'wind_direction'         => $data['wind_direction'] ?? null,
        ]);

        // Sla originele waarden op als er correcties zijn
        if (!empty($corrections)) {
            foreach ($corrections as $field => $correction) {
                OriginalMeasurement::create([
                    'corrected_measurement' => $measurement->id,
                    'missing_field'         => $correction['reason'] === 'missing' ? $field : null,
                    'inavlid_temperature'   => $correction['reason'] === 'deviation_too_large'
                                                ? $correction['original_celsius']
                                                : null,
                ]);
            }
        }

        return [
            'station'        => $station,
            'measurement_id' => $measurement->id,
            'corrections'    => $corrections,
        ];
    }

    /**
     * Extrapoleer een waarde op basis van de 30 vorige metingen (recente metingen wegen zwaarder).
     */
    private function extrapolate($measurements, string $field, bool $useKelvin = false): ?float
    {
        $values = $measurements
            ->whereNotNull($field)
            ->pluck($field)
            ->values();

        if ($values->count() === 0) {
            return null;
        }

        if ($useKelvin) {
            $values = $values->map(fn($v) => $this->celsiusToKelvin($v));
        }

        $totalWeight = 0;
        $weightedSum = 0;
        $count       = $values->count();

        foreach ($values as $index => $value) {
            $weight      = $count - $index;
            $weightedSum += $value * $weight;
            $totalWeight += $weight;
        }

        $result = $totalWeight > 0 ? $weightedSum / $totalWeight : null;

        return ($result !== null && $useKelvin) ? $this->kelvinToCelsius($result) : $result;
    }
}