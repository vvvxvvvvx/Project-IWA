<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $stationsQuery = DB::table('station')
            ->leftJoin('measurement as m', 'station.name', '=', 'm.station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('original_measurement as om', 'm.id', '=', 'om.corrected_measurement')
            ->select(
                'station.name as stn',
                'nl.name as location_label',
                'station.latitude as lat',
                'station.longitude as lon',
                DB::raw("MAX(CONCAT(m.date, ' ', m.time)) as measured_at"),
                DB::raw('MAX(m.temperature) as temp'),
                DB::raw('ROUND(AVG(m.temperature), 1) as avg_temp'),
                DB::raw('MAX(m.visibility) as visib'),
                DB::raw('MAX(m.wind_speed) as wdsp'),
                DB::raw('MAX(m.percipation) as prcp'),
                DB::raw('COUNT(m.id) as reading_count'),
                DB::raw('MAX(CASE WHEN om.missing_field IS NOT NULL THEN 1 ELSE 0 END) as has_missing_data'),
                DB::raw('MAX(CASE WHEN om.inavlid_temperature IS NOT NULL THEN 1 ELSE 0 END) as is_temp_peak')
            )
            ->groupBy('station.name', 'nl.name', 'station.latitude', 'station.longitude');

        if ($status === 'missing') {
            $stationsQuery->havingRaw('MAX(CASE WHEN om.missing_field IS NOT NULL THEN 1 ELSE 0 END) = 1');
        } elseif ($status === 'peak') {
            $stationsQuery
                ->havingRaw('MAX(CASE WHEN om.missing_field IS NOT NULL THEN 1 ELSE 0 END) = 0')
                ->havingRaw('MAX(CASE WHEN om.inavlid_temperature IS NOT NULL THEN 1 ELSE 0 END) = 1');
        } elseif ($status === 'ok') {
            $stationsQuery
                ->havingRaw('MAX(CASE WHEN om.missing_field IS NOT NULL THEN 1 ELSE 0 END) = 0')
                ->havingRaw('MAX(CASE WHEN om.inavlid_temperature IS NOT NULL THEN 1 ELSE 0 END) = 0');
        }

        $stations = $stationsQuery
            ->orderBy('station.name')
            ->get();

        // Stationsoverzicht gebruikt een expliciete viewnaam; pas deze ook aan als je de bestandsnaam wijzigt.
        return view('stations.station-list', [
            'stations' => $stations,
            'selectedStatus' => $status,
        ]);
    }

    public function show(string $stn)
    {
        $station = DB::table('station')
            ->leftJoin('nearestlocation', 'station.name', '=', 'nearestlocation.station_name')
            ->where('station.name', $stn)
            ->select(
                'station.name as stn',
                'nearestlocation.name as location_label',
                'station.latitude as lat',
                'station.longitude as lon'
            )
            ->first();

        abort_if(! $station, 404);

        $readings = DB::table('measurement')
            ->where('station', $stn)
            ->select(
                DB::raw("CONCAT(date, ' ', time) as measured_at"),
                'temperature as temp',
                'dewpoint_temperature as dewp',
                'air_pressure_station as stp',
                'air_pressure_sea_level as slp',
                'visibility as visib',
                'wind_speed as wdsp',
                'percipation as prcp'
            )
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->limit(50)
            ->get()
            ->toArray();

        return view('stations.station-details', compact('station', 'readings'));
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
