<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StationCompareController extends Controller
{
    private array $metrics = [
        'temperature'        => ['column' => 'temperature',          'label' => 'Temperatuur',       'unit' => '°C'],
        'dewpoint'           => ['column' => 'dewpoint_temperature',  'label' => 'Dauwpunt',          'unit' => '°C'],
        'pressure_station'   => ['column' => 'air_pressure_station',  'label' => 'Luchtdruk (st.)',   'unit' => 'hPa'],
        'pressure_sea'       => ['column' => 'air_pressure_sea_level','label' => 'Luchtdruk (z.n.)', 'unit' => 'hPa'],
        'visibility'         => ['column' => 'visibility',            'label' => 'Zicht',             'unit' => 'm'],
        'wind_speed'         => ['column' => 'wind_speed',            'label' => 'Windsnelheid',      'unit' => 'm/s'],
        'precipitation'      => ['column' => 'percipation',           'label' => 'Neerslag',          'unit' => 'mm'],
    ];

    public function index(Request $request)
    {
        $selectedStns    = array_filter((array) $request->input('stations', []));
        $selectedMetric  = $request->input('metric', 'temperature');
        $selectedPeriod  = $request->input('period', 'day');
        $selectedDate    = $request->input('date', now()->format('Y-m-d'));
        $selectedCountry = $request->input('country_filter', '');

        if (! array_key_exists($selectedMetric, $this->metrics)) {
            $selectedMetric = 'temperature';
        }

        // Alle stations voor de multi-select, gesorteerd op locatie
        $allStations = DB::table('station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->select('station.name as stn', 'nl.name as location_label', 'nl.country_code', 'c.country as country_name')
            ->orderBy('nl.name')
            ->get();

        // Unieke landen voor het landfilter
        $countries = $allStations
            ->whereNotNull('country_name')
            ->unique('country_code')
            ->sortBy('country_name')
            ->values();

        $chartDatasets = [];
        $tableRows     = [];
        $colors = [
            '#FF6B6B','#4ECDC4','#45B7D1','#96CEB4','#FFEAA7',
            '#DDA0DD','#98D8C8','#F7DC6F','#BB8FCE','#85C1E9',
        ];

        if (count($selectedStns) > 0) {
            $metricCol = $this->metrics[$selectedMetric]['column'];

            foreach ($selectedStns as $i => $stn) {
                $stationInfo  = $allStations->firstWhere('stn', $stn);
                $stationLabel = $stationInfo?->location_label ?? $stn;
                $color        = $colors[$i % count($colors)];

                if ($selectedPeriod === 'day') {
                    $targetDate = now()->format('Y-m-d');
                } elseif ($selectedPeriod === 'specific') {
                    $targetDate = $selectedDate;
                } else {
                    $targetDate = null;
                }

                if ($selectedPeriod === 'day' || $selectedPeriod === 'specific') {
                    // Uurdata voor één dag
                    if (! $targetDate) continue;

                    $readings = DB::table('measurement')
                        ->where('station', $stn)
                        ->where('date', $targetDate)
                        ->select(
                            DB::raw("CONCAT(date, ' ', time) as measured_at"),
                            DB::raw("$metricCol as value")
                        )
                        ->orderBy('time')
                        ->get();

                    $labels = $readings->map(fn ($r) => substr((string) $r->measured_at, 11, 5))->toArray();
                    $values = $readings->map(fn ($r) => $r->value !== null ? (float) $r->value : null)->toArray();

                    // Laatste meting voor de tabel
                    $latest = DB::table('measurement')
                        ->where('station', $stn)
                        ->where('date', $targetDate)
                        ->select(
                            DB::raw("CONCAT(date, ' ', time) as measured_at"),
                            'temperature', 'dewpoint_temperature', 'air_pressure_station',
                            'air_pressure_sea_level', 'visibility', 'wind_speed',
                            'wind_direction', 'percipation'
                        )
                        ->orderByDesc('time')
                        ->first();

                } elseif ($selectedPeriod === 'week') {
                    // Daggemiddelden afgelopen 7 dagen
                    $from = now()->subDays(6)->format('Y-m-d');
                    $to   = now()->format('Y-m-d');

                    $readings = DB::table('measurement')
                        ->where('station', $stn)
                        ->whereBetween('date', [$from, $to])
                        ->select(
                            'date',
                            DB::raw("AVG(NULLIF($metricCol, 9999.9)) as value")
                        )
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();

                    $labels = $readings->pluck('date')->toArray();
                    $values = $readings->map(fn ($r) => $r->value !== null ? round((float) $r->value, 1) : null)->toArray();

                    $latest = DB::table('measurement')
                        ->where('station', $stn)
                        ->whereBetween('date', [$from, $to])
                        ->select(
                            DB::raw("CONCAT(date, ' ', time) as measured_at"),
                            'temperature', 'dewpoint_temperature', 'air_pressure_station',
                            'air_pressure_sea_level', 'visibility', 'wind_speed',
                            'wind_direction', 'percipation'
                        )
                        ->orderByDesc('date')
                        ->orderByDesc('time')
                        ->first();

                } elseif ($selectedPeriod === 'month') {
                    // Daggemiddelden afgelopen 30 dagen
                    $from = now()->subDays(29)->format('Y-m-d');
                    $to   = now()->format('Y-m-d');

                    $readings = DB::table('measurement')
                        ->where('station', $stn)
                        ->whereBetween('date', [$from, $to])
                        ->select(
                            'date',
                            DB::raw("AVG(NULLIF($metricCol, 9999.9)) as value")
                        )
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();

                    $labels = $readings->pluck('date')->toArray();
                    $values = $readings->map(fn ($r) => $r->value !== null ? round((float) $r->value, 1) : null)->toArray();

                    $latest = DB::table('measurement')
                        ->where('station', $stn)
                        ->whereBetween('date', [$from, $to])
                        ->select(
                            DB::raw("CONCAT(date, ' ', time) as measured_at"),
                            'temperature', 'dewpoint_temperature', 'air_pressure_station',
                            'air_pressure_sea_level', 'visibility', 'wind_speed',
                            'wind_direction', 'percipation'
                        )
                        ->orderByDesc('date')
                        ->orderByDesc('time')
                        ->first();

                } else {
                    // Onbekende periode, overslaan
                    continue;
                }

                if (empty($labels)) continue;

                $chartDatasets[] = [
                    'label'           => $stationLabel . ' (' . $stn . ')',
                    'data'            => $values,
                    'labels'          => $labels,
                    'borderColor'     => $color,
                    'backgroundColor' => $color . '22',
                    'borderWidth'     => 2,
                    'tension'         => 0.4,
                    'fill'            => false,
                    'pointRadius'     => 3,
                ];

                $tableRows[] = [
                    'stn'    => $stn,
                    'label'  => $stationLabel,
                    'color'  => $color,
                    'latest' => $latest ?? null,
                ];
            }
        }

        // Gebruik de labels van de eerste dataset als gemeenschappelijke tijdas
        $sharedLabels = $chartDatasets[0]['labels'] ?? [];

        return view('stations.station-compare', [
            'allStations'     => $allStations,
            'countries'       => $countries,
            'selectedStns'    => $selectedStns,
            'selectedMetric'  => $selectedMetric,
            'selectedPeriod'  => $selectedPeriod,
            'selectedDate'    => $selectedDate,
            'selectedCountry' => $selectedCountry,
            'metrics'         => $this->metrics,
            'chartDatasets'   => $chartDatasets,
            'sharedLabels'    => $sharedLabels,
            'tableRows'       => $tableRows,
        ]);
    }
}
