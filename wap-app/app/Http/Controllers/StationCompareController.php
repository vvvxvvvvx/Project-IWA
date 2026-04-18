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
        $selectedStns  = array_filter((array) $request->input('stations', []));
        $selectedMetric = $request->input('metric', 'temperature');

        if (! array_key_exists($selectedMetric, $this->metrics)) {
            $selectedMetric = 'temperature';
        }

        // Alle stations voor de multi-select, gesorteerd op locatie
        $allStations = DB::table('station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->select('station.name as stn', 'nl.name as location_label')
            ->orderBy('nl.name')
            ->get();

        $chartDatasets = [];
        $tableRows     = [];
        $colors = [
            '#FF6B6B','#4ECDC4','#45B7D1','#96CEB4','#FFEAA7',
            '#DDA0DD','#98D8C8','#F7DC6F','#BB8FCE','#85C1E9',
        ];

        if (count($selectedStns) > 0) {
            $metricCol = $this->metrics[$selectedMetric]['column'];

            foreach ($selectedStns as $i => $stn) {
                // Vind locatielabel
                $stationInfo = $allStations->firstWhere('stn', $stn);
                $stationLabel = $stationInfo?->location_label ?? $stn;

                // Haal laatste beschikbare datum op
                $latestDate = DB::table('measurement')
                    ->where('station', $stn)
                    ->max('date');

                if (! $latestDate) {
                    continue;
                }

                // Haal metingen op voor die dag
                $readings = DB::table('measurement')
                    ->where('station', $stn)
                    ->where('date', $latestDate)
                    ->select(
                        DB::raw("CONCAT(date, ' ', time) as measured_at"),
                        DB::raw("$metricCol as value")
                    )
                    ->orderBy('date')
                    ->orderBy('time')
                    ->get();

                $labels = $readings->map(fn ($r) => substr((string) $r->measured_at, 11, 5))->toArray();
                $values = $readings->map(fn ($r) => $r->value !== null ? (float) $r->value : null)->toArray();

                $color = $colors[$i % count($colors)];

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

                // Laatste meting voor de vergelijkingstabel
                $latest = DB::table('measurement')
                    ->where('station', $stn)
                    ->where('date', $latestDate)
                    ->select(
                        DB::raw("CONCAT(date, ' ', time) as measured_at"),
                        'temperature', 'dewpoint_temperature', 'air_pressure_station',
                        'air_pressure_sea_level', 'visibility', 'wind_speed',
                        'wind_direction', 'percipation'
                    )
                    ->orderByDesc('time')
                    ->first();

                $tableRows[] = [
                    'stn'      => $stn,
                    'label'    => $stationLabel,
                    'color'    => $color,
                    'latest'   => $latest,
                ];
            }
        }

        // Gebruik de labels van de eerste dataset als gemeenschappelijke tijdas
        $sharedLabels = $chartDatasets[0]['labels'] ?? [];

        return view('stations.station-compare', [
            'allStations'    => $allStations,
            'selectedStns'   => $selectedStns,
            'selectedMetric' => $selectedMetric,
            'metrics'        => $this->metrics,
            'chartDatasets'  => $chartDatasets,
            'sharedLabels'   => $sharedLabels,
            'tableRows'      => $tableRows,
        ]);
    }
}
