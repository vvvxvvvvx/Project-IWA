<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $data = $this->buildDashboardData();
        // Dashboardview staat nu in resources/views/dashboard/operations-dashboard.blade.php.
        return view('dashboard.operations-dashboard', $data);
    }

    public function apiOverview()
    {
        return response()->json($this->buildDashboardData());
    }

    private function buildDashboardData(): array
    {
        $user = Auth::user();

        $role = $user->userrole ? $user->userrole->role : 'employee';
        $displayName = trim(($user->first_name ?? '') . ' ' . ($user->name ?? '')) ?: 'Gebruiker';

        // Overview metrics
        $overview = [
            'station_count'  => DB::table('station')->count(),
            'reading_count'  => DB::table('measurement')->count(),
            'average_temp'   => round((float) DB::table('measurement')->avg('temperature'), 1),
            'peak_count'     => DB::table('original_measurement')->whereNotNull('inavlid_temperature')->count(),
            'missing_count'  => DB::table('original_measurement')->whereNotNull('missing_field')->count(),
        ];

        // Top 5 stations by reading count
        $top_stations = DB::table('measurement')
            ->join('station', 'measurement.station', '=', 'station.name')
            ->select('station.name', DB::raw('COUNT(*) as reading_count'))
            ->groupBy('station.name')
            ->orderByDesc('reading_count')
            ->limit(5)
            ->get()
            ->toArray();

        // Chart points: original vs corrected temperature
        $chart_points = DB::table('original_measurement')
            ->join('measurement', 'original_measurement.corrected_measurement', '=', 'measurement.id')
            ->select(
                DB::raw("CONCAT(measurement.date, 'T', measurement.time) as bucket"),
                'original_measurement.inavlid_temperature as original_temp',
                'measurement.temperature as corrected_temp'
            )
            ->whereNotNull('original_measurement.inavlid_temperature')
            ->orderByDesc('measurement.date')
            ->orderByDesc('measurement.time')
            ->limit(20)
            ->get()
            ->toArray();

        // Latest 10 readings
        $latest_readings = DB::table('measurement')
            ->join('station', 'measurement.station', '=', 'station.name')
            ->leftJoin('nearestlocation', 'station.name', '=', 'nearestlocation.station_name')
            ->leftJoin('original_measurement', 'measurement.id', '=', 'original_measurement.corrected_measurement')
            ->select(
                'measurement.station as stn',
                'station.name',
                'nearestlocation.name as location_label',
                DB::raw("CONCAT(measurement.date, ' ', measurement.time) as measured_at"),
                'measurement.temperature as temp',
                'measurement.dewpoint_temperature as dewp',
                'measurement.wind_speed as wdsp',
                'measurement.visibility as visib',
                'measurement.percipation as prcp',
                DB::raw('CASE WHEN original_measurement.missing_field IS NOT NULL THEN 1 ELSE 0 END as has_missing_data'),
                DB::raw('CASE WHEN original_measurement.inavlid_temperature IS NOT NULL THEN 1 ELSE 0 END as is_temp_peak')
            )
            ->orderByDesc('measurement.date')
            ->orderByDesc('measurement.time')
            ->limit(10)
            ->get()
            ->toArray();

        // All stations with aggregated stats
        $stations = DB::table('station')
            ->leftJoin('measurement', 'station.name', '=', 'measurement.station')
            ->leftJoin('nearestlocation', 'station.name', '=', 'nearestlocation.station_name')
            ->leftJoin('original_measurement', 'measurement.id', '=', 'original_measurement.corrected_measurement')
            ->select(
                'station.name as stn',
                'station.name as name',
                'nearestlocation.name as location_label',
                'station.latitude as lat',
                'station.longitude as lon',
                DB::raw("MAX(CONCAT(measurement.date, ' ', measurement.time)) as measured_at"),
                DB::raw('MAX(measurement.temperature) as temp'),
                DB::raw('ROUND(AVG(measurement.temperature), 1) as avg_temp'),
                DB::raw('MAX(measurement.visibility) as visib'),
                DB::raw('MAX(measurement.wind_speed) as wdsp'),
                DB::raw('MAX(measurement.percipation) as prcp'),
                DB::raw('COUNT(measurement.id) as reading_count'),
                DB::raw('MAX(CASE WHEN original_measurement.missing_field IS NOT NULL THEN 1 ELSE 0 END) as has_missing_data'),
                DB::raw('MAX(CASE WHEN original_measurement.inavlid_temperature IS NOT NULL THEN 1 ELSE 0 END) as is_temp_peak')
            )
            ->groupBy('station.name', 'nearestlocation.name', 'station.latitude', 'station.longitude')
            ->get()
            ->toArray();

        // Employee/admin only data
        $flagged_readings    = [];
        $recent_corrections  = [];

        if ($role !== 'customer') {
            $flagged_readings = DB::table('measurement')
                ->join('station', 'measurement.station', '=', 'station.name')
                ->join('original_measurement', 'measurement.id', '=', 'original_measurement.corrected_measurement')
                ->select(
                    'measurement.station as stn',
                    'station.name',
                    DB::raw("CONCAT(measurement.date, ' ', measurement.time) as measured_at"),
                    'measurement.temperature as temp',
                    DB::raw('CASE WHEN original_measurement.missing_field IS NOT NULL THEN 1 ELSE 0 END as has_missing_data')
                )
                ->orderByDesc('measurement.date')
                ->orderByDesc('measurement.time')
                ->limit(10)
                ->get()
                ->toArray();

            $recent_corrections = DB::table('original_measurement')
                ->join('measurement', 'original_measurement.corrected_measurement', '=', 'measurement.id')
                ->select(
                    'measurement.station as stn',
                    DB::raw("COALESCE(original_measurement.missing_field, 'temperature') as field"),
                    DB::raw("'Automatische correctie' as reason"),
                    'original_measurement.inavlid_temperature as original_value',
                    'measurement.temperature as corrected_value',
                    DB::raw("CONCAT(measurement.date, ' ', measurement.time) as created_at")
                )
                ->whereNotNull('original_measurement.inavlid_temperature')
                ->orderByDesc('measurement.date')
                ->orderByDesc('measurement.time')
                ->limit(10)
                ->get()
                ->toArray();
        }

        return compact(
            'role', 'displayName', 'overview', 'top_stations', 'chart_points',
            'latest_readings', 'stations', 'flagged_readings', 'recent_corrections'
        );
    }
}
