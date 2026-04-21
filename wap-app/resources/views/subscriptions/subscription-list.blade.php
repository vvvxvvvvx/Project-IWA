{{-- Overzichtspagina voor alle abonnementen en gekoppelde type-samenvatting. --}}
{{--
    Overzichtspagina voor abonnementen en abonnementtypes.

    Als je route-namen aanpast in routes/web.php, pas dan deze knoppen ook aan.
--}}
@extends('layouts.iwa')

@section('title', 'Abonnementen')
@section('eyebrow', 'Abonnementen & klanttoegang')
@section('page-title', 'Abonnementenoverzicht')


@section('content')
<section class="summary-grid">
    <article class="summary-card"><span class="summary-label">Abonnementen totaal</span><strong class="summary-value">{{ $summary['subscription_count'] }}</strong><span class="summary-subtext">Volledige importset die nu zichtbaar is.</span></article>
    <article class="summary-card"><span class="summary-label">Actief</span><strong class="summary-value">{{ $summary['active_count'] }}</strong><span class="summary-subtext">Zonder einddatum of einddatum in de toekomst.</span></article>
    <article class="summary-card"><span class="summary-label">Aanbodtypes</span><strong class="summary-value">{{ $summary['type_count'] }}</strong><span class="summary-subtext">Beschikbare abonnementsvormen.</span></article>
    <article class="summary-card"><span class="summary-label">Gekoppelde stations</span><strong class="summary-value">{{ $summary['total_station_links'] }}</strong><span class="summary-subtext">Totaal station-koppelingen over alle abonnementen.</span></article>
</section>

<article class="panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Alle abonnementen</h2>
        </div>
        <div class="inline-form">
            <span class="info-pill">Omzetindicatie: &euro; {{ number_format($summary['total_revenue'], 2, ',', '.') }}</span>
            <a class="secondary-button compact-button" href="{{ route('subscriptions.create') }}">Abonnement toevoegen</a>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table data-table-comfortable">
            <thead>
                <tr><th>Identifier</th><th>Bedrijf</th><th>Type</th><th>Status</th><th>Looptijd</th><th>Prijs</th><th>Stations</th><th>Token</th><th>Acties</th></tr>
            </thead>
            <tbody>
                @foreach ($subscriptions as $sub)
                @php $isActive = empty($sub->end_date) || $sub->end_date >= now()->format('Y-m-d'); @endphp
                <tr>
                    <td><a href="{{ route('subscriptions.show', $sub->identifier) }}">{{ $sub->identifier }}</a><div class="table-subtext">{{ $sub->notes ?? 'Geen extra notitie' }}</div></td>
                    <td>{{ $sub->company_name }}<div class="table-subtext">{{ $sub->company_city ?? '-' }}</div></td>
                    <td>{{ $sub->type_name }}</td>
                    <td><span class="status-badge {{ $isActive ? 'success' : 'warning' }}">{{ $isActive ? 'Actief' : 'Verlopen' }}</span></td>
                    <td>{{ $sub->start_date }}<div class="table-subtext">{{ $sub->end_date ?? 'Doorlopend' }}</div></td>
                    <td>&euro; {{ number_format($sub->price, 2, ',', '.') }}</td>
                    <td>{{ $sub->station_count }}</td>
                    <td><code>{{ $sub->token }}</code></td>
                    <td><a class="secondary-button compact-button" href="{{ route('subscriptions.edit', $sub->identifier) }}">Bewerken</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $subscriptions->links() }}
</article>

<article class="panel" style="margin-top:18px;">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Abonnementtypes</h2>
        </div>
        <a class="secondary-button compact-button" href="{{ route('subscription-types.create') }}">Type toevoegen</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>Naam</th><th>Beschrijving</th><th>Prijs per station</th><th>Klantenaantal</th><th>Acties</th></tr>
            </thead>
            <tbody>
                @foreach($types as $type)
                    <tr>
                        <td>{{ $type->name }}</td>
                        <td>{{ $type->description ?? '-' }}</td>
                        <td>&euro; {{ number_format($type->price_per_station, 2, ',', '.') }}</td>
                        <td>{{ $type->subscriber_count }}</td>
                        <td><a class="secondary-button compact-button" href="{{ route('subscription-types.edit', $type->id) }}">Bewerken</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</article>
@endsection
