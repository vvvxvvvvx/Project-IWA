<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractDataController extends Controller
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

    public function queryData(Request $request, string $identifier, int $queryID): JsonResponse
    {
        $contract = $this->findContract($identifier);
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        $contractQuery = $this->findQuery($contract->id, $queryID);
        if (! $contractQuery) {
            return response()->json(['error' => 'Query niet gevonden voor dit contract.'], 404);
        }

        $stationRecords = $this->filteredStationsQuery($contractQuery)
            ->select(
                'station.name',
                'station.latitude',
                'station.longitude',
                'station.elevation',
                DB::raw('COALESCE(geolocation.country_code, nearestlocation.country_code) as country_code'),
                DB::raw('COALESCE(geolocation.country, geolocation_country.country, nearestlocation_country.country) as country'),
                'nearestlocation.administrative_region1'
            )
            ->orderBy('station.name')
            ->distinct()
            ->get();

        $stationNames = $stationRecords->pluck('name');
        $measurements = $this->measurementsForQuery($contractQuery, $stationNames)->get();
        $this->logActivity($identifier, '/IWA/contracten/' . $identifier . '/' . $queryID, $measurements->count());

        return response()->json([
            'contract' => $this->contractContext($contract),
            'query' => $this->queryContext($contractQuery),
            'stations' => $stationRecords->map(fn ($station) => $this->stationContext($station, $contract, $contractQuery))->values(),
            'data' => $measurements->map(function ($measurement) use ($contract, $contractQuery, $stationRecords) {
                $station = $stationRecords->firstWhere('name', $measurement->station);

                return array_merge((array) $measurement, [
                    'contract_id' => $contract->id,
                    'contract_identifier' => $contract->identifier,
                    'query_id' => $contractQuery->id,
                    'query_name' => $contractQuery->name,
                    'station_name' => $measurement->station,
                    'country_code' => $station->country_code ?? null,
                    'country' => $station->country ?? null,
                ]);
            })->values(),
            'meta' => array_merge(
                $this->queryMeta($contractQuery, $measurements->count()),
                ['measurement_fields' => $this->measurementFieldsForQuery($contractQuery)]
            ),
        ]);
    }

    public function defaultStations(Request $request, string $identifier): JsonResponse
    {
        $contract = $this->findContract($identifier);
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        $contractQuery = $this->defaultQuery($contract->id);
        if (! $contractQuery) {
            return response()->json(['error' => 'Er is nog geen actieve query voor dit contract.'], 404);
        }

        return $this->stations($request, $identifier, (int) $contractQuery->id);
    }

    public function stations(Request $request, string $identifier, int $queryID): JsonResponse
    {
        $contract = $this->findContract($identifier);
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        $contractQuery = $this->findQuery($contract->id, $queryID);
        if (! $contractQuery) {
            return response()->json(['error' => 'Query niet gevonden voor dit contract.'], 404);
        }

        $stations = $this->filteredStationsQuery($contractQuery)
            ->select(
                'station.name',
                'station.latitude',
                'station.longitude',
                'station.elevation',
                DB::raw('COALESCE(geolocation.country_code, nearestlocation.country_code) as country_code'),
                DB::raw('COALESCE(geolocation.country, geolocation_country.country, nearestlocation_country.country) as country'),
                'nearestlocation.name as nearest_location',
                'nearestlocation.administrative_region1'
            )
            ->orderBy('station.name')
            ->distinct()
            ->get();

        $this->logActivity($identifier, '/IWA/contracten/' . $identifier . '/' . $queryID . '/stations', $stations->count());

        return response()->json([
            'contract' => $this->contractContext($contract),
            'query' => $this->queryContext($contractQuery),
            'data' => $stations->map(fn ($station) => $this->stationContext($station, $contract, $contractQuery))->values(),
            'meta' => $this->queryMeta($contractQuery, $stations->count()),
        ]);
    }

    public function station(Request $request, string $identifier, string $name): JsonResponse
    {
        $contract = $this->findContract($identifier);
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        $queryID = (int) $request->query('query_id', 0);
        $contractQuery = $queryID > 0 ? $this->findQuery($contract->id, $queryID) : $this->defaultQuery($contract->id);

        $station = $this->filteredStationsQuery($contractQuery)
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

        if (! $station) {
            return response()->json(['error' => 'Station niet gevonden of valt buiten de querycriteria.'], 404);
        }

        $this->logActivity($identifier, '/IWA/contracten/' . $identifier . '/station/' . $name, 1);

        return response()->json([
            'contract' => $this->contractContext($contract),
            'query' => $contractQuery ? $this->queryContext($contractQuery) : null,
            'data' => $this->stationContext($station, $contract, $contractQuery),
            'meta' => $this->queryMeta($contractQuery, 1),
        ]);
    }

    private function findContract(string $identifier): ?object
    {
        return DB::table('contracts')->where('identifier', $identifier)->first();
    }

    private function findQuery(int $contractId, int $queryID): ?object
    {
        return DB::table('contract_queries')
            ->where('contract_id', $contractId)
            ->where('id', $queryID)
            ->first();
    }

    private function defaultQuery(int $contractId): ?object
    {
        return DB::table('contract_queries')
            ->where('contract_id', $contractId)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '')->orWhere('status', 'Actief');
            })
            ->orderBy('name')
            ->first();
    }

    private function filteredStationsQuery(?object $contractQuery)
    {
        $stationQuery = DB::table('station')
            ->leftJoin('geolocation', 'station.name', '=', 'geolocation.station_name')
            ->leftJoin('nearestlocation', 'station.name', '=', 'nearestlocation.station_name')
            ->leftJoin('country as geolocation_country', 'geolocation.country_code', '=', 'geolocation_country.country_code')
            ->leftJoin('country as nearestlocation_country', 'nearestlocation.country_code', '=', 'nearestlocation_country.country_code');

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

        foreach ([
            ['elevation_min', 'station.elevation', '>='],
            ['elevation_max', 'station.elevation', '<='],
            ['latitude_min', 'station.latitude', '>='],
            ['latitude_max', 'station.latitude', '<='],
            ['longitude_min', 'station.longitude', '>='],
            ['longitude_max', 'station.longitude', '<='],
        ] as [$prop, $column, $operator]) {
            if (($contractQuery->{$prop} ?? null) !== null) {
                $stationQuery->where($column, $operator, $contractQuery->{$prop});
            }
        }

        return $stationQuery;
    }

    private function measurementsForQuery(?object $contractQuery, $stationNames)
    {
        $measurementFields = $this->measurementFieldsForQuery($contractQuery);
        $columns = array_merge(['measurement.id', 'measurement.station', 'measurement.date', 'measurement.time'], $measurementFields);

        $query = DB::table('measurement')
            ->select($columns)
            ->whereIn('measurement.station', $stationNames);

        if (($contractQuery->measurement_date_from ?? null) !== null) {
            $query->whereDate('measurement.date', '>=', $contractQuery->measurement_date_from);
        }
        if (($contractQuery->measurement_date_to ?? null) !== null) {
            $query->whereDate('measurement.date', '<=', $contractQuery->measurement_date_to);
        }
        if (($contractQuery->temperature_min ?? null) !== null) {
            $query->where('measurement.temperature', '>=', $contractQuery->temperature_min);
        }
        if (($contractQuery->temperature_max ?? null) !== null) {
            $query->where('measurement.temperature', '<=', $contractQuery->temperature_max);
        }

        return $query->orderByDesc('measurement.date')->orderByDesc('measurement.time');
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
                    'measurement_date_from' => $contractQuery->measurement_date_from ?? null,
                    'measurement_date_to' => $contractQuery->measurement_date_to ?? null,
                    'temperature_min' => $contractQuery->temperature_min ?? null,
                    'temperature_max' => $contractQuery->temperature_max ?? null,
                ],
            ] : null,
            'result_count' => $resultCount,
        ];
    }

    private function contractContext(object $contract): array
    {
        return [
            'id' => $contract->id,
            'identifier' => $contract->identifier,
        ];
    }

    private function queryContext(?object $contractQuery): ?array
    {
        if (! $contractQuery) {
            return null;
        }

        return [
            'id' => $contractQuery->id,
            'name' => $contractQuery->name,
            'status' => $contractQuery->status,
        ];
    }

    private function stationContext(object $station, object $contract, ?object $contractQuery): array
    {
        return [
            'contract_id' => $contract->id,
            'contract_identifier' => $contract->identifier,
            'query_id' => $contractQuery->id ?? null,
            'query_name' => $contractQuery->name ?? null,
            'station_name' => $station->name,
            'country_code' => $station->country_code ?? null,
            'country' => $station->country ?? null,
            'nearest_location' => $station->nearest_location ?? null,
            'administrative_region1' => $station->administrative_region1 ?? null,
            'latitude' => $station->latitude ?? null,
            'longitude' => $station->longitude ?? null,
            'elevation' => $station->elevation ?? null,
        ];
    }

    private function csvToArray(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    private function logActivity(string $identifier, string $endpoint, int $resultCount): void
    {
        if (! DB::getSchemaBuilder()->hasTable('contract_endpoint_activity')) {
            return;
        }

        DB::table('contract_endpoint_activity')->insert([
            'identifier' => $identifier,
            'endpoint_used' => $endpoint,
            'files_downloaded' => 0,
            'activity_date' => now()->toDateString(),
            'activity_time' => now()->format('H:i:s'),
            'authorized' => 1,
            'data_transferred' => $resultCount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
