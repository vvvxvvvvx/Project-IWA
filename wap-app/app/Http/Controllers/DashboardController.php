<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Laad de landingspagina met alle KPI-data.
     */
    public function index(Request $request)
    {
        return view('dashboard.nieuw_dashboard', $this->buildDashboardData());
    }

    public function apiOverview()
    {
        return response()->json($this->buildDashboardData());
    }

    private function getUserTasks(?\App\Models\User $user): array
    {
        if (!$user || !$user->userrole) {
            return [];
        }
        return $user->userrole->tasks()->pluck('name')->toArray();
    }

    private function buildDashboardData(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $today = today()->toDateString();
        $userTasks = $this->getUserTasks($user);

        $dashboardData = Cache::remember('dashboard.overview.v2.' . $today, now()->addMinutes(5), function () use ($today) {
            $last14Days = now()->subDays(13)->toDateString();
            $last30Days = now()->subDays(30)->toDateString();

            /* ------------------------------------------------------------------ */
            /* Algemene metrics                                                   */
            /* ------------------------------------------------------------------ */

            $stationCount = DB::table('station')->count();

            $readingsToday = DB::table('measurement')
                ->where('date', $today)
                ->count();

            $totalReadings = DB::table('measurement')->count();

            $activeSubscriptions = DB::table('subscriptions')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', today());
                })
                ->count();

            $missingCount = DB::table('original_measurement')
                ->whereNotNull('missing_field')
                ->count();

            $peakCount = DB::table('original_measurement')
                ->whereNotNull('inavlid_temperature')
                ->count();

            $qualityPct = $totalReadings > 0
                ? max(0, 100 - (int) round(($missingCount / $totalReadings) * 100))
                : 100;

            $overview = [
                'station_count' => $stationCount,
                'readings_today' => $readingsToday,
                'reading_count' => $totalReadings,
                'active_subscriptions' => $activeSubscriptions,
                'missing_count' => $missingCount,
                'peak_count' => $peakCount,
                'quality_pct' => $qualityPct,
            ];

            /* ------------------------------------------------------------------ */
            /* Top stations + temperatuurtrend                                   */
            /* ------------------------------------------------------------------ */

            $top_stations = DB::table('measurement')
                ->select('station as name', DB::raw('COUNT(*) as reading_count'))
                ->where('date', '>=', $last30Days)
                ->groupBy('station')
                ->orderByDesc('reading_count')
                ->limit(5)
                ->get();

            $chart_points = DB::table('measurement as m')
                ->leftJoin('original_measurement as o', 'o.corrected_measurement', '=', 'm.id')
                ->select(
                    'm.date',
                    DB::raw('ROUND(AVG(m.temperature), 1) as avg_temp'),
                    DB::raw('ROUND(AVG(o.inavlid_temperature), 1) as avg_original')
                )
                ->where('m.date', '>=', $last14Days)
                ->groupBy('m.date')
                ->orderBy('m.date')
                ->get();

            /* ------------------------------------------------------------------ */
            /* Laatste metingen + stationsoverzicht                              */
            /* ------------------------------------------------------------------ */

            $latestMeasurementIds = DB::table('measurement')
                ->select('station', DB::raw('MAX(id) as latest_id'))
                ->whereNotNull('station')
                ->groupBy('station');

            $latest_readings = DB::table('measurement as m')
                ->joinSub(clone $latestMeasurementIds, 'latest', function ($join) {
                    $join->on('m.id', '=', 'latest.latest_id');
                })
                ->leftJoin('nearestlocation as nl', 'nl.station_name', '=', 'm.station')
                ->leftJoin('original_measurement as o', 'o.corrected_measurement', '=', 'm.id')
                ->select(
                    'm.station as name',
                    DB::raw('CONCAT(m.date, " ", m.time) as measured_at'),
                    DB::raw('COALESCE(nl.name, m.station) as location_label'),
                    'm.temperature as temp',
                    'm.dewpoint_temperature as dewp',
                    'm.wind_speed as wdsp',
                    'm.visibility as visib',
                    'm.percipation as prcp',
                    DB::raw('IF(o.missing_field IS NOT NULL, 1, 0) as has_missing_data'),
                    DB::raw('IF(o.inavlid_temperature IS NOT NULL, 1, 0) as is_temp_peak')
                )
                ->orderByDesc('m.id')
                ->limit(20)
                ->get();

            $stationStats = DB::table('measurement')
                ->select(
                    'station',
                    DB::raw('ROUND(AVG(temperature), 1) as avg_temp'),
                    DB::raw('COUNT(*) as reading_count')
                )
                ->where('date', '>=', $last30Days)
                ->groupBy('station');

            $stations = DB::table('station as s')
                ->leftJoin('nearestlocation as nl', 'nl.station_name', '=', 's.name')
                ->leftJoinSub($stationStats, 'stats', function ($join) {
                    $join->on('stats.station', '=', 's.name');
                })
                ->leftJoinSub(clone $latestMeasurementIds, 'latest', function ($join) {
                    $join->on('latest.station', '=', 's.name');
                })
                ->leftJoin('measurement as m', 'm.id', '=', 'latest.latest_id')
                ->leftJoin('original_measurement as o', 'o.corrected_measurement', '=', 'm.id')
                ->select(
                    's.name as stn',
                    DB::raw('COALESCE(nl.name, s.name) as location_label'),
                    's.latitude as lat',
                    's.longitude as lon',
                    DB::raw('CONCAT(m.date, " ", m.time) as measured_at'),
                    'm.temperature as temp',
                    'stats.avg_temp',
                    DB::raw('COALESCE(stats.reading_count, 0) as reading_count'),
                    DB::raw('IF(o.missing_field IS NOT NULL, 1, 0) as has_missing_data'),
                    DB::raw('IF(o.inavlid_temperature IS NOT NULL, 1, 0) as is_temp_peak')
                )
                ->orderBy('s.name')
                ->get();

            /* ------------------------------------------------------------------ */
            /* Signaleringen, correcties en API-activiteit                       */
            /* ------------------------------------------------------------------ */

            $flagged_readings = DB::table('original_measurement as o')
                ->join('measurement as m', 'm.id', '=', 'o.corrected_measurement')
                ->where(function ($query) {
                    $query->whereNotNull('o.missing_field')
                        ->orWhereNotNull('o.inavlid_temperature');
                })
                ->select(
                    'm.station as name',
                    DB::raw('CONCAT(m.date, " ", m.time) as measured_at'),
                    'm.temperature as temp',
                    DB::raw('IF(o.missing_field IS NOT NULL, 1, 0) as has_missing_data'),
                    DB::raw('IF(o.inavlid_temperature IS NOT NULL, 1, 0) as is_temp_peak')
                )
                ->orderByDesc('m.id')
                ->limit(15)
                ->get();

            $recent_corrections = DB::table('original_measurement as o')
                ->join('measurement as m', 'm.id', '=', 'o.corrected_measurement')
                ->where(function ($query) {
                    $query->whereNotNull('o.inavlid_temperature')
                        ->orWhereNotNull('o.missing_field');
                })
                ->select(
                    'm.station as stn',
                    DB::raw("COALESCE(o.missing_field, 'temperature') as field"),
                    'o.inavlid_temperature as original_value',
                    'm.temperature as corrected_value',
                    DB::raw('CONCAT(m.date, " ", m.time) as created_at')
                )
                ->orderByDesc('m.id')
                ->limit(10)
                ->get();

            $api_activity = DB::table('endpoint_activity')
                ->whereDate('activity_date', $today)
                ->select(
                    'endpoint_used',
                    DB::raw('COUNT(*) as calls'),
                    DB::raw('SUM(data_transferred) as total_data'),
                    DB::raw('SUM(IF(authorized = 0, 1, 0)) as unauthorized')
                )
                ->groupBy('endpoint_used')
                ->orderByDesc('calls')
                ->limit(10)
                ->get();

            /* ------------------------------------------------------------------ */
            /* Missing fields statistics                                         */
            /* ------------------------------------------------------------------ */

            $missing_fields_stats = DB::table('original_measurement as o')
                ->select(
                    'o.missing_field as field_name',
                    DB::raw('COUNT(*) as missing_count'),
                    DB::raw('(SELECT COUNT(*) FROM original_measurement) as total_count')
                )
                ->whereNotNull('o.missing_field')
                ->groupBy('o.missing_field')
                ->orderByDesc('missing_count')
                ->limit(10)
                ->get();

            /* ------------------------------------------------------------------ */
            /* Stations with most missing fields                                 */
            /* ------------------------------------------------------------------ */

            $stations_most_missing = DB::table('original_measurement as o')
                ->join('measurement as m', 'm.id', '=', 'o.corrected_measurement')
                ->leftJoin('nearestlocation as nl', 'nl.station_name', '=', 'm.station')
                ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
                ->select(
                    'm.station as stn',
                    DB::raw('COALESCE(nl.name, "Onbekend") as location_label'),
                    DB::raw('COALESCE(c.country, "—") as country_name'),
                    DB::raw('COUNT(*) as missing_count'),
                    DB::raw('(SELECT COUNT(*) FROM measurement WHERE station = m.station) as reading_count')
                )
                ->whereNotNull('o.missing_field')
                ->groupBy('m.station', 'nl.name', 'c.country')
                ->orderByDesc('missing_count')
                ->limit(10)
                ->get();

            /* ------------------------------------------------------------------ */
            /* Stations with most temperature corrections (REMOVED PER USER REQUEST) */
            /* ------------------------------------------------------------------ */

            $stations_most_corrected = collect(); // Empty, removed from view

            /* ------------------------------------------------------------------ */
            /* Stations by country with geo coordinates                          */
            /* ------------------------------------------------------------------ */

            // Use nearestlocation table (has country_code) + country table (has country names)
            $stations_by_country = DB::table('nearestlocation as nl')
                ->join('country as c', 'c.country_code', '=', 'nl.country_code')
                ->select(
                    'c.country as country_name',
                    'nl.country_code',
                    DB::raw('AVG(nl.latitude) as lat'),
                    DB::raw('AVG(nl.longitude) as lng'),
                    DB::raw('COUNT(DISTINCT nl.station_name) as station_count')
                )
                ->groupBy('nl.country_code', 'c.country')
                ->orderByDesc('station_count')
                ->get()
                ->map(function ($row) {
                    return [
                        'country_name' => $row->country_name,
                        'country_code' => $row->country_code,
                        'lat' => floatval($row->lat),
                        'lng' => floatval($row->lng),
                        'station_count' => intval($row->station_count)
                    ];
                })
                ->toArray();

            return [
                'overview' => $overview,
                'top_stations' => $top_stations,
                'chart_points' => $chart_points,
                'latest_readings' => $latest_readings,
                'stations' => $stations,
                'flagged_readings' => $flagged_readings,
                'recent_corrections' => $recent_corrections,
                'api_activity' => $api_activity,
                'missing_fields_stats' => $missing_fields_stats,
                'stations_most_missing' => $stations_most_missing,
                'stations_by_country' => $stations_by_country,
            ];
        });

        // Altijd live ophalen (niet gecached), zodat het dashboard altijd actueel is
        $activeStoringen = in_array('view_stations', $userTasks)
            ? DB::table('station_fault')->whereIn('status', ['open', 'in_behandeling'])->count()
            : null;

        // Filter cached data to only include what this user is allowed to see
        $canViewStations     = in_array('view_stations', $userTasks);
        $canViewSubscriptions = in_array('view_subscriptions', $userTasks);

        $filtered = $dashboardData;

        if (!$canViewStations) {
            $filtered['top_stations']         = collect();
            $filtered['chart_points']         = collect();
            $filtered['latest_readings']      = collect();
            $filtered['stations']             = collect();
            $filtered['flagged_readings']     = collect();
            $filtered['recent_corrections']   = collect();
            $filtered['missing_fields_stats'] = collect();
            $filtered['stations_most_missing']= collect();
            $filtered['stations_by_country']  = [];
            $filtered['overview']['station_count']  = null;
            $filtered['overview']['readings_today'] = null;
            $filtered['overview']['quality_pct']    = null;
            $filtered['overview']['missing_count']  = null;
            $filtered['overview']['peak_count']     = null;
        }

        if (!$canViewSubscriptions) {
            $filtered['overview']['active_subscriptions'] = null;
        }

        $filtered['overview']['active_storingen'] = $activeStoringen;

        return array_merge($filtered, [
            'displayName' => $user
                ? (trim(($user->first_name ?? '') . ' ' . ($user->name ?? '')) ?: ($user->name ?? 'IWA-medewerker'))
                : 'IWA-medewerker',
            'role'      => $user ? (optional($user->userrole)->role ?? 'medewerker') : 'medewerker',
            'userTasks' => $userTasks,
        ]);
    }
}
