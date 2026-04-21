@extends('layouts.iwa')

@section('title', $contract->identifier)
@section('eyebrow', 'Contractdetail')
@section('page-title', $contract->identifier)
@section('page-subtitle', 'Zelfstandig contractrecord met eigen contractgebruikers, querycriteria, meetdatafilters en contract-endpoints.')


@section('back-button')
    <div class="inline-form">
        <a class="secondary-button compact-button" href="{{ route('contracts.index') }}">Terug naar contracten</a>
        @if(auth()->user()?->hasTask('manage_contracts'))
            <a class="secondary-button compact-button" href="{{ route('contracts.edit', $contract->identifier) }}">Bewerken</a>
            <form method="POST" action="{{ route('contracts.destroy', $contract->identifier) }}" onsubmit="return confirm('Weet je zeker dat je dit contract wilt verwijderen?');">
                @csrf
                @method('DELETE')
                <button class="danger-button compact-button" type="submit">Verwijderen</button>
            </form>
        @endif
    </div>
@endsection

@section('content')
<section class="summary-grid contract-summary-grid">
    <article class="summary-card"><span class="summary-label">Bedrijf</span><strong class="summary-value summary-value-text">{{ $contract->company_name }}</strong></article>
    <article class="summary-card"><span class="summary-label">Soort</span><strong class="summary-value summary-value-text">{{ $contract->type_name }}</strong></article>
    <article class="summary-card"><span class="summary-label">Status</span><strong class="summary-value summary-value-text">{{ $contract->status ?: 'Concept' }}</strong></article>
    <article class="summary-card"><span class="summary-label">Contractgebruikers</span><strong class="summary-value">{{ count($authorizedUsers) }}</strong></article>
</section>

