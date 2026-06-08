<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeatherApiController extends Controller
{
    // Begrenzingsvak gelijk aan ASIA_BOUNDS in de frontend (weatherData.ts)
    // Hiermee worden alleen weerstations in het zichtbare kaartgebied opgehaald.
    private const LAT_MIN = -12.0;
    private const LAT_MAX =  58.0;
    private const LNG_MIN =  55.0;
    private const LNG_MAX = 155.0;

    /**
     * GET /api/weather/asia/stations
     * Geeft alle Aziatische stations terug met hun meest recente meting.
     */
    public function stations(): JsonResponse
    {
        $stations = DB::table('station as s')
            ->leftJoin('geolocation as g', 's.name', '=', 'g.station_name')
            ->leftJoin('nearestlocation as nl', 's.name', '=', 'nl.station_name')
            ->leftJoin('country as gc', 'g.country_code', '=', 'gc.country_code')
            ->leftJoin('country as nc', 'nl.country_code', '=', 'nc.country_code')
            ->whereBetween('s.latitude',  [self::LAT_MIN, self::LAT_MAX])
            ->whereBetween('s.longitude', [self::LNG_MIN, self::LNG_MAX])
            ->select(
                's.name',
                's.latitude',
                's.longitude',
                's.elevation',
                DB::raw('COALESCE(g.country_code, nl.country_code) as country_code'),
                DB::raw('COALESCE(gc.country, nc.country)           as country'),
                DB::raw('COALESCE(g.city, nl.name)                  as city')
            )
            ->distinct()
            ->orderBy('s.name')
            ->get();

        $names = $stations->pluck('name');

        // Meest recente meting per station via MAX(id) — id's zijn oplopend bij invoer
        $latest = DB::table('measurement as m')
            ->joinSub(
                DB::table('measurement')
                    ->select('station', DB::raw('MAX(id) as max_id'))
                    ->whereIn('station', $names)
                    ->groupBy('station'),
                'sub',
                fn ($j) => $j->on('m.id', '=', 'sub.max_id')
            )
            ->select('m.station', 'm.date', 'm.time', 'm.temperature', 'm.dewpoint_temperature', 'm.percipation')
            ->get()
            ->keyBy('station');

        return response()->json([
            'stations' => $stations->map(function ($s) use ($latest) {
                $m = $latest->get($s->name);
                return [
                    'name'         => $s->name,
                    'latitude'     => (float) $s->latitude,
                    'longitude'    => (float) $s->longitude,
                    'elevation'    => $s->elevation !== null ? (float) $s->elevation : null,
                    'country_code' => $s->country_code,
                    'country'      => $s->country,
                    'city'         => $s->city,
                    'latest_measurement' => $m ? [
                        'date'          => $m->date,
                        'time'          => $m->time,
                        'temperature'   => $m->temperature !== null    ? (float) $m->temperature   : null,
                        'humidity'      => $this->calcHumidity($m->temperature, $m->dewpoint_temperature),
                        'precipitation' => $m->percipation !== null    ? (float) $m->percipation    : null,
                    ] : null,
                ];
            })->values(),
            'meta' => [
                'total'        => $stations->count(),
                'region'       => 'Asia',
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/weather/asia/measurements
     * Query-parameters:
     *   limit     (int,  default 500, max 2000)
     *   date_from (YYYY-MM-DD)
     *   date_to   (YYYY-MM-DD)
     */
    public function measurements(Request $request): JsonResponse
    {
        $limit    = min((int) $request->query('limit', 500), 2000);
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = DB::table('measurement as m')
            ->join('station as s', 'm.station', '=', 's.name')
            ->whereBetween('s.latitude',  [self::LAT_MIN, self::LAT_MAX])
            ->whereBetween('s.longitude', [self::LNG_MIN, self::LNG_MAX])
            ->select('m.id', 'm.station', 'm.date', 'm.time', 'm.temperature', 'm.dewpoint_temperature', 'm.percipation');

        if ($dateFrom) {
            $query->whereDate('m.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('m.date', '<=', $dateTo);
        }

        $rows = $query
            ->orderByDesc('m.date')
            ->orderByDesc('m.time')
            ->limit($limit)
            ->get();

        return response()->json([
            'measurements' => $rows->map(fn ($m) => [
                'id'            => $m->id,
                'station'       => $m->station,
                'date'          => $m->date,
                'time'          => $m->time,
                'temperature'   => $m->temperature    !== null ? (float) $m->temperature    : null,
                'humidity'      => $this->calcHumidity($m->temperature, $m->dewpoint_temperature),
                'precipitation' => $m->percipation    !== null ? (float) $m->percipation    : null,
            ])->values(),
            'meta' => [
                'total'        => $rows->count(),
                'limit'        => $limit,
                'date_from'    => $dateFrom,
                'date_to'      => $dateTo,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Berekent relatieve luchtvochtigheid (%) uit temperatuur en dauwpunt
     * via de Magnus-formule.
     */
    private function calcHumidity(mixed $temp, mixed $dew): ?float
    {
        if ($temp === null || $dew === null) {
            return null;
        }
        $t  = (float) $temp;
        $td = (float) $dew;
        $a  = 17.625;
        $b  = 243.04;
        $rh = 100.0 * exp(($a * $td) / ($b + $td)) / exp(($a * $t) / ($b + $t));
        return round(min(100.0, max(0.0, $rh)), 1);
    }
}
