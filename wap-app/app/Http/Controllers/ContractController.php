<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ContractController extends Controller
{
    private const AUTHORIZED_USER_ROLES = ['Beheerder', 'Gebruiker'];

    private const MEASUREMENT_FIELD_OPTIONS = [
        'temperature' => 'Temperatuur',
        'dewpoint_temperature' => 'Dauwpunt',
        'air_pressure_station' => 'Luchtdruk station',
        'air_pressure_sea_level' => 'Luchtdruk zeeniveau',
        'visibility' => 'Zicht',
        'wind_speed' => 'Windsnelheid',
        'percipation' => 'Neerslag',
        'snow_depth' => 'Sneeuwdiepte',
        'conditions' => 'Condities',
        'cloud_cover' => 'Bewolking',
        'wind_direction' => 'Windrichting',
    ];

    public function index(): View
    {
        $contracts = $this->contractOverviewQuery()->get();

        return view('contracts.contract-list', compact('contracts'));
    }

    public function overview(): View
    {
        $contracts = $this->contractOverviewQuery()->get();

        $summary = [
            'contract_count' => $contracts->count(),
            'active_count' => $contracts->filter(fn ($contract) => empty($contract->end_date) || $contract->end_date >= now()->toDateString())->count(),
            'authorized_user_count' => $contracts->sum('authorized_user_count'),
            'query_count' => $contracts->sum('query_count'),
        ];

        $recentContracts = $contracts->take(5);

        return view('contracts.overview', compact('summary', 'recentContracts'));
    }

    public function authorizedUsersIndex(): View
    {
        $authorizedUsers = DB::table('contract_authorized_users')
            ->join('subscriptions', 'contract_authorized_users.subscription_id', '=', 'subscriptions.id')
            ->join('companies', 'subscriptions.company', '=', 'companies.id')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->select(
                'contract_authorized_users.id',
                'contract_authorized_users.name',
                'contract_authorized_users.email',
                'contract_authorized_users.role_label',
                'contract_authorized_users.status',
                'contract_authorized_users.notes',
                'contract_authorized_users.updated_at',
                'subscriptions.identifier as contract_identifier',
                'companies.name as company_name',
                'subscription_types.name as type_name'
            )
            ->orderBy('companies.name')
            ->orderBy('subscriptions.identifier')
            ->orderBy('contract_authorized_users.name')
            ->get();

        $summary = [
            'authorized_user_count' => $authorizedUsers->count(),
            'contract_count' => $authorizedUsers->pluck('contract_identifier')->unique()->count(),
            'active_count' => $authorizedUsers->where('status', 'Actief')->count(),
        ];

        return view('contracts.authorized-users-list', compact('authorizedUsers', 'summary'));
    }

    public function show(string $identifier): View
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $stations = DB::table('subscription_station')
            ->join('station', 'subscription_station.station', '=', 'station.name')
            ->leftJoin('nearestlocation', 'station.name', '=', 'nearestlocation.station_name')
            ->where('subscription_station.subscription', $contract->id)
            ->select(
                'station.name as stn',
                'nearestlocation.name as location_label',
                'station.latitude as lat',
                'station.longitude as lon'
            )
            ->orderBy('station.name')
            ->get();

        $activity = DB::table('endpoint_activity')
            ->where('identifier', $identifier)
            ->orderByDesc('activity_date')
            ->orderByDesc('activity_time')
            ->limit(25)
            ->get();

        $authorizedUsers = DB::table('contract_authorized_users')
            ->where('subscription_id', $contract->id)
            ->orderBy('name')
            ->get();

        $queryConfigurationOptions = $this->queryConfigurationOptions();
        $queryColumnsAvailable = $this->contractQueryConfigurationColumnsExist();
        $queries = $this->prepareContractQueries(
            DB::table('contract_queries')
                ->where('subscription_id', $contract->id)
                ->orderBy('name')
                ->get(),
            $queryConfigurationOptions['measurement_fields'] ?? []
        );

        $authorizedUserRoles = self::AUTHORIZED_USER_ROLES;
        $rolePermissions = $this->rolePermissionsForContract($contract->id);

        return view('contracts.contract-details', compact(
            'contract',
            'stations',
            'activity',
            'authorizedUsers',
            'queries',
            'authorizedUserRoles',
            'rolePermissions',
            'queryConfigurationOptions',
            'queryColumnsAvailable'
        ));
    }

    public function create(): View
    {
        return view('contracts.contract-form', $this->contractFormData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateContract($request, null);

        try {
            DB::table('subscriptions')->insert($data);
            return redirect()->route('contracts.index')->with('success', 'Contract aangemaakt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij contract aanmaken', ['exception' => $e->getMessage(), 'payload' => $data]);
            return back()->withInput()->with('error', 'Contract kon niet worden aangemaakt.');
        }
    }

    public function edit(string $identifier): View
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        return view('contracts.contract-form', $this->contractFormData($contract));
    }

    public function update(Request $request, string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $data = $this->validateContract($request, $contract->id);

        try {
            DB::table('subscriptions')->where('id', $contract->id)->update($data);
            return redirect()->route('contracts.show', $data['identifier'])->with('success', 'Contract bijgewerkt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij contract bijwerken', ['exception' => $e->getMessage(), 'identifier' => $identifier, 'payload' => $data]);
            return back()->withInput()->with('error', 'Contract kon niet worden bijgewerkt.');
        }
    }

    public function destroy(string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        try {
            DB::transaction(function () use ($contract) {
                DB::table('contract_authorized_users')->where('subscription_id', $contract->id)->delete();
                DB::table('contract_queries')->where('subscription_id', $contract->id)->delete();
                if (Schema::hasTable('contract_role_permissions')) {
                    DB::table('contract_role_permissions')->where('subscription_id', $contract->id)->delete();
                }
                DB::table('subscription_station')->where('subscription', $contract->id)->delete();
                DB::table('endpoint_activity')->where('identifier', $contract->identifier)->delete();
                DB::table('subscriptions')->where('id', $contract->id)->delete();
            });

            return redirect()->route('contracts.index')->with('success', 'Contract verwijderd.');
        } catch (\Throwable $e) {
            Log::error('Fout bij contract verwijderen', ['exception' => $e->getMessage(), 'identifier' => $identifier]);
            return back()->with('error', 'Contract kon niet worden verwijderd.');
        }
    }

    public function regenerateToken(string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $token = strtoupper(bin2hex(random_bytes(12)));
        DB::table('subscriptions')->where('id', $contract->id)->update(['token' => $token]);

        return redirect()->route('contracts.edit', $identifier)->with('success', 'Nieuw contracttoken gegenereerd.');
    }

    public function markTokenSent(string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $stamp = 'Contracttoken verstuurd op ' . now()->format('d-m-Y H:i');
        $notes = trim(($contract->notes ? $contract->notes . PHP_EOL : '') . $stamp);
        DB::table('subscriptions')->where('id', $contract->id)->update(['notes' => $notes]);

        return redirect()->route('contracts.edit', $identifier)->with('success', 'Contracttoken als verstuurd gemarkeerd.');
    }

    public function storeAuthorizedUser(Request $request, string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'role_label' => ['required', 'in:' . implode(',', self::AUTHORIZED_USER_ROLES)],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['subscription_id'] = $contract->id;
        $data['status'] = $data['status'] ?: 'Actief';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('contract_authorized_users')->insert($data);
        return redirect()->route('contracts.show', $identifier)->with('success', 'Geautoriseerde gebruiker toegevoegd.');
    }

    public function updateAuthorizedUser(Request $request, string $identifier, int $userId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'role_label' => ['required', 'in:' . implode(',', self::AUTHORIZED_USER_ROLES)],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['updated_at'] = now();

        DB::table('contract_authorized_users')
            ->where('subscription_id', $contract->id)
            ->where('id', $userId)
            ->update($data);

        return redirect()->route('contracts.show', $identifier)->with('success', 'Geautoriseerde gebruiker bijgewerkt.');
    }

    public function destroyAuthorizedUser(string $identifier, int $userId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        DB::table('contract_authorized_users')
            ->where('subscription_id', $contract->id)
            ->where('id', $userId)
            ->delete();

        return redirect()->route('contracts.show', $identifier)->with('success', 'Geautoriseerde gebruiker verwijderd.');
    }

    public function updateRolePermissions(Request $request, string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        if (! Schema::hasTable('contract_role_permissions')) {
            return redirect()->route('contracts.show', $identifier)->with('error', 'Voer eerst de nieuwste migraties uit om contractbevoegdheden op te slaan.');
        }

        $validated = $request->validate([
            'roles' => ['required', 'array'],
            'roles.Beheerder' => ['required', 'array'],
            'roles.Gebruiker' => ['required', 'array'],
        ]);

        foreach (self::AUTHORIZED_USER_ROLES as $roleLabel) {
            $roleData = $validated['roles'][$roleLabel] ?? [];

            DB::table('contract_role_permissions')->updateOrInsert(
                [
                    'subscription_id' => $contract->id,
                    'role_label' => $roleLabel,
                ],
                [
                    'can_view_contract' => array_key_exists('can_view_contract', $roleData) ? 1 : 0,
                    'can_view_queries' => array_key_exists('can_view_queries', $roleData) ? 1 : 0,
                    'can_manage_queries' => array_key_exists('can_manage_queries', $roleData) ? 1 : 0,
                    'can_manage_authorized_users' => array_key_exists('can_manage_authorized_users', $roleData) ? 1 : 0,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return redirect()->route('contracts.show', $identifier)->with('success', 'Bevoegdheden per contractrol bijgewerkt.');
    }

    public function storeQuery(Request $request, string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        if (! $this->contractQueryConfigurationColumnsExist()) {
            return redirect()->route('contracts.show', $identifier)->with('error', 'Voer eerst de nieuwste migraties uit om querycriteria en meetvelden op te slaan.');
        }

        $data = $this->validateContractQuery($request);
        $data['subscription_id'] = $contract->id;
        $data['endpoint'] = $data['endpoint'] ?: '/IWA/abonnement/' . $contract->identifier . '/stations';
        $data['format'] = $data['format'] ?: 'JSON';
        $data['status'] = $data['status'] ?: 'Actief';
        $data['query_text'] = $this->buildContractQuerySummary($data);
        $data = $this->contractQueryDatabasePayload($data);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('contract_queries')->insert($data);
        return redirect()->route('contracts.show', $identifier)->with('success', 'Contractquery toegevoegd.');
    }

    public function updateQuery(Request $request, string $identifier, int $queryId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        if (! $this->contractQueryConfigurationColumnsExist()) {
            return redirect()->route('contracts.show', $identifier)->with('error', 'Voer eerst de nieuwste migraties uit om querycriteria en meetvelden op te slaan.');
        }

        $data = $this->validateContractQuery($request);
        $data['endpoint'] = $data['endpoint'] ?: '/IWA/abonnement/' . $contract->identifier . '/stations';
        $data['query_text'] = $this->buildContractQuerySummary($data);
        $data = $this->contractQueryDatabasePayload($data);
        $data['updated_at'] = now();

        DB::table('contract_queries')
            ->where('subscription_id', $contract->id)
            ->where('id', $queryId)
            ->update($data);

        return redirect()->route('contracts.show', $identifier)->with('success', 'Contractquery bijgewerkt.');
    }

    public function destroyQuery(string $identifier, int $queryId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        DB::table('contract_queries')
            ->where('subscription_id', $contract->id)
            ->where('id', $queryId)
            ->delete();

        return redirect()->route('contracts.show', $identifier)->with('success', 'Contractquery verwijderd.');
    }


    private function validateContractQuery(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'endpoint' => ['nullable', 'string', 'max:255'],
            'format' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'measurement_fields' => ['required', 'array', 'min:1'],
            'measurement_fields.*' => ['required', 'string', 'in:' . implode(',', array_keys(self::MEASUREMENT_FIELD_OPTIONS))],
            'country_codes' => ['nullable', 'array'],
            'country_codes.*' => ['nullable', 'string', 'size:2'],
            'region_codes' => ['nullable', 'array'],
            'region_codes.*' => ['nullable', 'string', 'max:100'],
            'elevation_min' => ['nullable', 'numeric'],
            'elevation_max' => ['nullable', 'numeric'],
            'latitude_min' => ['nullable', 'numeric'],
            'latitude_max' => ['nullable', 'numeric'],
            'longitude_min' => ['nullable', 'numeric'],
            'longitude_max' => ['nullable', 'numeric'],
        ]);

        $validated['measurement_fields'] = $this->normalizeStringList($validated['measurement_fields'] ?? []);
        $validated['country_codes'] = $this->normalizeStringList($validated['country_codes'] ?? []);
        $validated['region_codes'] = $this->normalizeStringList($validated['region_codes'] ?? []);

        foreach (['elevation_min', 'elevation_max', 'latitude_min', 'latitude_max', 'longitude_min', 'longitude_max'] as $numericField) {
            if ($request->filled($numericField)) {
                $validated[$numericField] = $request->input($numericField);
            } else {
                $validated[$numericField] = null;
            }
        }

        return $validated;
    }

    private function contractQueryDatabasePayload(array $data): array
    {
        $data['measurement_fields'] = implode(',', $data['measurement_fields'] ?? []);
        $data['country_codes'] = implode(',', $data['country_codes'] ?? []);
        $data['region_codes'] = implode(',', $data['region_codes'] ?? []);

        return $data;
    }

    private function buildContractQuerySummary(array $data): string
    {
        $parts = [];

        if (! empty($data['country_codes'])) {
            $parts[] = 'Landen: ' . implode(', ', $data['country_codes']);
        }
        if (! empty($data['region_codes'])) {
            $parts[] = "Regio's: " . implode(', ', $data['region_codes']);
        }
        if ($data['elevation_min'] !== null || $data['elevation_max'] !== null) {
            $min = $data['elevation_min'] !== null ? $data['elevation_min'] : 'vrij';
            $max = $data['elevation_max'] !== null ? $data['elevation_max'] : 'vrij';
            $parts[] = 'Elevation: ' . $min . ' t/m ' . $max;
        }
        if ($data['latitude_min'] !== null || $data['latitude_max'] !== null) {
            $min = $data['latitude_min'] !== null ? $data['latitude_min'] : 'vrij';
            $max = $data['latitude_max'] !== null ? $data['latitude_max'] : 'vrij';
            $parts[] = 'Breedtegraad: ' . $min . ' t/m ' . $max;
        }
        if ($data['longitude_min'] !== null || $data['longitude_max'] !== null) {
            $min = $data['longitude_min'] !== null ? $data['longitude_min'] : 'vrij';
            $max = $data['longitude_max'] !== null ? $data['longitude_max'] : 'vrij';
            $parts[] = 'Lengtegraad: ' . $min . ' t/m ' . $max;
        }
        if (! empty($data['measurement_fields'])) {
            $labels = array_map(fn ($field) => self::MEASUREMENT_FIELD_OPTIONS[$field] ?? $field, $data['measurement_fields']);
            $parts[] = 'Meetvelden: ' . implode(', ', $labels);
        }

        return implode(' | ', $parts);
    }

    private function normalizeStringList(array $values): array
    {
        return array_values(array_filter(array_unique(array_map(fn ($value) => trim((string) $value), $values))));
    }

    private function queryConfigurationOptions(): array
    {
        $countryCodes = collect()
            ->merge(
                DB::table('geolocation')
                    ->whereNotNull('country_code')
                    ->where('country_code', '!=', '')
                    ->pluck('country_code')
            )
            ->merge(
                DB::table('nearestlocation')
                    ->whereNotNull('country_code')
                    ->where('country_code', '!=', '')
                    ->pluck('country_code')
            )
            ->merge(
                DB::table('country')
                    ->whereNotNull('country_code')
                    ->where('country_code', '!=', '')
                    ->pluck('country_code')
            )
            ->map(fn ($countryCode) => strtoupper(trim((string) $countryCode)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $regionCodes = DB::table('nearestlocation')
            ->whereNotNull('administrative_region1')
            ->where('administrative_region1', '!=', '')
            ->select('administrative_region1')
            ->distinct()
            ->orderBy('administrative_region1')
            ->pluck('administrative_region1')
            ->map(fn ($regionCode) => trim((string) $regionCode))
            ->filter()
            ->values()
            ->all();

        return [
            'country_codes' => $countryCodes,
            'region_codes' => $regionCodes,
            'measurement_fields' => self::MEASUREMENT_FIELD_OPTIONS,
        ];
    }

    private function contractQueryConfigurationColumnsExist(): bool
    {
        return Schema::hasColumns('contract_queries', [
            'measurement_fields',
            'country_codes',
            'region_codes',
            'elevation_min',
            'elevation_max',
            'latitude_min',
            'latitude_max',
            'longitude_min',
            'longitude_max',
        ]);
    }



    private function prepareContractQueries(iterable $queries, array $measurementFieldOptions): array
    {
        $preparedQueries = [];

        foreach ($queries as $query) {
            $query->measurement_fields_list = $this->csvToArray($query->measurement_fields ?? null);
            $query->country_codes_list = $this->csvToArray($query->country_codes ?? null);
            $query->region_codes_list = $this->csvToArray($query->region_codes ?? null);
            $query->measurement_field_labels = array_map(
                fn ($fieldKey) => $measurementFieldOptions[$fieldKey] ?? $fieldKey,
                $query->measurement_fields_list
            );
            $query->criteria_summary = $this->contractQueryCriteriaSummaryFromRecord($query);
            $preparedQueries[] = $query;
        }

        return $preparedQueries;
    }

    private function csvToArray($value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ($value ?? '')))));
    }

    private function contractQueryCriteriaSummaryFromRecord(object $query): string
    {
        $parts = [];

        if (! empty($query->country_codes_list)) {
            $parts[] = 'Landen: ' . implode(', ', $query->country_codes_list);
        }
        if (! empty($query->region_codes_list)) {
            $parts[] = "Regio's: " . implode(', ', $query->region_codes_list);
        }
        if (($query->elevation_min ?? null) !== null || ($query->elevation_max ?? null) !== null) {
            $parts[] = 'Elevation: ' . (($query->elevation_min ?? null) ?? 'vrij') . ' t/m ' . (($query->elevation_max ?? null) ?? 'vrij');
        }
        if (($query->latitude_min ?? null) !== null || ($query->latitude_max ?? null) !== null) {
            $parts[] = 'Breedtegraad: ' . (($query->latitude_min ?? null) ?? 'vrij') . ' t/m ' . (($query->latitude_max ?? null) ?? 'vrij');
        }
        if (($query->longitude_min ?? null) !== null || ($query->longitude_max ?? null) !== null) {
            $parts[] = 'Lengtegraad: ' . (($query->longitude_min ?? null) ?? 'vrij') . ' t/m ' . (($query->longitude_max ?? null) ?? 'vrij');
        }

        return $parts ? implode(' | ', $parts) : '—';
    }

    private function rolePermissionsForContract(int $contractId): array
    {
        $defaults = [
            'Beheerder' => [
                'can_view_contract' => true,
                'can_view_queries' => true,
                'can_manage_queries' => true,
                'can_manage_authorized_users' => true,
            ],
            'Gebruiker' => [
                'can_view_contract' => true,
                'can_view_queries' => true,
                'can_manage_queries' => false,
                'can_manage_authorized_users' => false,
            ],
        ];

        if (! Schema::hasTable('contract_role_permissions')) {
            return $defaults;
        }

        $storedPermissions = DB::table('contract_role_permissions')
            ->where('subscription_id', $contractId)
            ->get()
            ->keyBy('role_label');

        $permissions = [];
        foreach (self::AUTHORIZED_USER_ROLES as $roleLabel) {
            $stored = $storedPermissions->get($roleLabel);
            $permissions[$roleLabel] = [
                'can_view_contract' => $stored ? (bool) $stored->can_view_contract : $defaults[$roleLabel]['can_view_contract'],
                'can_view_queries' => $stored ? (bool) $stored->can_view_queries : $defaults[$roleLabel]['can_view_queries'],
                'can_manage_queries' => $stored ? (bool) $stored->can_manage_queries : $defaults[$roleLabel]['can_manage_queries'],
                'can_manage_authorized_users' => $stored ? (bool) $stored->can_manage_authorized_users : $defaults[$roleLabel]['can_manage_authorized_users'],
            ];
        }

        return $permissions;
    }

    private function contractOverviewQuery()
    {
        $authorizedUsers = DB::table('contract_authorized_users')
            ->select('subscription_id', DB::raw('COUNT(*) as authorized_user_count'))
            ->groupBy('subscription_id');

        $contractQueries = DB::table('contract_queries')
            ->select('subscription_id', DB::raw('COUNT(*) as query_count'))
            ->groupBy('subscription_id');

        $contractStations = DB::table('subscription_station')
            ->select('subscription', DB::raw('COUNT(*) as station_count'))
            ->groupBy('subscription');

        $endpointActivity = DB::table('endpoint_activity')
            ->select(
                'identifier',
                DB::raw('SUM(CASE WHEN authorized = 1 THEN 1 ELSE 0 END) as successful_calls')
            )
            ->groupBy('identifier');

        return DB::table('subscriptions')
            ->join('companies', 'subscriptions.company', '=', 'companies.id')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->leftJoinSub($authorizedUsers, 'authorized_users', function ($join) {
                $join->on('subscriptions.id', '=', 'authorized_users.subscription_id');
            })
            ->leftJoinSub($contractQueries, 'contract_queries_summary', function ($join) {
                $join->on('subscriptions.id', '=', 'contract_queries_summary.subscription_id');
            })
            ->leftJoinSub($contractStations, 'contract_stations', function ($join) {
                $join->on('subscriptions.id', '=', 'contract_stations.subscription');
            })
            ->leftJoinSub($endpointActivity, 'endpoint_activity_summary', function ($join) {
                $join->on('subscriptions.identifier', '=', 'endpoint_activity_summary.identifier');
            })
            ->select(
                'subscriptions.id',
                'subscriptions.identifier',
                'subscriptions.start_date',
                'subscriptions.end_date',
                'subscriptions.price',
                'subscriptions.notes',
                'subscriptions.token',
                'companies.name as company_name',
                'subscription_types.name as type_name',
                DB::raw('COALESCE(authorized_users.authorized_user_count, 0) as authorized_user_count'),
                DB::raw('COALESCE(contract_queries_summary.query_count, 0) as query_count'),
                DB::raw('COALESCE(contract_stations.station_count, 0) as station_count'),
                DB::raw('COALESCE(endpoint_activity_summary.successful_calls, 0) as successful_calls')
            )
            ->orderBy('companies.name')
            ->orderBy('subscriptions.identifier');
    }

    private function contractFormData(?object $contract = null): array
    {
        return [
            'contract' => $contract,
            'companies' => DB::table('companies')->orderBy('name')->get(),
            'types' => DB::table('subscription_types')->orderBy('name')->get(),
        ];
    }

    private function validateContract(Request $request, ?int $ignoreId): array
    {
        return $request->validate([
            'company' => ['required', 'integer', 'exists:companies,id'],
            'type' => ['required', 'integer', 'exists:subscription_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:256'],
            'identifier' => ['required', 'string', 'max:45', 'unique:subscriptions,identifier,' . ($ignoreId ?? 'NULL') . ',id'],
            'token' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function findContract(string $identifier): ?object
    {
        return DB::table('subscriptions')
            ->join('companies', 'subscriptions.company', '=', 'companies.id')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->where('subscriptions.identifier', $identifier)
            ->select(
                'subscriptions.*',
                'companies.name as company_name',
                'subscription_types.name as type_name'
            )
            ->first();
    }
}
