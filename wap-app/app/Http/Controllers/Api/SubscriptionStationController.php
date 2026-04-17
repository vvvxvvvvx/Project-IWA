<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionStationController extends Controller
{
    private const DEFAULT_MEASUREMENT_FIELDS = [
        'temperature',
        'dewpoint_temperature',
        'air_pressure_station',
        'air_pressure_sea_level',
        'visibility',
        'wind_speed',
        'percipation',
        'snow_depth',
        'conditions',
        'cloud_cover',
        'wind_direction',
    ];

    public function index(Request $request, string $identifier): JsonResponse
    {
        $subscription = $this->findSubscription($identifier);
        if (! $subscription) {
            return response()->json(['error' => 'Abonnement niet gevonden.'], 404);
        }

        $contractQuery = $this->resolveContractQuery($subscription->id, $request);
        $stations = $this->filteredStationsQuery($subscription->id, $contractQuery)
            ->select(
                'station.name',
                'station.latitude',
                'station.longitude',
                'station.elevation',
                DB::raw('COALESCE(geolocation.country_code, nearestlocation.country_code) as country_code'),
                'nearestlocation.name as nearest_location',
                'nearestlocation.administrative_region1'
            )
            ->orderBy('station.name')
            ->distinct()
            ->get();

        return response()->json([
            'data' => $stations,
            'meta' => $this->queryMeta($contractQuery, $stations->count()),
        ]);
    }

    public function show(Request $request, string $identifier, string $name): JsonResponse
    {
        $subscription = $this->findSubscription($identifier);
        if (! $subscription) {
            return response()->json(['error' => 'Abonnement niet gevonden.'], 404);
        }

        $contractQuery = $this->resolveContractQuery($subscription->id, $request);
        $stationDetails = $this->filteredStationsQuery($subscription->id, $contractQuery)
            ->where('station.name', $name)
            ->select(
                'station.name',
                'station.latitude',
                'station.longitude',
                'station.elevation',
                DB::raw('COALESCE(geolocation.country_code, nearestlocation.country_code) as country_code'),
                DB::raw('COALESCE(geolocation.country, geolocation_country.country, nearestlocation_country.country) as country'),
                'geolocation.province',
                'geolocation.city',
                'nearestlocation.name as nearest_location',
                'nearestlocation.administrative_region1',
                'nearestlocation.administrative_region2'
            )
            ->distinct()
            ->first();

        if (! $stationDetails) {
            return response()->json(['error' => 'Station behoort niet tot dit abonnement of valt buiten de actieve querycriteria.'], 403);
        }

        return response()->json([
            'data' => $stationDetails,
            'meta' => $this->queryMeta($contractQuery, 1),
        ]);
    }

    public function measurements(Request $request, string $identifier): JsonResponse
    {
        $subscription = $this->findSubscription($identifier);
        if (! $subscription) {
            return response()->json(['error' => 'Abonnement niet gevonden.'], 404);
        }

        $contractQuery = $this->resolveContractQuery($subscription->id, $request);
        $stationNames = $this->filteredStationsQuery($subscription->id, $contractQuery)
            ->orderBy('station.name')
            ->distinct()
            ->pluck('station.name');

        if ($stationNames->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => $this->queryMeta($contractQuery, 0),
            ]);
        }

        $measurementFields = $this->measurementFieldsForQuery($contractQuery);
        $columns = array_merge(['measurement.id', 'measurement.station', 'measurement.date', 'measurement.time'], $measurementFields);

        $latestMeasurements = DB::table('measurement')
            ->select($columns)
            ->whereIn('measurement.station', $stationNames)
            ->whereIn('measurement.id', function ($query) use ($stationNames) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('measurement')
                    ->whereIn('station', $stationNames)
                    ->groupBy('station');
            })
            ->orderBy('measurement.station')
            ->get();

        return response()->json([
            'data' => $latestMeasurements,
            'meta' => array_merge(
                $this->queryMeta($contractQuery, $latestMeasurements->count()),
                ['measurement_fields' => $measurementFields]
            ),
        ]);
    }

    private function findSubscription(string $identifier): ?Subscription
    {
        return Subscription::where('identifier', $identifier)->first();
    }

    private function resolveContractQuery(int $subscriptionId, Request $request): ?object
    {
        $queryBuilder = DB::table('contract_queries')->where('subscription_id', $subscriptionId);

        if ($request->filled('query_id')) {
            return (clone $queryBuilder)->where('id', (int) $request->query('query_id'))->first();
        }

        if ($request->filled('query')) {
            $queryValue = trim((string) $request->query('query'));

            return (clone $queryBuilder)
                ->where(function ($innerQuery) use ($queryValue) {
                    $innerQuery->where('name', $queryValue)
                        ->orWhere('endpoint', $queryValue);
                })
                ->orderBy('name')
                ->first();
        }

        return (clone $queryBuilder)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhere('status', 'Actief');
            })
            ->orderBy('name')
            ->first();
    }

    private function filteredStationsQuery(int $subscriptionId, ?object $contractQuery)
    {
        $stationQuery = DB::table('subscription_station')
            ->join('station', 'subscription_station.station', '=', 'station.name')
            ->leftJoin('geolocation', 'station.name', '=', 'geolocation.station_name')
            ->leftJoin('nearestlocation', 'station.name', '=', 'nearestlocation.station_name')
            ->leftJoin('country as geolocation_country', 'geolocation.country_code', '=', 'geolocation_country.country_code')
            ->leftJoin('country as nearestlocation_country', 'nearestlocation.country_code', '=', 'nearestlocation_country.country_code')
            ->where('subscription_station.subscription', $subscriptionId);

        if (! $contractQuery) {
            return $stationQuery;
        }

        $countryCodes = $this->csvToArray($contractQuery->country_codes ?? null);
        if ($countryCodes !== []) {
            $stationQuery->where(function ($query) use ($countryCodes) {
                $query->whereIn('geolocation.country_code', $countryCodes)
                    ->orWhereIn('nearestlocation.country_code', $countryCodes);
            });
        }

        $regionCodes = $this->csvToArray($contractQuery->region_codes ?? null);
        if ($regionCodes !== []) {
            $stationQuery->whereIn('nearestlocation.administrative_region1', $regionCodes);
        }

        if (($contractQuery->elevation_min ?? null) !== null) {
            $stationQuery->where('station.elevation', '>=', $contractQuery->elevation_min);
        }
        if (($contractQuery->elevation_max ?? null) !== null) {
            $stationQuery->where('station.elevation', '<=', $contractQuery->elevation_max);
        }
        if (($contractQuery->latitude_min ?? null) !== null) {
            $stationQuery->where('station.latitude', '>=', $contractQuery->latitude_min);
        }
        if (($contractQuery->latitude_max ?? null) !== null) {
            $stationQuery->where('station.latitude', '<=', $contractQuery->latitude_max);
        }
        if (($contractQuery->longitude_min ?? null) !== null) {
            $stationQuery->where('station.longitude', '>=', $contractQuery->longitude_min);
        }
        if (($contractQuery->longitude_max ?? null) !== null) {
            $stationQuery->where('station.longitude', '<=', $contractQuery->longitude_max);
        }

        return $stationQuery;
    }

    private function measurementFieldsForQuery(?object $contractQuery): array
    {
        if (! $contractQuery) {
            return self::DEFAULT_MEASUREMENT_FIELDS;
        }

        $selectedFields = array_values(array_filter(
            $this->csvToArray($contractQuery->measurement_fields ?? null),
            fn ($field) => in_array($field, self::DEFAULT_MEASUREMENT_FIELDS, true)
        ));

        return $selectedFields !== [] ? $selectedFields : self::DEFAULT_MEASUREMENT_FIELDS;
    }

    private function queryMeta(?object $contractQuery, int $resultCount): array
    {
        return [
            'query_applied' => $contractQuery ? [
                'id' => $contractQuery->id,
                'name' => $contractQuery->name,
                'status' => $contractQuery->status,
                'criteria' => [
                    'country_codes' => $this->csvToArray($contractQuery->country_codes ?? null),
                    'region_codes' => $this->csvToArray($contractQuery->region_codes ?? null),
                    'elevation_min' => $contractQuery->elevation_min ?? null,
                    'elevation_max' => $contractQuery->elevation_max ?? null,
                    'latitude_min' => $contractQuery->latitude_min ?? null,
                    'latitude_max' => $contractQuery->latitude_max ?? null,
                    'longitude_min' => $contractQuery->longitude_min ?? null,
                    'longitude_max' => $contractQuery->longitude_max ?? null,
                ],
            ] : null,
            'result_count' => $resultCount,
        ];
    }

    private function csvToArray(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }
}