<section class="panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Contractgegevens</h2>
            <p class="muted">Periode 3: eigen identifier, omschrijving, bedrijf, looptijd en app-URL. Contracten zijn hier losgekoppeld van abonnementen.</p>
        </div>
    </div>
    <div class="details-grid-2">
        <div><strong>Identifier</strong><div class="inline-code-block">{{ $contract->identifier }}</div></div>
        <div><strong>Contractsoort</strong><div class="inline-code-block">{{ $contract->type_name }}</div></div>
        <div style="grid-column:1 / -1;"><strong>Omschrijving</strong><div class="inline-code-block">{{ $contract->description ?: 'Geen omschrijving vastgelegd.' }}</div></div>
        <div><strong>Startdatum</strong><div class="inline-code-block">{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}</div></div>
        <div><strong>Einddatum</strong><div class="inline-code-block">{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d-m-Y') : 'Doorlopend' }}</div></div>
        <div><strong>App-URL</strong><div class="inline-code-block">{{ $contract->app_url ?: 'Niet ingesteld' }}</div></div>
        <div><strong>Prijs</strong><div class="inline-code-block">€ {{ number_format((float) $contract->price, 2, ',', '.') }}</div></div>
        <div><strong>Token</strong><div class="inline-code-block">{{ $contract->api_token ?: 'Nog geen token ingesteld.' }}</div></div>
        <div style="grid-column:1 / -1;"><strong>Notities</strong><div class="inline-code-block contract-note-box">{{ $contract->notes ?: 'Geen extra notities.' }}</div></div>
    </div>
</section>


<section class="panel" style="margin-top:18px;">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Contactpersonen</h2>
            <p class="muted">Het contract hangt aan een bedrijf met één of meer contactpersonen uit de bedrijfsadministratie.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table compact-table">
            <thead><tr><th>Naam</th><th>E-mail</th><th>Telefoon</th><th>Functie</th></tr></thead>
            <tbody>
                @forelse($contacts as $contact)
                <tr><td>{{ $contact->name }}</td><td>{{ $contact->email ?: '—' }}</td><td>{{ $contact->phone ?: '—' }}</td><td>{{ $contact->function ?: ($contact->title ?: '—') }}</td></tr>
                @empty
                <tr><td colspan="4" class="muted">Nog geen contactpersonen gekoppeld aan dit bedrijf.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Contract-endpoints</h2>
            <p class="muted">Gebruik deze endpoints om login, querydata, stations, stationdetail, users en logout te testen. Het stations-endpoint gebruikt standaard de eerste actieve query van dit contract.</p>
        </div>
    </div>
    <div class="details-grid-2">
        <div><strong>Login</strong><div class="inline-code-block">{{ $apiEndpoints['login'] }}</div></div>
        <div><strong>Query data</strong><div class="inline-code-block">{{ $apiEndpoints['query_data'] }}</div></div>
        <div><strong>Stations</strong><div class="inline-code-block">{{ $apiEndpoints['stations'] }}</div></div>
        <div><strong>Stationdetail</strong><div class="inline-code-block">{{ $apiEndpoints['station'] }}</div></div>
        <div><strong>Gebruikers</strong><div class="inline-code-block">{{ $apiEndpoints['users'] }}</div></div>
        <div><strong>Logout</strong><div class="inline-code-block">{{ $apiEndpoints['logout'] }}</div></div>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Contractgebruikers</h2>
            <p class="muted">Eigen contract-user tabel met login-id, machtiging admin/user, e-mail, status en contractkoppeling.</p>
        </div>
    </div>

    @if(auth()->user()?->hasTask('manage_contract_users'))
    <form method="POST" action="{{ route('contracts.authorized-users.store', $contract->identifier) }}" class="inline-manage-form" style="margin-bottom:18px;">
        @csrf
        <div class="inline-manage-grid contract-user-form-grid">
            <div class="inline-manage-field"><label>Naam</label><input name="name" value="{{ old('name') }}"></div>
            <div class="inline-manage-field"><label>E-mail</label><input name="email" type="email" value="{{ old('email') }}"></div>
            <div class="inline-manage-field"><label>Login-id</label><input name="user_identifier" value="{{ old('user_identifier') }}"></div>
            <div class="inline-manage-field"><label>Wachtwoord</label><input name="password" type="password"></div>
            <div class="inline-manage-field"><label>Machtiging</label><select name="permission_level">@foreach(['admin' => 'Admin', 'user' => 'User'] as $value => $label)<option value="{{ $value }}" {{ old('permission_level', 'user') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="inline-manage-field"><label>Contractrol</label><select name="role_label">@foreach($authorizedUserRoles as $role)<option value="{{ $role }}" {{ old('role_label') === $role ? 'selected' : '' }}>{{ $role }}</option>@endforeach</select></div>
            <div class="inline-manage-field"><label>Status</label><input name="status" value="{{ old('status', 'Actief') }}"></div>
            <div class="inline-manage-field inline-manage-full"><label>Notities</label><textarea name="notes">{{ old('notes') }}</textarea></div>
        </div>
        <div class="inline-manage-actions"><button class="secondary-button compact-button" type="submit">Gebruiker toevoegen</button></div>
    </form>
    @endif

    <div class="table-wrapper">
        <table class="data-table compact-table">
            <thead><tr><th>Naam</th><th>Login-id</th><th>E-mail</th><th>Machtiging</th><th>Contractrol</th><th>Status</th><th>Acties</th></tr></thead>
            <tbody>
                @forelse($authorizedUsers as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->user_identifier ?: '—' }}</td>
                    <td class="cell-break">{{ $user->email }}</td>
                    <td>{{ strtoupper($user->permission_level ?: 'user') }}</td>
                    <td>{{ $user->role_label }}</td>
                    <td>{{ $user->status ?: '—' }}</td>
                    <td class="cell-actions">
                        @if(auth()->user()?->hasTask('manage_contract_users'))
                            <button
                                type="button"
                                class="secondary-button compact-button inline-manage-button"
                                onclick="const row=this.closest('tr').nextElementSibling; if(row){ row.classList.toggle('is-open'); this.setAttribute('aria-expanded', row.classList.contains('is-open') ? 'true' : 'false'); }"
                                aria-expanded="false"
                            >Beheren</button>
                        @else
                            <span class="muted">Geen beheerrechten</span>
                        @endif
                    </td>
                </tr>
                @if(auth()->user()?->hasTask('manage_contract_users'))
                <tr class="inline-manage-row-full">
                    <td colspan="7">
                        <div class="inline-manage-panel inline-manage-panel-full-row">
                            <div class="inline-manage-body">
                                <form method="POST" action="{{ route('contracts.authorized-users.update', [$contract->identifier, $user->id]) }}" class="inline-manage-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="inline-manage-grid inline-manage-grid-wide">
                                        <div class="inline-manage-field"><label>Naam</label><input name="name" value="{{ $user->name }}"></div>
                                        <div class="inline-manage-field"><label>E-mail</label><input name="email" type="email" value="{{ $user->email }}"></div>
                                        <div class="inline-manage-field"><label>Login-id</label><input name="user_identifier" value="{{ $user->user_identifier }}"></div>
                                        <div class="inline-manage-field"><label>Nieuw wachtwoord</label><input name="password" type="password"></div>
                                        <div class="inline-manage-field"><label>Machtiging</label><select name="permission_level">@foreach(['admin' => 'Admin', 'user' => 'User'] as $value => $label)<option value="{{ $value }}" {{ ($user->permission_level ?: 'user') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                                        <div class="inline-manage-field"><label>Contractrol</label><select name="role_label">@foreach($authorizedUserRoles as $role)<option value="{{ $role }}" {{ $user->role_label === $role ? 'selected' : '' }}>{{ $role }}</option>@endforeach</select></div>
                                        <div class="inline-manage-field"><label>Status</label><input name="status" value="{{ $user->status }}"></div>
                                        <div class="inline-manage-field inline-manage-full"><label>Notities</label><textarea name="notes">{{ $user->notes }}</textarea></div>
                                    </div>
                                    <div class="inline-manage-actions"><button class="primary-button compact-button" type="submit">Opslaan</button></div>
                                </form>
                                <form method="POST" action="{{ route('contracts.authorized-users.destroy', [$contract->identifier, $user->id]) }}" class="inline-manage-delete-form" onsubmit="return confirm('Gebruiker verwijderen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger-button compact-button" type="submit">Verwijderen</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="7" class="muted">Er zijn nog geen contractgebruikers vastgelegd.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Queries</h2>
            <p class="muted">Per query leg je stationselectie en meetdatafilters vast: landcodes, regio, elevation, coördinaten, datumrange, temperatuur en gekozen meetvelden. Na opslaan kun je de query testen via de contract-endpoints hierboven.</p>
        </div>
    </div>

    @if(auth()->user()?->hasTask('manage_contract_queries'))
    <form method="POST" action="{{ route('contracts.queries.store', $contract->identifier) }}" class="contract-query-builder contract-query-builder-grid">
        @csrf
        <div class="contract-query-card">
            <div class="inline-manage-grid contract-query-grid-compact">
                <div class="inline-manage-field"><label>Naam</label><input name="name" value="{{ old('name') }}"></div>
                <div class="inline-manage-field"><label>Endpoint</label><input name="endpoint" value="{{ old('endpoint') }}" placeholder="/IWA/contracten/{{ $contract->identifier }}/{queryID}"></div>
                <div class="inline-manage-field"><label>Formaat</label><input name="format" value="{{ old('format', 'JSON') }}"></div>
                <div class="inline-manage-field"><label>Status</label><input name="status" value="{{ old('status', 'Actief') }}"></div>
            </div>
        </div>
        <div class="contract-query-card">
            <div class="inline-manage-field contract-query-field-full">
                <label>Meetvelden</label>
                <select name="measurement_fields[]" multiple>
                    @foreach($queryConfigurationOptions['measurement_fields'] as $fieldKey => $fieldLabel)
                        <option value="{{ $fieldKey }}" {{ collect(old('measurement_fields', []))->contains($fieldKey) ? 'selected' : '' }}>{{ $fieldLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="contract-query-card contract-query-card-wide">
            <div class="contract-query-filters-grid">
                <div class="contract-query-filter-block inline-manage-field">
                    <label>Landcodes</label>
                    <select name="country_codes[]" multiple>
                        @foreach($queryConfigurationOptions['country_codes'] as $countryCode)
                            <option value="{{ $countryCode }}" {{ collect(old('country_codes', []))->contains($countryCode) ? 'selected' : '' }}>{{ $countryCode }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="contract-query-filter-block inline-manage-field">
                    <label>Regiocodes</label>
                    <select name="region_codes[]" multiple>
                        @foreach($queryConfigurationOptions['region_codes'] as $regionCode)
                            <option value="{{ $regionCode }}" {{ collect(old('region_codes', []))->contains($regionCode) ? 'selected' : '' }}>{{ $regionCode }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="contract-query-range-grid" style="margin-top:14px;">
                <div class="inline-manage-field"><label>Elevation min</label><input type="number" step="0.01" name="elevation_min" value="{{ old('elevation_min') }}"></div>
                <div class="inline-manage-field"><label>Elevation max</label><input type="number" step="0.01" name="elevation_max" value="{{ old('elevation_max') }}"></div>
                <div class="inline-manage-field"><label>Breedtegraad min</label><input type="number" step="0.000001" name="latitude_min" value="{{ old('latitude_min') }}"></div>
                <div class="inline-manage-field"><label>Breedtegraad max</label><input type="number" step="0.000001" name="latitude_max" value="{{ old('latitude_max') }}"></div>
                <div class="inline-manage-field"><label>Lengtegraad min</label><input type="number" step="0.000001" name="longitude_min" value="{{ old('longitude_min') }}"></div>
                <div class="inline-manage-field"><label>Lengtegraad max</label><input type="number" step="0.000001" name="longitude_max" value="{{ old('longitude_max') }}"></div>
                <div class="inline-manage-field"><label>Meetdatum vanaf</label><input type="date" name="measurement_date_from" value="{{ old('measurement_date_from') }}"></div>
                <div class="inline-manage-field"><label>Meetdatum t/m</label><input type="date" name="measurement_date_to" value="{{ old('measurement_date_to') }}"></div>
                <div class="inline-manage-field"><label>Temperatuur min</label><input type="number" step="0.01" name="temperature_min" value="{{ old('temperature_min') }}"></div>
                <div class="inline-manage-field"><label>Temperatuur max</label><input type="number" step="0.01" name="temperature_max" value="{{ old('temperature_max') }}"></div>
            </div>
        </div>
        <div class="contract-query-card contract-query-card-wide">
            <div class="inline-manage-field contract-query-field-full"><label>Notities</label><textarea name="notes">{{ old('notes') }}</textarea></div>
        </div>
        <div class="contract-query-actions"><button class="secondary-button compact-button" type="submit">Query toevoegen</button></div>
    </form>
    @endif

    <div class="table-wrapper" style="margin-top:18px;">
        <table class="data-table compact-table">
            <thead><tr><th>Query ID</th><th>Contract ID</th><th>Naam</th><th>Status</th><th>Meetvelden</th><th>Selectiecriteria</th><th>Endpoint</th><th>Acties</th></tr></thead>
            <tbody>
                @forelse($queries as $query)
                <tr>
                    <td>{{ $query->id }}</td>
                    <td>{{ $contract->id }}</td>
                    <td>{{ $query->name }}</td>
                    <td>{{ $query->status ?: '—' }}</td>
                    <td class="cell-break">{{ $query->measurement_field_labels ? implode(', ', $query->measurement_field_labels) : '—' }}</td>
                    <td class="cell-break">{{ $query->criteria_summary }}</td>
                    <td><code class="code-wrap">{{ $query->endpoint ?: '—' }}</code></td>
                    <td class="cell-actions">
                        @if(auth()->user()?->hasTask('manage_contract_queries'))
                        <button
                            type="button"
                            class="secondary-button compact-button inline-manage-button"
                            onclick="const row=this.closest('tr').nextElementSibling; if(row){ row.classList.toggle('is-open'); this.setAttribute('aria-expanded', row.classList.contains('is-open') ? 'true' : 'false'); }"
                            aria-expanded="false"
                        >Beheren</button>
                        @else
                        <span class="muted">Geen beheerrechten</span>
                        @endif
                    </td>
                </tr>
                @if(auth()->user()?->hasTask('manage_contract_queries'))
                <tr class="inline-manage-row-full">
                    <td colspan="8">
                        <div class="inline-manage-panel inline-manage-panel-full-row">
                            <div class="inline-manage-body">
                                <form method="POST" action="{{ route('contracts.queries.update', [$contract->identifier, $query->id]) }}" class="contract-query-builder contract-query-builder-grid">
                                    @csrf
                                    @method('PUT')
                                    <div class="contract-query-card">
                                        <div class="inline-manage-grid contract-query-grid-compact">
                                            <div class="inline-manage-field"><label>Naam</label><input name="name" value="{{ $query->name }}"></div>
                                            <div class="inline-manage-field"><label>Endpoint</label><input name="endpoint" value="{{ $query->endpoint }}"></div>
                                            <div class="inline-manage-field"><label>Formaat</label><input name="format" value="{{ $query->format }}"></div>
                                            <div class="inline-manage-field"><label>Status</label><input name="status" value="{{ $query->status }}"></div>
                                        </div>
                                    </div>
                                    <div class="contract-query-card">
                                        <div class="inline-manage-field contract-query-field-full">
                                            <label>Meetvelden</label>
                                            <select name="measurement_fields[]" multiple>
                                                @foreach($queryConfigurationOptions['measurement_fields'] as $fieldKey => $fieldLabel)
                                                    <option value="{{ $fieldKey }}" {{ in_array($fieldKey, $query->measurement_fields_list, true) ? 'selected' : '' }}>{{ $fieldLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="contract-query-card contract-query-card-wide">
                                        <div class="contract-query-filters-grid">
                                            <div class="contract-query-filter-block inline-manage-field">
                                                <label>Landcodes</label>
                                                <select name="country_codes[]" multiple>
                                                    @foreach($queryConfigurationOptions['country_codes'] as $countryCode)
                                                        <option value="{{ $countryCode }}" {{ in_array($countryCode, $query->country_codes_list, true) ? 'selected' : '' }}>{{ $countryCode }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="contract-query-filter-block inline-manage-field">
                                                <label>Regiocodes</label>
                                                <select name="region_codes[]" multiple>
                                                    @foreach($queryConfigurationOptions['region_codes'] as $regionCode)
                                                        <option value="{{ $regionCode }}" {{ in_array($regionCode, $query->region_codes_list, true) ? 'selected' : '' }}>{{ $regionCode }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="contract-query-range-grid" style="margin-top:14px;">
                                            <div class="inline-manage-field"><label>Elevation min</label><input type="number" step="0.01" name="elevation_min" value="{{ $query->elevation_min }}"></div>
                                            <div class="inline-manage-field"><label>Elevation max</label><input type="number" step="0.01" name="elevation_max" value="{{ $query->elevation_max }}"></div>
                                            <div class="inline-manage-field"><label>Breedtegraad min</label><input type="number" step="0.000001" name="latitude_min" value="{{ $query->latitude_min }}"></div>
                                            <div class="inline-manage-field"><label>Breedtegraad max</label><input type="number" step="0.000001" name="latitude_max" value="{{ $query->latitude_max }}"></div>
                                            <div class="inline-manage-field"><label>Lengtegraad min</label><input type="number" step="0.000001" name="longitude_min" value="{{ $query->longitude_min }}"></div>
                                            <div class="inline-manage-field"><label>Lengtegraad max</label><input type="number" step="0.000001" name="longitude_max" value="{{ $query->longitude_max }}"></div>
                                            <div class="inline-manage-field"><label>Meetdatum vanaf</label><input type="date" name="measurement_date_from" value="{{ $query->measurement_date_from }}"></div>
                                            <div class="inline-manage-field"><label>Meetdatum t/m</label><input type="date" name="measurement_date_to" value="{{ $query->measurement_date_to }}"></div>
                                            <div class="inline-manage-field"><label>Temperatuur min</label><input type="number" step="0.01" name="temperature_min" value="{{ $query->temperature_min }}"></div>
                                            <div class="inline-manage-field"><label>Temperatuur max</label><input type="number" step="0.01" name="temperature_max" value="{{ $query->temperature_max }}"></div>
                                        </div>
                                    </div>
                                    <div class="contract-query-card contract-query-card-wide">
                                        <div class="inline-manage-field contract-query-field-full"><label>Notities</label><textarea name="notes">{{ $query->notes }}</textarea></div>
                                    </div>
                                    <div class="contract-query-card contract-query-card-wide">
                                        <div class="contract-query-card-header"><h4>Resultaatstations onder deze query</h4><p class="muted">Preview van de weerstations die door de huidige querycriteria worden geselecteerd.</p></div>
                                        <div class="table-wrapper">
                                            <table class="data-table compact-table">
                                                <thead><tr><th>Contract ID</th><th>Query ID</th><th>Weerstation</th><th>Landcode</th><th>Latitude</th><th>Longitude</th><th>Elevation</th><th>Regio</th></tr></thead>
                                                <tbody>
                                                    @forelse($query->preview_stations as $station)
                                                    <tr>
                                                        <td>{{ $contract->id }}</td>
                                                        <td>{{ $query->id }}</td>
                                                        <td>{{ $station->name }}</td>
                                                        <td>{{ $station->country_code ?: '—' }}</td>
                                                        <td>{{ $station->latitude ?? '—' }}</td>
                                                        <td>{{ $station->longitude ?? '—' }}</td>
                                                        <td>{{ $station->elevation ?? '—' }}</td>
                                                        <td>{{ $station->administrative_region1 ?: '—' }}</td>
                                                    </tr>
                                                    @empty
                                                    <tr><td colspan="8" class="muted">Er zijn geen weerstations gevonden voor deze query.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="inline-manage-actions"><button class="primary-button compact-button" type="submit">Opslaan</button></div>
                                </form>
                                <form method="POST" action="{{ route('contracts.queries.destroy', [$contract->identifier, $query->id]) }}" class="inline-manage-delete-form" onsubmit="return confirm('Query verwijderen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger-button compact-button" type="submit">Verwijderen</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="6" class="muted">Er zijn nog geen queries vastgelegd voor dit contract.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
