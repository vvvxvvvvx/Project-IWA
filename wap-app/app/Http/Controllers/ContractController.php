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

    private const CONTRACT_USER_PERMISSION_LEVELS = ['admin', 'user'];

    private const CONTRACT_TYPE_SEED = [
        'data_contract' => 'Datacontract',
        'service_contract' => 'Servicecontract',
    ];

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
            'active_count' => $contracts->filter(fn ($contract) => $this->isContractActive($contract))->count(),
            'authorized_user_count' => (int) $contracts->sum('authorized_user_count'),
            'query_count' => (int) $contracts->sum('query_count'),
        ];

        $recentContracts = $contracts->take(5);

        return view('contracts.overview', compact('summary', 'recentContracts'));
    }

    public function authorizedUsersIndex(): View
    {
        $authorizedUsers = DB::table('contract_authorized_users')
            ->join('contracts', 'contract_authorized_users.contract_id', '=', 'contracts.id')
            ->join('companies', 'contracts.company_id', '=', 'companies.id')
            ->leftJoin('contract_types', 'contracts.contract_type_id', '=', 'contract_types.id')
            ->whereNull('contract_authorized_users.deleted_at')
            ->select(
                'contract_authorized_users.id',
                'contract_authorized_users.name',
                'contract_authorized_users.email',
                'contract_authorized_users.user_identifier',
                'contract_authorized_users.permission_level',
                'contract_authorized_users.role_label',
                'contract_authorized_users.status',
                'contract_authorized_users.notes',
                'contract_authorized_users.updated_at',
                'contracts.identifier as contract_identifier',
                'contracts.status as contract_status',
                'companies.name as company_name',
                DB::raw("COALESCE(contract_types.name, 'Onbekend type') as type_name")
            )
            ->orderBy('companies.name')
            ->orderBy('contracts.identifier')
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

        $contacts = DB::table('relations')
            ->where('company', $contract->company_id)
            ->orderBy('name')
            ->get();

        $activity = Schema::hasTable('contract_endpoint_activity')
            ? DB::table('contract_endpoint_activity')
                ->where('identifier', $identifier)
                ->orderByDesc('activity_date')
                ->orderByDesc('activity_time')
                ->limit(25)
                ->get()
            : collect();

        $authorizedUsers = DB::table('contract_authorized_users')
            ->where('contract_id', $contract->id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $queryConfigurationOptions = $this->queryConfigurationOptions();
        $queryColumnsAvailable = $this->contractQueryConfigurationColumnsExist();
        $queries = $this->prepareContractQueries(
            DB::table('contract_queries')
                ->where('contract_id', $contract->id)
                ->orderBy('name')
                ->get(),
            $queryConfigurationOptions['measurement_fields'] ?? []
        );

        $authorizedUserRoles = self::AUTHORIZED_USER_ROLES;
        $rolePermissions = $this->rolePermissionsForContract($contract->id);
        $firstQuery = $queries[0] ?? null;
        $queryIdPlaceholder = $firstQuery->id ?? '{queryID}';
        $apiEndpoints = [
            'login' => url('/api/IWA/contracten/login'),
            'query_data' => url('/api/IWA/contracten/' . $contract->identifier . '/' . $queryIdPlaceholder),
            'stations' => url('/api/IWA/contracten/' . $contract->identifier . '/stations'),
            'station' => url('/api/IWA/contracten/' . $contract->identifier . '/station/{name}'),
            'users' => url('/api/IWA/contracten/' . $contract->identifier . '/users'),
            'logout' => url('/api/IWA/contract/logout'),
        ];

        return view('contracts.contract-details', compact(
            'contract',
            'activity',
            'contacts',
            'authorizedUsers',
            'queries',
            'authorizedUserRoles',
            'rolePermissions',
            'queryConfigurationOptions',
            'queryColumnsAvailable',
            'apiEndpoints'
        ));
    }

    public function create(): View
    {
        return view('contracts.contract-form', $this->contractFormData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateContract($request, null);
        $adminUser = $this->validateContractUserForCreate($request);
        $data['api_token'] = $data['api_token'] ?: strtoupper(bin2hex(random_bytes(12)));
        $data['created_at'] = now();
        $data['updated_at'] = now();

        try {
            DB::transaction(function () use ($data, $adminUser) {
                $contractId = DB::table('contracts')->insertGetId($data);
                DB::table('contract_authorized_users')->insert([
                    'contract_id' => $contractId,
                    'name' => $adminUser['admin_name'],
                    'email' => $adminUser['admin_email'],
                    'user_identifier' => $adminUser['admin_user_identifier'],
                    'password_hash' => bcrypt($adminUser['admin_password']),
                    'permission_level' => 'admin',
                    'role_label' => 'Beheerder',
                    'status' => 'Actief',
                    'is_active' => 1,
                    'notes' => $adminUser['admin_notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

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
        $data['updated_at'] = now();

        try {
            DB::table('contracts')->where('id', $contract->id)->update($data);

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
                DB::table('contract_authorized_users')->where('contract_id', $contract->id)->delete();
                DB::table('contract_queries')->where('contract_id', $contract->id)->delete();
                if (Schema::hasTable('contract_role_permissions')) {
                    DB::table('contract_role_permissions')->where('contract_id', $contract->id)->delete();
                }
                if (Schema::hasTable('contract_endpoint_activity')) {
                    DB::table('contract_endpoint_activity')->where('identifier', $contract->identifier)->delete();
                }
                DB::table('contracts')->where('id', $contract->id)->delete();
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
        DB::table('contracts')->where('id', $contract->id)->update(['api_token' => $token, 'updated_at' => now()]);

        return redirect()->route('contracts.edit', $identifier)->with('success', 'Nieuw contracttoken gegenereerd.');
    }

    public function markTokenSent(string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $stamp = 'Contracttoken verstuurd op ' . now()->format('d-m-Y H:i');
        $notes = trim(($contract->notes ? $contract->notes . PHP_EOL : '') . $stamp);
        DB::table('contracts')->where('id', $contract->id)->update(['notes' => $notes, 'updated_at' => now()]);

        return redirect()->route('contracts.edit', $identifier)->with('success', 'Contracttoken als verstuurd gemarkeerd.');
    }

    public function storeAuthorizedUser(Request $request, string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $data = $this->validateAuthorizedUser($request, true);
        $data['contract_id'] = $contract->id;
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

        $data = $this->validateAuthorizedUser($request, false);
        $data['updated_at'] = now();

        DB::table('contract_authorized_users')
            ->where('contract_id', $contract->id)
            ->where('id', $userId)
            ->update($data);

        return redirect()->route('contracts.show', $identifier)->with('success', 'Geautoriseerde gebruiker bijgewerkt.');
    }

    public function destroyAuthorizedUser(string $identifier, int $userId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        DB::table('contract_authorized_users')
            ->where('contract_id', $contract->id)
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
                    'contract_id' => $contract->id,
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
        $data['contract_id'] = $contract->id;
        $data['endpoint'] = $data['endpoint'] ?: '/IWA/contracten/' . $contract->identifier . '/{queryID}';
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
        $data['endpoint'] = $data['endpoint'] ?: '/IWA/contracten/' . $contract->identifier . '/{queryID}';
        $data['query_text'] = $this->buildContractQuerySummary($data);
        $data = $this->contractQueryDatabasePayload($data);
        $data['updated_at'] = now();

        DB::table('contract_queries')
            ->where('contract_id', $contract->id)
            ->where('id', $queryId)
            ->update($data);

        return redirect()->route('contracts.show', $identifier)->with('success', 'Contractquery bijgewerkt.');
    }

    public function destroyQuery(string $identifier, int $queryId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        DB::table('contract_queries')
            ->where('contract_id', $contract->id)
            ->where('id', $queryId)
            ->delete();

        return redirect()->route('contracts.show', $identifier)->with('success', 'Contractquery verwijderd.');
    }

    private function validateAuthorizedUser(Request $request, bool $requirePassword): array
    {
        $passwordRule = $requirePassword ? ['required', 'string', 'min:8', 'max:255'] : ['nullable', 'string', 'min:8', 'max:255'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'user_identifier' => ['required', 'string', 'max:100'],
            'password' => $passwordRule,
            'permission_level' => ['required', 'in:' . implode(',', self::CONTRACT_USER_PERMISSION_LEVELS)],
            'role_label' => ['required', 'in:' . implode(',', self::AUTHORIZED_USER_ROLES)],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($data['password'])) {
            $data['password_hash'] = bcrypt($data['password']);
        }

        unset($data['password']);
        $data['is_active'] = ($data['status'] ?? 'Actief') === 'Actief' ? 1 : 0;

        return $data;
    }

    private function validateContractUserForCreate(Request $request): array
    {
        return $request->validate([
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:100'],
            'admin_user_identifier' => ['required', 'string', 'max:100'],
            'admin_password' => ['required', 'string', 'min:8', 'max:255'],
            'admin_notes' => ['nullable', 'string'],
        ], [], [
            'admin_name' => 'admin naam',
            'admin_email' => 'admin e-mail',
            'admin_user_identifier' => 'admin user identifier',
            'admin_password' => 'admin wachtwoord',
        ]);
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
            'measurement_date_from' => ['nullable', 'date'],
            'measurement_date_to' => ['nullable', 'date', 'after_or_equal:measurement_date_from'],
            'temperature_min' => ['nullable', 'numeric'],
            'temperature_max' => ['nullable', 'numeric'],
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

        foreach (['temperature_min', 'temperature_max', 'elevation_min', 'elevation_max', 'latitude_min', 'latitude_max', 'longitude_min', 'longitude_max'] as $numericField) {
            $validated[$numericField] = $request->filled($numericField) ? $request->input($numericField) : null;
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
        if (($data['measurement_date_from'] ?? null) !== null || ($data['measurement_date_to'] ?? null) !== null) {
            $parts[] = 'Meetdatum: ' . (($data['measurement_date_from'] ?? null) ?? 'vrij') . ' t/m ' . (($data['measurement_date_to'] ?? null) ?? 'vrij');
        }
        if (($data['temperature_min'] ?? null) !== null || ($data['temperature_max'] ?? null) !== null) {
            $parts[] = 'Temperatuur: ' . (($data['temperature_min'] ?? null) ?? 'vrij') . ' t/m ' . (($data['temperature_max'] ?? null) ?? 'vrij');
        }
        if ($data['elevation_min'] !== null || $data['elevation_max'] !== null) {
            $parts[] = 'Elevation: ' . ($data['elevation_min'] ?? 'vrij') . ' t/m ' . ($data['elevation_max'] ?? 'vrij');
        }
        if ($data['latitude_min'] !== null || $data['latitude_max'] !== null) {
            $parts[] = 'Breedtegraad: ' . ($data['latitude_min'] ?? 'vrij') . ' t/m ' . ($data['latitude_max'] ?? 'vrij');
        }
        if ($data['longitude_min'] !== null || $data['longitude_max'] !== null) {
            $parts[] = 'Lengtegraad: ' . ($data['longitude_min'] ?? 'vrij') . ' t/m ' . ($data['longitude_max'] ?? 'vrij');
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
            ->merge($this->safeColumnValues('geolocation', 'country_code'))
            ->merge($this->safeColumnValues('nearestlocation', 'country_code'))
            ->merge($this->safeColumnValues('country', 'country_code'))
            ->map(fn ($countryCode) => strtoupper(trim((string) $countryCode)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $regionCodes = collect($this->safeColumnValues('nearestlocation', 'administrative_region1'))
            ->map(fn ($regionCode) => trim((string) $regionCode))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'country_codes' => $countryCodes,
            'region_codes' => $regionCodes,
            'measurement_fields' => self::MEASUREMENT_FIELD_OPTIONS,
        ];
    }

    private function safeColumnValues(string $table, string $column): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }

    private function contractQueryConfigurationColumnsExist(): bool
    {
        return Schema::hasTable('contract_queries') && Schema::hasColumns('contract_queries', [
            'measurement_fields',
            'country_codes',
            'region_codes',
            'elevation_min',
            'elevation_max',
            'latitude_min',
            'latitude_max',
            'longitude_min',
            'longitude_max',
            'measurement_date_from',
            'measurement_date_to',
            'temperature_min',
            'temperature_max',
            'contract_id',
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
            $query->preview_stations = $this->previewStationsForContractQuery($query);
            $preparedQueries[] = $query;
        }

        return $preparedQueries;
    }

    private function previewStationsForContractQuery(object $query)
    {
        $stationQuery = DB::table('station')
            ->leftJoin('geolocation', 'station.name', '=', 'geolocation.station_name')
            ->leftJoin('nearestlocation', 'station.name', '=', 'nearestlocation.station_name');

        if (! empty($query->country_codes_list)) {
            $stationQuery->where(function ($subQuery) use ($query) {
                $subQuery->whereIn('geolocation.country_code', $query->country_codes_list)
                    ->orWhereIn('nearestlocation.country_code', $query->country_codes_list);
            });
        }

        if (! empty($query->region_codes_list)) {
            $stationQuery->whereIn('nearestlocation.administrative_region1', $query->region_codes_list);
        }

        foreach ([
            ['elevation_min', 'station.elevation', '>='],
            ['elevation_max', 'station.elevation', '<='],
            ['latitude_min', 'station.latitude', '>='],
            ['latitude_max', 'station.latitude', '<='],
            ['longitude_min', 'station.longitude', '>='],
            ['longitude_max', 'station.longitude', '<='],
        ] as [$property, $column, $operator]) {
            if (($query->{$property} ?? null) !== null) {
                $stationQuery->where($column, $operator, $query->{$property});
            }
        }

        return $stationQuery
            ->select(
                'station.name',
                'station.latitude',
                'station.longitude',
                'station.elevation',
                DB::raw('COALESCE(geolocation.country_code, nearestlocation.country_code) as country_code'),
                'nearestlocation.administrative_region1'
            )
            ->orderBy('station.name')
            ->distinct()
            ->limit(100)
            ->get();
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
        if (($query->measurement_date_from ?? null) !== null || ($query->measurement_date_to ?? null) !== null) {
            $parts[] = 'Meetdatum: ' . (($query->measurement_date_from ?? null) ?? 'vrij') . ' t/m ' . (($query->measurement_date_to ?? null) ?? 'vrij');
        }
        if (($query->temperature_min ?? null) !== null || ($query->temperature_max ?? null) !== null) {
            $parts[] = 'Temperatuur: ' . (($query->temperature_min ?? null) ?? 'vrij') . ' t/m ' . (($query->temperature_max ?? null) ?? 'vrij');
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
            ->where('contract_id', $contractId)
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
            ->select('contract_id', DB::raw('COUNT(*) as authorized_user_count'))
            ->groupBy('contract_id');

        $contractQueries = DB::table('contract_queries')
            ->select('contract_id', DB::raw('COUNT(*) as query_count'))
            ->groupBy('contract_id');

        $query = DB::table('contracts')
            ->join('companies', 'contracts.company_id', '=', 'companies.id')
            ->leftJoin('contract_types', 'contracts.contract_type_id', '=', 'contract_types.id')
            ->leftJoinSub($authorizedUsers, 'authorized_users', function ($join) {
                $join->on('contracts.id', '=', 'authorized_users.contract_id');
            })
            ->leftJoinSub($contractQueries, 'contract_queries_summary', function ($join) {
                $join->on('contracts.id', '=', 'contract_queries_summary.contract_id');
            });

        if (Schema::hasTable('contract_endpoint_activity')) {
            $endpointActivity = DB::table('contract_endpoint_activity')
                ->select('identifier', DB::raw('SUM(CASE WHEN authorized = 1 THEN 1 ELSE 0 END) as successful_calls'))
                ->groupBy('identifier');

            $query->leftJoinSub($endpointActivity, 'contract_activity_summary', function ($join) {
                $join->on('contracts.identifier', '=', 'contract_activity_summary.identifier');
            });
        }

        return $query
            ->select(
                'contracts.id',
                'contracts.identifier',
                'contracts.start_date',
                'contracts.end_date',
                'contracts.price',
                'contracts.description',
                'contracts.app_url',
                'contracts.notes',
                'contracts.status',
                'contracts.api_token',
                'companies.name as company_name',
                DB::raw("COALESCE(contract_types.name, 'Onbekend type') as type_name"),
                DB::raw('COALESCE(authorized_users.authorized_user_count, 0) as authorized_user_count'),
                DB::raw('COALESCE(contract_queries_summary.query_count, 0) as query_count'),
                DB::raw('COALESCE(contract_activity_summary.successful_calls, 0) as successful_calls')
            )
            ->orderBy('companies.name')
            ->orderBy('contracts.identifier');
    }

    private function contractFormData(?object $contract = null): array
    {
        return [
            'contract' => $contract,
            'companies' => DB::table('companies')->orderBy('name')->get(),
            'contractTypes' => DB::table('contract_types')->orderBy('name')->get(),
            'availableStatuses' => ['Concept', 'Actief', 'Gepauzeerd', 'Beëindigd'],
            'contractUserPermissionLevels' => self::CONTRACT_USER_PERMISSION_LEVELS,
        ];
    }

    private function validateContract(Request $request, ?int $ignoreId): array
    {
        return $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'contract_type_id' => ['required', 'integer', 'exists:contract_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'price' => ['nullable', 'numeric'],
            'status' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'app_url' => ['required', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],
            'identifier' => ['required', 'string', 'max:45', 'unique:contracts,identifier,' . ($ignoreId ?? 'NULL') . ',id'],
            'api_token' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function findContract(string $identifier): ?object
    {
        return DB::table('contracts')
            ->join('companies', 'contracts.company_id', '=', 'companies.id')
            ->leftJoin('contract_types', 'contracts.contract_type_id', '=', 'contract_types.id')
            ->where('contracts.identifier', $identifier)
            ->select(
                'contracts.*',
                'companies.name as company_name',
                DB::raw("COALESCE(contract_types.name, 'Onbekend type') as type_name"),
                'contract_types.slug as contract_type_slug',
                'contract_types.description as contract_type_description'
            )
            ->first();
    }

    private function isContractActive(object $contract): bool
    {
        $today = now()->toDateString();

        if (! empty($contract->end_date) && $contract->end_date < $today) {
            return false;
        }

        return empty($contract->start_date) || $contract->start_date <= $today;
    }
}
