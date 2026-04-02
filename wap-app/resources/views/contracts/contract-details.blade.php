@extends('layouts.iwa')

@section('title', $contract->identifier)
@section('eyebrow', 'Contractdetail')
@section('page-title', $contract->identifier)
@section('page-subtitle', 'Alle contractfunctionaliteit uit de oude webapplicatie is hier samengebracht in de Laravel-huisstijl.')

@section('back-button')
    <div class="inline-form">
        <a class="secondary-button" href="{{ route('contracts.index') }}">Terug naar contracten</a>
        @if(auth()->user()?->hasTask('manage_contracts'))
            <a class="secondary-button" href="{{ route('contracts.edit', $contract->identifier) }}">Wijzigen</a>
            <form method="POST" action="{{ route('contracts.destroy', $contract->identifier) }}" onsubmit="return confirm('Weet je zeker dat je dit contract wilt verwijderen?');">
                @csrf
                @method('DELETE')
                <button class="danger-button" type="submit">Verwijderen</button>
            </form>
        @endif
    </div>
@endsection

@section('content')
<section class="panel">
    <div class="panel-header"><h2>Contractsamenvatting</h2></div>
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
    @if(auth()->user()?->hasTask('manage_contract_users'))
    <form method="POST" action="{{ route('contracts.authorized-users.store', $contract->identifier) }}" class="stack-form">
        @csrf
        <div class="details-grid">
            <div><strong>Naam</strong><input name="name" required></div>
            <div><strong>E-mail</strong><input name="email" type="email" required></div>
            <div><strong>Rol</strong><input name="role_label"></div>
            <div><strong>Status</strong><input name="status" value="Actief"></div>
            <div style="grid-column: 1 / -1;"><strong>Notities</strong><input name="notes"></div>
        </div>
        <div class="inline-form" style="margin-top:18px;"><button class="primary-button" type="submit">Gebruiker toevoegen</button></div>
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
                        <details>
                            <summary>Beheren</summary>
                            <form method="POST" action="{{ route('contracts.authorized-users.update', [$contract->identifier, $user->id]) }}" class="stack-form" style="margin-top:12px;">
                                @csrf
                                @method('PUT')
                                <input name="name" value="{{ $user->name }}" required>
                                <input name="email" type="email" value="{{ $user->email }}" required>
                                <input name="role_label" value="{{ $user->role_label }}">
                                <input name="status" value="{{ $user->status }}">
                                <input name="notes" value="{{ $user->notes }}">
                                <button class="secondary-button" type="submit">Opslaan</button>
                            </form>
                            <form method="POST" action="{{ route('contracts.authorized-users.destroy', [$contract->identifier, $user->id]) }}" onsubmit="return confirm('Gebruiker verwijderen uit dit contract?');" style="margin-top:8px;">
                                @csrf
                                @method('DELETE')
                                <button class="danger-button" type="submit">Verwijderen</button>
                            </form>
                        </details>
                        @else
                        —
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="muted">Nog geen geautoriseerde gebruikers gekoppeld.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header"><div><h2>Queries per contract</h2><p class="muted">CRUD-beheer voor data-ontsluiting en querydefinities per contract.</p></div></div>
    @if(auth()->user()?->hasTask('manage_contract_queries'))
    <form method="POST" action="{{ route('contracts.queries.store', $contract->identifier) }}" class="stack-form">
        @csrf
        <div class="details-grid">
            <div><strong>Naam</strong><input name="name" required></div>
            <div><strong>Endpoint</strong><input name="endpoint" value="/IWA/abonnement/{{ $contract->identifier }}/stations"></div>
            <div><strong>Formaat</strong><input name="format" value="JSON"></div>
            <div><strong>Status</strong><input name="status" value="Actief"></div>
            <div style="grid-column: 1 / -1;"><strong>Query</strong><input name="query_text"></div>
            <div style="grid-column: 1 / -1;"><strong>Notities</strong><input name="notes"></div>
        </div>
        <div class="inline-form" style="margin-top:18px;"><button class="primary-button" type="submit">Query toevoegen</button></div>
    </form>
    @endif
    <div class="table-wrapper" style="margin-top:18px;">
        <table class="data-table compact-table">
            <thead><tr><th>Naam</th><th>Endpoint</th><th>Formaat</th><th>Status</th><th>Query</th><th>Acties</th></tr></thead>
            <tbody>
                @forelse($queries as $query)
                <tr>
                    <td>{{ $query->name }}</td>
                    <td>{{ $query->endpoint ?: '—' }}</td>
                    <td>{{ $query->format }}</td>
                    <td>{{ $query->status }}</td>
                    <td>{{ $query->query_text ?: '—' }}</td>
                    <td>
                        @if(auth()->user()?->hasTask('manage_contract_queries'))
                        <details>
                            <summary>Beheren</summary>
                            <form method="POST" action="{{ route('contracts.queries.update', [$contract->identifier, $query->id]) }}" class="stack-form" style="margin-top:12px;">
                                @csrf
                                @method('PUT')
                                <input name="name" value="{{ $query->name }}" required>
                                <input name="endpoint" value="{{ $query->endpoint }}">
                                <input name="format" value="{{ $query->format }}">
                                <input name="status" value="{{ $query->status }}">
                                <input name="query_text" value="{{ $query->query_text }}">
                                <input name="notes" value="{{ $query->notes }}">
                                <button class="secondary-button" type="submit">Opslaan</button>
                            </form>
                            <form method="POST" action="{{ route('contracts.queries.destroy', [$contract->identifier, $query->id]) }}" onsubmit="return confirm('Deze query verwijderen?');" style="margin-top:8px;">
                                @csrf
                                @method('DELETE')
                                <button class="danger-button" type="submit">Verwijderen</button>
                            </form>
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
