@extends('layouts.iwa')

@section('title', $contract->identifier)
@section('eyebrow', 'Contractdetail')
@section('page-title', $contract->identifier)
@section('page-subtitle', 'Alle contractfunctionaliteit uit de oude webapplicatie is hier samengebracht in de Laravel-huisstijl.')

@section('back-button')
    <div class="inline-form">
        <a class="secondary-button compact-button" href="{{ route('contracts.index') }}">Terug naar contracten</a>
        @if(auth()->user()?->hasTask('manage_contracts'))
            <a class="secondary-button compact-button" href="{{ route('contracts.edit', $contract->identifier) }}">Wijzigen</a>
            <form method="POST" action="{{ route('contracts.destroy', $contract->identifier) }}" onsubmit="return confirm('Weet je zeker dat je dit contract wilt verwijderen?');">
                @csrf
                @method('DELETE')
                <button class="danger-button compact-button" type="submit">Verwijderen</button>
            </form>
        @endif
    </div>
@endsection

@section('content')
<section class="panel">
    <div class="panel-header panel-header-stack"><div><h2>Contractsamenvatting</h2><p class="muted">Basisinformatie van het contract in één compact overzicht.</p></div><div class="header-badges"><span class="info-pill">{{ $contract->type_name }}</span></div></div>
    <div class="details-grid">
        <div><strong>Bedrijf</strong><div>{{ $contract->company_name }}</div></div>
        <div><strong>Type</strong><div>{{ $contract->type_name }}</div></div>
        <div><strong>Prijs</strong><div>€ {{ number_format((float) $contract->price, 2, ',', '.') }}</div></div>
        <div><strong>Looptijd</strong><div>{{ $contract->start_date }} t/m {{ $contract->end_date ?? 'Doorlopend' }}</div></div>
        <div><strong>Token</strong><div><code>{{ $contract->token ?: '-' }}</code></div></div>
        <div><strong>Notities</strong><div>{{ $contract->notes ?: '-' }}</div></div>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header"><div><h2>Gekoppelde stations</h2><p class="muted">Overgenomen van de abonnementskoppeling waarop het contract technisch draait.</p></div></div>
    <div class="table-wrapper">
        <table class="data-table compact-table">
            <thead><tr><th>Station</th><th>Locatie</th><th>Latitude</th><th>Longitude</th></tr></thead>
            <tbody>
                @forelse($stations as $station)
                <tr><td>{{ $station->stn }}</td><td>{{ $station->location_label ?: 'Onbekend' }}</td><td>{{ $station->lat }}</td><td>{{ $station->lon }}</td></tr>
                @empty
                <tr><td colspan="4" class="muted">Geen stations gekoppeld.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header"><div><h2>Geautoriseerde gebruikers</h2><p class="muted">CRUD-beheer per contract, zodat toegang volledig beheerd kan worden.</p></div></div>

    <div class="contract-role-permissions">
        <div class="contract-role-permissions-header">
            <div>
                <h3>Rollen en bevoegdheden</h3>
                <p class="muted">Hier bepaal je per contract wat een Beheerder of Gebruiker binnen dit contract mag doen.</p>
            </div>
        </div>
        @if(auth()->user()?->hasTask('manage_contract_users'))
        <form method="POST" action="{{ route('contracts.role-permissions.update', $contract->identifier) }}" class="stack-form">
            @csrf
            @method('PUT')
            <div class="table-wrapper">
                <table class="data-table compact-table permissions-table">
                    <thead>
                        <tr>
                            <th>Bevoegdheid</th>
                            <th>Beheerder</th>
                            <th>Gebruiker</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Contract bekijken</td>
                            <td><input type="checkbox" name="roles[Beheerder][can_view_contract]" value="1" {{ !empty($rolePermissions['Beheerder']['can_view_contract']) ? 'checked' : '' }}></td>
                            <td><input type="checkbox" name="roles[Gebruiker][can_view_contract]" value="1" {{ !empty($rolePermissions['Gebruiker']['can_view_contract']) ? 'checked' : '' }}></td>
                        </tr>
                        <tr>
                            <td>Queries inzien</td>
                            <td><input type="checkbox" name="roles[Beheerder][can_view_queries]" value="1" {{ !empty($rolePermissions['Beheerder']['can_view_queries']) ? 'checked' : '' }}></td>
                            <td><input type="checkbox" name="roles[Gebruiker][can_view_queries]" value="1" {{ !empty($rolePermissions['Gebruiker']['can_view_queries']) ? 'checked' : '' }}></td>
                        </tr>
                        <tr>
                            <td>Queries beheren</td>
                            <td><input type="checkbox" name="roles[Beheerder][can_manage_queries]" value="1" {{ !empty($rolePermissions['Beheerder']['can_manage_queries']) ? 'checked' : '' }}></td>
                            <td><input type="checkbox" name="roles[Gebruiker][can_manage_queries]" value="1" {{ !empty($rolePermissions['Gebruiker']['can_manage_queries']) ? 'checked' : '' }}></td>
                        </tr>
                        <tr>
                            <td>Geautoriseerde gebruikers beheren</td>
                            <td><input type="checkbox" name="roles[Beheerder][can_manage_authorized_users]" value="1" {{ !empty($rolePermissions['Beheerder']['can_manage_authorized_users']) ? 'checked' : '' }}></td>
                            <td><input type="checkbox" name="roles[Gebruiker][can_manage_authorized_users]" value="1" {{ !empty($rolePermissions['Gebruiker']['can_manage_authorized_users']) ? 'checked' : '' }}></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="inline-form" style="margin-top:16px;"><button class="secondary-button compact-button" type="submit">Bevoegdheden opslaan</button></div>
        </form>
        @else
        <p class="muted">Alleen gebruikers met beheertaken kunnen deze bevoegdheden aanpassen.</p>
        @endif
    </div>

    @if(auth()->user()?->hasTask('manage_contract_users'))
    <form method="POST" action="{{ route('contracts.authorized-users.store', $contract->identifier) }}" class="stack-form" style="margin-top:18px;">
        @csrf
        <div class="inline-manage-grid contract-user-form-grid">
            <div class="inline-manage-field">
                <label for="new-user-name">Naam</label>
                <input id="new-user-name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="inline-manage-field">
                <label for="new-user-email">E-mail</label>
                <input id="new-user-email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <div class="inline-manage-field">
                <label for="new-user-role">Rol bij contract</label>
                <select id="new-user-role" name="role_label" required>
                    @foreach($authorizedUserRoles as $authorizedUserRole)
                        <option value="{{ $authorizedUserRole }}" {{ old('role_label', 'Gebruiker') === $authorizedUserRole ? 'selected' : '' }}>{{ $authorizedUserRole }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inline-manage-field">
                <label for="new-user-status">Status</label>
                <input id="new-user-status" name="status" value="{{ old('status', 'Actief') }}">
            </div>
            <div class="inline-manage-field inline-manage-full">
                <label for="new-user-notes">Notities</label>
                <input id="new-user-notes" name="notes" value="{{ old('notes') }}">
            </div>
        </div>
        <div class="inline-form" style="margin-top:18px;"><button class="primary-button compact-button" type="submit">Gebruiker toevoegen</button></div>
    </form>
    @endif
    <div class="table-wrapper" style="margin-top:18px;">
        <table class="data-table compact-table">
            <thead><tr><th>Naam</th><th>E-mail</th><th>Rol</th><th>Status</th><th>Notities</th><th>Acties</th></tr></thead>
            <tbody>
                @forelse($authorizedUsers as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role_label ?: '—' }}</td>
                    <td>{{ $user->status }}</td>
                    <td>{{ $user->notes ?: '—' }}</td>
                    <td>
                        @if(auth()->user()?->hasTask('manage_contract_users'))
                            <details class="inline-manage-toggle">
                                <summary class="inline-manage-summary">Beheren</summary>
                            </details>
                        @else
                        —
                        @endif
                    </td>
                </tr>
                @if(auth()->user()?->hasTask('manage_contract_users'))
                <tr class="inline-manage-row">
                    <td colspan="6">
                        <details class="inline-manage-panel inline-manage-panel-full">
                            <summary class="inline-manage-summary">Beheren</summary>
                            <div class="inline-manage-body">
                                <form method="POST" action="{{ route('contracts.authorized-users.update', [$contract->identifier, $user->id]) }}" class="inline-manage-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="inline-manage-grid">
                                        <div class="inline-manage-field">
                                            <label for="authorized-user-name-{{ $user->id }}">Naam</label>
                                            <input id="authorized-user-name-{{ $user->id }}" name="name" value="{{ $user->name }}" required>
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="authorized-user-email-{{ $user->id }}">E-mail</label>
                                            <input id="authorized-user-email-{{ $user->id }}" name="email" type="email" value="{{ $user->email }}" required>
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="authorized-user-role-{{ $user->id }}">Rol bij contract</label>
                                            <select id="authorized-user-role-{{ $user->id }}" name="role_label" required>
                                                @foreach($authorizedUserRoles as $authorizedUserRole)
                                                    <option value="{{ $authorizedUserRole }}" {{ $user->role_label === $authorizedUserRole ? 'selected' : '' }}>{{ $authorizedUserRole }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="authorized-user-status-{{ $user->id }}">Status</label>
                                            <input id="authorized-user-status-{{ $user->id }}" name="status" value="{{ $user->status }}">
                                        </div>
                                        <div class="inline-manage-field inline-manage-full">
                                            <label for="authorized-user-notes-{{ $user->id }}">Notities</label>
                                            <input id="authorized-user-notes-{{ $user->id }}" name="notes" value="{{ $user->notes }}">
                                        </div>
                                    </div>
                                    <div class="inline-manage-actions">
                                        <button class="secondary-button compact-button" type="submit">Opslaan</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('contracts.authorized-users.destroy', [$contract->identifier, $user->id]) }}" onsubmit="return confirm('Gebruiker verwijderen uit dit contract?');" class="inline-manage-delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger-button compact-button" type="submit">Verwijderen</button>
                                </form>
                            </div>
                        </details>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="6" class="muted">Nog geen geautoriseerde gebruikers gekoppeld.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header"><div><h2>Queries per contract</h2><p class="muted">Leg per query de stationselectie en de aan te leveren meetvelden vast.</p></div></div>
    @php
        $countryCodeOptions = $queryConfigurationOptions['country_codes'] ?? [];
        $regionCodeOptions = $queryConfigurationOptions['region_codes'] ?? [];
        $measurementFieldOptions = $queryConfigurationOptions['measurement_fields'] ?? [];
    @endphp

    @if(! $queryColumnsAvailable)
        <p class="muted">Voer eerst de nieuwste migraties uit om querycriteria en meetvelden op te slaan.</p>
    @elseif(auth()->user()?->hasTask('manage_contract_queries'))
    <form method="POST" action="{{ route('contracts.queries.store', $contract->identifier) }}" class="stack-form contract-query-builder">
        @csrf
        <div class="contract-query-builder-intro">
            <div>
                <span class="summary-label">Contractquery</span>
                <h3>Stations selecteren en output bepalen</h3>
                <p class="muted">Leg hier per contractquery vast welke stations en meetgegevens gebruikt moeten worden.</p>
            </div>
        </div>

        <div class="contract-query-builder-grid">
            <section class="contract-query-card">
                <div class="contract-query-card-header">
                    <h4>Basisgegevens</h4>
                    <p class="muted">Naam, status en endpoint van de query.</p>
                </div>
                <div class="details-grid contract-query-grid contract-query-grid-compact">
                    <div><strong>Naam</strong><input name="name" value="{{ old('name') }}" required></div>
                    <div><strong>Status</strong><input name="status" value="{{ old('status', 'Actief') }}"></div>
                    <div><strong>Endpoint</strong><input name="endpoint" value="{{ old('endpoint', '/IWA/abonnement/' . $contract->identifier . '/stations') }}"></div>
                    <div><strong>Formaat</strong><input name="format" value="{{ old('format', 'JSON') }}"></div>
                </div>
            </section>

            <section class="contract-query-card">
                <div class="contract-query-card-header">
                    <h4>Meetvelden</h4>
                    <p class="muted">Kies welke meetgegevens via deze query worden aangeleverd.</p>
                </div>
                <div class="contract-query-field-full">
                    <select name="measurement_fields[]" multiple size="7">
                        @foreach($measurementFieldOptions as $fieldKey => $fieldLabel)
                            <option value="{{ $fieldKey }}" {{ in_array($fieldKey, old('measurement_fields', []), true) ? 'selected' : '' }}>{{ $fieldLabel }}</option>
                        @endforeach
                    </select>
                    <div class="table-subtext">De API /measurements gebruikt alleen deze geselecteerde velden voor de actieve query.</div>
                </div>
            </section>

            <section class="contract-query-card contract-query-card-wide">
                <div class="contract-query-card-header">
                    <h4>Selectiecriteria voor stations</h4>
                    <p class="muted">Must have: landcodes, elevation en coördinaten. Regiocodes blijven beschikbaar als extra filter.</p>
                </div>
                <div class="contract-query-filters-grid">
                    <div class="contract-query-filter-block">
                        <strong>Landcodes</strong>
                        <select name="country_codes[]" multiple size="7">
                            @foreach($countryCodeOptions as $countryCode)
                                <option value="{{ $countryCode }}" {{ in_array($countryCode, old('country_codes', []), true) ? 'selected' : '' }}>{{ $countryCode }}</option>
                            @endforeach
                        </select>
                        <div class="table-subtext">Beschikbare landcodes uit geolocation, nearestlocation en country.</div>
                    </div>
                    <div class="contract-query-filter-block">
                        <strong>Regiocodes</strong>
                        <select name="region_codes[]" multiple size="7">
                            @foreach($regionCodeOptions as $regionCode)
                                <option value="{{ $regionCode }}" {{ in_array($regionCode, old('region_codes', []), true) ? 'selected' : '' }}>{{ $regionCode }}</option>
                            @endforeach
                        </select>
                        <div class="table-subtext">Could have: administrative_region1 uit nearestlocation.</div>
                    </div>
                    <div class="contract-query-range-grid">
                        <div><strong>Elevation vanaf</strong><input name="elevation_min" type="number" step="0.01" value="{{ old('elevation_min') }}"></div>
                        <div><strong>Elevation t/m</strong><input name="elevation_max" type="number" step="0.01" value="{{ old('elevation_max') }}"></div>
                        <div><strong>Breedtegraad vanaf</strong><input name="latitude_min" type="number" step="0.000001" value="{{ old('latitude_min') }}"></div>
                        <div><strong>Breedtegraad t/m</strong><input name="latitude_max" type="number" step="0.000001" value="{{ old('latitude_max') }}"></div>
                        <div><strong>Lengtegraad vanaf</strong><input name="longitude_min" type="number" step="0.000001" value="{{ old('longitude_min') }}"></div>
                        <div><strong>Lengtegraad t/m</strong><input name="longitude_max" type="number" step="0.000001" value="{{ old('longitude_max') }}"></div>
                    </div>
                </div>
            </section>

            <section class="contract-query-card contract-query-card-wide">
                <div class="contract-query-card-header">
                    <h4>Notities</h4>
                    <p class="muted">Gebruik dit voor uitleg of uitzonderingen binnen het contract.</p>
                </div>
                <div class="contract-query-field-full">
                    <input name="notes" value="{{ old('notes') }}">
                </div>
            </section>
        </div>

        <div class="inline-form contract-query-actions"><button class="primary-button" type="submit">Query toevoegen</button></div>
    </form>
    @endif
    <div class="table-wrapper" style="margin-top:18px;">
        <table class="data-table compact-table">
            <thead><tr><th>Naam</th><th>Status</th><th>Meetvelden</th><th>Selectiecriteria</th><th>Endpoint</th><th>Acties</th></tr></thead>
            <tbody>
                @forelse($queries as $query)
                <tr>
                    <td>{{ $query->name }}</td>
                    <td>{{ $query->status }}</td>
                    <td>
                        @if($query->measurement_field_labels)
                            {{ implode(', ', $query->measurement_field_labels) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $query->criteria_summary }}</td>
                    <td>{{ $query->endpoint ?: '—' }}</td>
                    <td>
                        @if($queryColumnsAvailable && auth()->user()?->hasTask('manage_contract_queries'))
                        <details class="inline-manage-panel inline-manage-panel-full">
                            <summary class="inline-manage-summary">Beheren</summary>
                            <div class="inline-manage-body">
                                <form method="POST" action="{{ route('contracts.queries.update', [$contract->identifier, $query->id]) }}" class="inline-manage-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="inline-manage-grid">
                                        <div class="inline-manage-field">
                                            <label for="query-name-{{ $query->id }}">Naam</label>
                                            <input id="query-name-{{ $query->id }}" name="name" value="{{ $query->name }}" required>
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="query-status-{{ $query->id }}">Status</label>
                                            <input id="query-status-{{ $query->id }}" name="status" value="{{ $query->status }}">
                                        </div>
                                        <div class="inline-manage-field inline-manage-full">
                                            <label for="query-endpoint-{{ $query->id }}">Endpoint</label>
                                            <input id="query-endpoint-{{ $query->id }}" name="endpoint" value="{{ $query->endpoint }}">
                                        </div>
                                        <div class="inline-manage-field inline-manage-full">
                                            <label for="query-fields-{{ $query->id }}">Meetvelden</label>
                                            <select id="query-fields-{{ $query->id }}" name="measurement_fields[]" multiple size="6">
                                                @foreach($measurementFieldOptions as $fieldKey => $fieldLabel)
                                                    <option value="{{ $fieldKey }}" {{ in_array($fieldKey, $query->measurement_fields_list, true) ? 'selected' : '' }}>{{ $fieldLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="query-country-codes-{{ $query->id }}">Landcodes</label>
                                            <select id="query-country-codes-{{ $query->id }}" name="country_codes[]" multiple size="6">
                                                @foreach($countryCodeOptions as $countryCode)
                                                    <option value="{{ $countryCode }}" {{ in_array($countryCode, $query->country_codes_list, true) ? 'selected' : '' }}>{{ $countryCode }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="query-region-codes-{{ $query->id }}">Regiocodes</label>
                                            <select id="query-region-codes-{{ $query->id }}" name="region_codes[]" multiple size="6">
                                                @foreach($regionCodeOptions as $regionCode)
                                                    <option value="{{ $regionCode }}" {{ in_array($regionCode, $query->region_codes_list, true) ? 'selected' : '' }}>{{ $regionCode }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="query-elevation-min-{{ $query->id }}">Elevation vanaf</label>
                                            <input id="query-elevation-min-{{ $query->id }}" name="elevation_min" type="number" step="0.01" value="{{ property_exists($query, 'elevation_min') ? $query->elevation_min : '' }}">
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="query-elevation-max-{{ $query->id }}">Elevation t/m</label>
                                            <input id="query-elevation-max-{{ $query->id }}" name="elevation_max" type="number" step="0.01" value="{{ property_exists($query, 'elevation_max') ? $query->elevation_max : '' }}">
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="query-latitude-min-{{ $query->id }}">Breedtegraad vanaf</label>
                                            <input id="query-latitude-min-{{ $query->id }}" name="latitude_min" type="number" step="0.000001" value="{{ property_exists($query, 'latitude_min') ? $query->latitude_min : '' }}">
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="query-latitude-max-{{ $query->id }}">Breedtegraad t/m</label>
                                            <input id="query-latitude-max-{{ $query->id }}" name="latitude_max" type="number" step="0.000001" value="{{ property_exists($query, 'latitude_max') ? $query->latitude_max : '' }}">
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="query-longitude-min-{{ $query->id }}">Lengtegraad vanaf</label>
                                            <input id="query-longitude-min-{{ $query->id }}" name="longitude_min" type="number" step="0.000001" value="{{ property_exists($query, 'longitude_min') ? $query->longitude_min : '' }}">
                                        </div>
                                        <div class="inline-manage-field">
                                            <label for="query-longitude-max-{{ $query->id }}">Lengtegraad t/m</label>
                                            <input id="query-longitude-max-{{ $query->id }}" name="longitude_max" type="number" step="0.000001" value="{{ property_exists($query, 'longitude_max') ? $query->longitude_max : '' }}">
                                        </div>
                                        <div class="inline-manage-field inline-manage-full">
                                            <label for="query-notes-{{ $query->id }}">Notities</label>
                                            <input id="query-notes-{{ $query->id }}" name="notes" value="{{ $query->notes }}">
                                        </div>
                                    </div>
                                    <div class="inline-manage-actions">
                                        <button class="secondary-button compact-button" type="submit">Opslaan</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('contracts.queries.destroy', [$contract->identifier, $query->id]) }}" onsubmit="return confirm('Deze query verwijderen?');" class="inline-manage-delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger-button compact-button" type="submit">Verwijderen</button>
                                </form>
                            </div>
                        </details>
                        @else
                        —
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="muted">Nog geen queries gekoppeld aan dit contract.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header"><div><h2>REST-API activiteit</h2><p class="muted">Historie van endpointgebruik voor dit contract.</p></div></div>
    <div class="table-wrapper">
        <table class="data-table compact-table">
            <thead><tr><th>Datum</th><th>Tijd</th><th>Endpoint</th><th>Authorized</th><th>Bestanden</th><th>Data</th></tr></thead>
            <tbody>
                @forelse ($activity as $row)
                <tr>
                    <td>{{ $row->activity_date }}</td>
                    <td>{{ $row->activity_time }}</td>
                    <td>{{ $row->endpoint_used }}</td>
                    <td>{{ (int) ($row->authorized ?? 0) === 1 ? 'Ja' : 'Nee' }}</td>
                    <td>{{ $row->files_downloaded ?? 0 }}</td>
                    <td>{{ $row->data_transferred ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="muted">Geen activiteit geregistreerd.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
