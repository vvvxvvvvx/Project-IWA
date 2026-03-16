<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeatherDataController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->json()->all();
        $weatherData = $data['WEATHERDATA'] ?? [];

        foreach ($weatherData as $entry) {
            $stn = isset($entry['STN']) ? (string)(int)$entry['STN'] : null;
            if (!$stn || !DB::table('station')->where('name', $stn)->exists()) {
                continue;
            }

            $rawTemp = $this->parseValue($entry['TEMP'] ?? null);
            $dewp    = $this->parseValue($entry['DEWP'] ?? null);
            $stp     = $this->parseValue($entry['STP'] ?? null);
            $slp     = $this->parseValue($entry['SLP'] ?? null);
            $visib   = $this->parseValue($entry['VISIB'] ?? null);
            $wdsp    = $this->parseValue($entry['WDSP'] ?? null);
            $prcp    = $this->parseValue($entry['PRCP'] ?? null);
            $sndp    = $this->parseValue($entry['SNDP'] ?? null);
            $cldc    = $this->parseValue($entry['CLDC'] ?? null);
            $wddir   = $this->parseValue($entry['WNDDIR'] ?? null);
            $frshtt  = (!$this->isNone($entry['FRSHTT'] ?? null) && isset($entry['FRSHTT']))
                ? $entry['FRSHTT']
                : null;

            // Temperatures outside Earth's recorded extremes are treated as peaks
            $isTempPeak = $rawTemp !== null && ($rawTemp < -89.4 || $rawTemp > 58.0);
            $correctedTemp = $isTempPeak ? null : $rawTemp;

            // Find the first missing (None) field
            $missingField = null;
            $fieldMap = [
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
            foreach ($fieldMap as $key => $dbField) {
                if ($this->isNone($entry[$key] ?? null)) {
                    $missingField = $dbField;
                    break;
                }
            }

            $measurementId = DB::table('measurement')->insertGetId([
                'station'                => $stn,
                'date'                   => $entry['DATE'] ?? null,
                'time'                   => $entry['TIME'] ?? null,
                'temperature'            => $correctedTemp,
                'dewpoint_temperature'   => $dewp,
                'air_pressure_station'   => $stp,
                'air_pressure_sea_level' => $slp,
                'visibility'             => $visib,
                'wind_speed'             => $wdsp,
                'percipation'            => $prcp,
                'snow_depth'             => $sndp,
                'conditions'             => $frshtt,
                'cloud_cover'            => $cldc,
                'wind_direction'         => $wddir,
            ]);

            if ($isTempPeak || $missingField !== null) {
                DB::table('original_measurement')->insert([
                    'corrected_measurement' => $measurementId,
                    'missing_field'         => $missingField,
                    'inavlid_temperature'   => $isTempPeak ? $rawTemp : null,
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function parseValue(mixed $value): ?float
    {
        if ($this->isNone($value)) {
            return null;
        }
        return is_numeric($value) ? (float) $value : null;
    }

    private function isNone(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        return trim((string) $value) === 'None';
    }
}
