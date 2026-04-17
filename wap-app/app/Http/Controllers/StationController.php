<?php

namespace App\Http\Controllers;

use App\Models\StationFault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StationController extends Controller
{
    public function index(Request $request)
    {
        $country = $request->query('country', 'NL');  // Standaard Nederland
        $location = $request->query('location', '');

        // Haal alle landen op voor dropdown
        $countriesQuery = DB::table('nearestlocation as nl')
            ->join('country as c', 'c.country_code', '=', 'nl.country_code')
            ->select('c.country_code', 'c.country')
            ->distinct()
            ->orderBy('c.country');
        $countries = $countriesQuery->get();

        // Haal alle locaties op voor geselecteerde land
        $locationsQuery = DB::table('nearestlocation as nl')
            ->where('nl.country_code', $country)
            ->select('nl.name')
            ->distinct()
            ->orderBy('nl.name');
        $locations = $locationsQuery->get();

        $stationsQuery = DB::table('station')
            ->leftJoin('measurement as m', 'station.name', '=', 'm.station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->leftJoin('original_measurement as om', 'm.id', '=', 'om.corrected_measurement')
            ->select(
                'station.name as stn',
                'nl.name as location_label',
                'c.country as country_name',
                'station.latitude as lat',
                'station.longitude as lon',
                DB::raw("MAX(CONCAT(m.date, ' ', m.time)) as measured_at"),
                DB::raw('MAX(m.temperature) as temp'),
                DB::raw('ROUND(AVG(m.temperature), 1) as avg_temp'),
                DB::raw('MAX(m.visibility) as visib'),
                DB::raw('MAX(m.wind_speed) as wdsp'),
                DB::raw('MAX(m.wind_direction) as wnddir'),
                DB::raw('MAX(m.percipation) as prcp'),
                DB::raw('COUNT(m.id) as reading_count'),
                DB::raw('MAX(CASE WHEN om.missing_field IS NOT NULL THEN 1 ELSE 0 END) as has_missing_data'),
                DB::raw('MAX(CASE WHEN om.inavlid_temperature IS NOT NULL THEN 1 ELSE 0 END) as is_temp_peak'),
                DB::raw("CASE WHEN MAX(m.date) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END as is_online")
            )
            ->where('nl.country_code', $country)
            ->groupBy('station.name', 'nl.name', 'c.country', 'station.latitude', 'station.longitude');

        // Filter op locatie/plaats als geselecteerd
        if ($location) {
            $stationsQuery->where('nl.name', $location);
        }

        $stations = $stationsQuery
            ->orderBy('nl.name')
            ->get();

        return view('stations.station-list', [
            'stations' => $stations,
            'countries' => $countries,
            'locations' => $locations,
            'selectedCountry' => $country,
            'selectedLocation' => $location,
        ]);
    }

    public function faults()
    {
        $stations = DB::table('station')
            ->leftJoin('measurement as m', 'station.name', '=', 'm.station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->leftJoin('original_measurement as om', 'm.id', '=', 'om.corrected_measurement')
            ->select(
                'station.name as stn',
                'nl.name as location_label',
                'c.country as country_name',
                DB::raw("MAX(CONCAT(m.date, ' ', m.time)) as measured_at"),
                DB::raw('COUNT(m.id) as reading_count'),
                DB::raw('MAX(CASE WHEN om.missing_field IS NOT NULL THEN 1 ELSE 0 END) as has_missing_data'),
                DB::raw('MAX(CASE WHEN om.inavlid_temperature IS NOT NULL THEN 1 ELSE 0 END) as is_temp_peak'),
                DB::raw("CASE WHEN MAX(m.date) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END as is_online")
            )
            ->groupBy('station.name', 'nl.name', 'c.country')
            ->havingRaw("
                (CASE WHEN MAX(m.date) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) = 0
                OR MAX(CASE WHEN om.missing_field IS NOT NULL THEN 1 ELSE 0 END) = 1
                OR MAX(CASE WHEN om.inavlid_temperature IS NOT NULL THEN 1 ELSE 0 END) = 1
            ")
            ->orderBy('station.name')
            ->get();

        return view('stations.station-faults', [
            'stations' => $stations,
        ]);
    }

    public function faultsOffline()
    {
        $stations = DB::table('station')
            ->leftJoin('measurement as m', 'station.name', '=', 'm.station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->select(
                'station.name as stn',
                'nl.name as location_label',
                'c.country as country_name',
                DB::raw("MAX(CONCAT(m.date, ' ', m.time)) as measured_at"),
                DB::raw('COUNT(m.id) as reading_count')
            )
            ->groupBy('station.name', 'nl.name', 'c.country')
            ->havingRaw("CASE WHEN MAX(m.date) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END = 0")
            ->orderBy('station.name')
            ->get();

        return view('stations.station-offline', ['stations' => $stations]);
    }

    public function faultsMissing()
    {
        $stations = DB::table('station')
            ->leftJoin('measurement as m', 'station.name', '=', 'm.station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->leftJoin('original_measurement as om', 'm.id', '=', 'om.corrected_measurement')
            ->select(
                'station.name as stn',
                'nl.name as location_label',
                'c.country as country_name',
                DB::raw("MAX(CONCAT(m.date, ' ', m.time)) as measured_at"),
                DB::raw('COUNT(DISTINCT m.id) as reading_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN om.missing_field IS NOT NULL THEN om.id END) as missing_count')
            )
            ->groupBy('station.name', 'nl.name', 'c.country')
            ->havingRaw("MAX(CASE WHEN om.missing_field IS NOT NULL THEN 1 ELSE 0 END) = 1")
            ->orderBy('station.name')
            ->get();

        return view('stations.station-missing', ['stations' => $stations]);
    }

    public function faultsTemperature()
    {
        $stations = DB::table('station')
            ->leftJoin('measurement as m', 'station.name', '=', 'm.station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->leftJoin('original_measurement as om', 'm.id', '=', 'om.corrected_measurement')
            ->select(
                'station.name as stn',
                'nl.name as location_label',
                'c.country as country_name',
                DB::raw("MAX(CONCAT(m.date, ' ', m.time)) as measured_at"),
                DB::raw('COUNT(DISTINCT m.id) as reading_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN om.inavlid_temperature IS NOT NULL THEN om.id END) as correction_count')
            )
            ->groupBy('station.name', 'nl.name', 'c.country')
            ->havingRaw("MAX(CASE WHEN om.inavlid_temperature IS NOT NULL THEN 1 ELSE 0 END) = 1")
            ->orderBy('station.name')
            ->get();

        return view('stations.station-temperature', ['stations' => $stations]);
    }

    public function show(string $stn)
    {
        $station = DB::table('station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->where('station.name', $stn)
            ->select(
                'station.name as stn',
                'nl.name as location_label',
                'c.country as country_name',
                'station.latitude as lat',
                'station.longitude as lon'
            )
            ->first();

        abort_if(! $station, 404);

        // Find the most recent date available in database for this station
        $latestDate = DB::table('measurement')
            ->where('station', $stn)
            ->max('date');
        
        // Use latest date from database instead of current day
        $today = $latestDate ?? now()->format('Y-m-d');
        $lastWeek = \Carbon\Carbon::parse($today)->subDays(7)->format('Y-m-d');
        $lastMonth = \Carbon\Carbon::parse($today)->subDays(30)->format('Y-m-d');

        // Haal metingen op voor vandaag met correctie-informatie
        $readingsToday = DB::table('measurement as m')
            ->leftJoin('original_measurement as om', 'm.id', '=', 'om.corrected_measurement')
            ->where('m.station', $stn)
            ->where('m.date', $today)
            ->select(
                'm.id',
                DB::raw("CONCAT(m.date, ' ', m.time) as measured_at"),
                'm.temperature as temp',
                'm.dewpoint_temperature as dewp',
                'm.air_pressure_station as stp',
                'm.air_pressure_sea_level as slp',
                'm.visibility as visib',
                'm.wind_speed as wdsp',
                'm.wind_direction as wnddir',
                'm.percipation as prcp',
                'om.inavlid_temperature as orig_temp',
                'om.missing_field as is_missing'
            )
            ->orderBy('m.date')
            ->orderBy('m.time')
            ->get();

        // Haal metingen op voor week (gemiddeld per uur)
        $readingsWeek = DB::table('measurement')
            ->where('station', $stn)
            ->where('date', '>=', $lastWeek)
            ->select(
                'date',
                DB::raw("SUBSTRING(time, 1, 2) as hour"),
                DB::raw('AVG(temperature) as temp'),
                DB::raw('AVG(dewpoint_temperature) as dewp'),
                DB::raw('AVG(air_pressure_station) as stp'),
                DB::raw('AVG(air_pressure_sea_level) as slp'),
                DB::raw('AVG(visibility) as visib'),
                DB::raw('AVG(wind_speed) as wdsp'),
                DB::raw('AVG(percipation) as prcp')
            )
            ->groupBy('date')
            ->groupBy(DB::raw("SUBSTRING(time, 1, 2)"))
            ->orderBy('date')
            ->orderBy(DB::raw("SUBSTRING(time, 1, 2)"))
            ->get();

        // Haal metingen op voor maand (dagelijks gemiddelde)
        $readingsMonth = DB::table('measurement')
            ->where('station', $stn)
            ->where('date', '>=', $lastMonth)
            ->select(
                DB::raw("CONCAT(date, ' 12:00') as measured_at"),
                'date',
                DB::raw('AVG(temperature) as temp'),
                DB::raw('AVG(dewpoint_temperature) as dewp'),
                DB::raw('AVG(air_pressure_station) as stp'),
                DB::raw('AVG(air_pressure_sea_level) as slp'),
                DB::raw('AVG(visibility) as visib'),
                DB::raw('AVG(wind_speed) as wdsp'),
                DB::raw('AVG(percipation) as prcp')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Alle data
        $readingsAll = DB::table('measurement')
            ->where('station', $stn)
            ->select(
                DB::raw("CONCAT(date, ' 12:00') as measured_at"),
                'date',
                DB::raw('AVG(temperature) as temp'),
                DB::raw('AVG(dewpoint_temperature) as dewp'),
                DB::raw('AVG(air_pressure_station) as stp'),
                DB::raw('AVG(air_pressure_sea_level) as slp'),
                DB::raw('AVG(visibility) as visib'),
                DB::raw('AVG(wind_speed) as wdsp'),
                DB::raw('AVG(percipation) as prcp')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->limit(365)
            ->get();

        // Data kwaliteit berekenen voor vandaag
        $totalReadings = DB::table('measurement')
            ->where('station', $stn)
            ->where('date', $today)
            ->count();

        $missingFieldsCount = DB::table('original_measurement as om')
            ->join('measurement as m', 'm.id', '=', 'om.corrected_measurement')
            ->where('m.station', $stn)
            ->where('m.date', $today)
            ->whereNotNull('om.missing_field')
            ->count();

        $temperatureCorrectionsCount = DB::table('original_measurement as om')
            ->join('measurement as m', 'm.id', '=', 'om.corrected_measurement')
            ->where('m.station', $stn)
            ->where('m.date', $today)
            ->whereNotNull('om.inavlid_temperature')
            ->count();

        $missingFieldsPercentage = $totalReadings > 0 ? round(($missingFieldsCount / $totalReadings) * 100, 1) : 0;
        $correctionPercentage = $totalReadings > 0 ? round(($temperatureCorrectionsCount / $totalReadings) * 100, 1) : 0;
        $qualityPercentage = 100 - max($missingFieldsPercentage, $correctionPercentage);

        // Bepaal welke metingen voor de tabel moeten worden gebruikt op basis van periode parameter
        $period = request('period', 'day');
        $tableReadings = match($period) {
            'week' => $readingsWeek,
            'month' => $readingsMonth,
            'custom' => $readingsToday, // TODO: implementeer custom filtering
            default => $readingsToday,
        };

        // Detecteer actieve storingen uit meetdata en maak automatisch records aan
        $isOffline = !$latestDate || $latestDate < now()->subDay()->format('Y-m-d');

        $detectedTypes = array_filter([
            'offline'              => $isOffline,
            'ontbrekende_data'     => $missingFieldsCount > 0,
            'temperatuurcorrectie' => $temperatureCorrectionsCount > 0,
        ]);

        foreach (array_keys($detectedTypes) as $type) {
            $openExists = StationFault::where('station', $stn)
                ->where('type', $type)
                ->whereIn('status', ['open', 'in_behandeling'])
                ->exists();

            if (!$openExists) {
                StationFault::create(['station' => $stn, 'type' => $type, 'status' => 'open']);
            }
        }

        $faults = StationFault::where('station', $stn)
            ->withCount('notes')
            ->orderByRaw("FIELD(status, 'open', 'in_behandeling', 'opgelost')")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('stations.station-details', [
            'station' => $station,
            'readings' => $tableReadings,
            'readingsToday' => $readingsToday,
            'readingsWeek' => $readingsWeek,
            'readingsMonth' => $readingsMonth,
            'readingsAll' => $readingsAll,
            'totalReadings' => $totalReadings,
            'missingFieldsPercentage' => $missingFieldsPercentage,
            'correctionPercentage' => $correctionPercentage,
            'qualityPercentage' => $qualityPercentage,
            'faults' => $faults,
        ]);
    }

    public function download(Request $request, string $stn): StreamedResponse
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $query = DB::table('measurement')
            ->where('station', $stn)
            ->orderBy('date')
            ->orderBy('time');

        if ($from) {
            $query->where('date', '>=', $from);
        }
        if ($to) {
            $query->where('date', '<=', $to);
        }

        $readings = $query->get();
        $filename = 'station_' . $stn . '_' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($readings) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'station', 'date', 'time', 'temperature', 'dewpoint_temperature',
                'air_pressure_station', 'air_pressure_sea_level', 'visibility',
                'wind_speed', 'percipation', 'snow_depth', 'wind_direction',
            ]);

            foreach ($readings as $row) {
                fputcsv($handle, [
                    $row->station,
                    $row->date,
                    $row->time,
                    $row->temperature,
                    $row->dewpoint_temperature,
                    $row->air_pressure_station,
                    $row->air_pressure_sea_level,
                    $row->visibility,
                    $row->wind_speed,
                    $row->percipation,
                    $row->snow_depth,
                    $row->wind_direction,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
