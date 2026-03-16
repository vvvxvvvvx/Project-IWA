@extends('layouts.iwa')

@section('title', 'Abonnementen')
@section('eyebrow', 'Abonnementen & klanttoegang')
@section('page-title', 'Abonnementenoverzicht')
@section('page-subtitle', 'Welke bedrijven gekoppeld zijn, welk aanbod actief is en welke stations per abonnement beschikbaar zijn.')

@section('back-button')
    <a class="secondary-button" href="{{ route('dashboard') }}">Terug naar dashboard</a>
@endsection

@section('content')

{{-- Summary cards --}}
<section class="summary-grid">
    <article class="summary-card">
        <span class="summary-label">Abonnementen totaal</span>
        <strong class="summary-value">{{ $summary['subscription_count'] }}</strong>
        <span class="summary-subtext">Volledige importset die nu zichtbaar is.</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Actief</span>
        <strong class="summary-value">{{ $summary['active_count'] }}</strong>
        <span class="summary-subtext">Zonder einddatum of einddatum in de toekomst.</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Aanbodtypes</span>
        <strong class="summary-value">{{ $summary['type_count'] }}</strong>
        <span class="summary-subtext">Beschikbare abonnementsvormen.</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Gekoppelde stations</span>
        <strong class="summary-value">{{ $summary['total_station_links'] }}</strong>
        <span class="summary-subtext">Totaal station-koppelingen over alle abonnementen.</span>
    </article>
</section>

{{-- Quick links --}}
<section class="quick-links-grid">
    <a class="quick-link-card" href="{{ route('subscription-types.index') }}">
        <strong>Bekijk aanbod</strong>
        <span>Zie alle abonnementsvormen met prijs, frequentie en aangesloten klanten.</span>
    </a>
    <a class="quick-link-card" href="{{ route('contracts.index') }}">
        <strong>Contracten</strong>
        <span>Open de contractlaag voor REST-API gebruik, looptijd en endpoint-activiteit.</span>
    </a>
    <a class="quick-link-card" href="{{ route('companies.index') }}">
        <strong>Bedrijven</strong>
        <span>Bekijk alle bedrijven en contactpersonen.</span>
    </a>
</section>

{{-- Abonnementen tabel --}}
<article class="panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Alle abonnementen</h2>
            <p class="muted">Per klant welk abonnement actief is, wat de looptijd is, hoeveel stations gekoppeld zijn en welk token bij de REST-laag hoort.</p>
        </div>
        <div class="header-badges">
            <span class="info-pill">Omzetindicatie: &euro; {{ number_format($summary['total_revenue'], 2, ',', '.') }}</span>
            <span class="info-pill muted-pill">Data uit import + runtime koppelingen</span>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table data-table-comfortable">
            <thead>
                <tr>
                    <th>Identifier</th>
                    <th>Bedrijf</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Looptijd</th>
                    <th>Prijs</th>
                    <th>Stations</th>
                    <th>Token</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subscriptions as $sub)
                @php $isActive = empty($sub->end_date) || $sub->end_date >= now()->format('Y-m-d'); @endphp
                <tr>
                    <td>
                        <a href="{{ route('subscriptions.show', $sub->identifier) }}">{{ $sub->identifier }}</a>
                        <div class="table-subtext">{{ $sub->notes ?? 'Geen extra notitie' }}</div>
                    </td>
                    <td>
                        {{ $sub->company_name }}
                        <div class="table-subtext">{{ $sub->company_city ?? '-' }}</div>
                    </td>
                    <td>
                        <a href="{{ route('subscription-types.index') }}">{{ $sub->type_name }}</a>
                        <div class="table-subtext">
                            @if ($sub->continuous)
                                Continu
                            @elseif ($sub->frequency_in_hours)
                                Elke {{ $sub->frequency_in_hours }} uur
                            @else
                                Elke {{ $sub->frequency_in_days }} dag(en)
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="status-badge {{ $isActive ? 'success' : 'warning' }}">
                            {{ $isActive ? 'Actief' : 'Verlopen' }}
                        </span>
                    </td>
                    <td>
                        {{ $sub->start_date }}
                        <div class="table-subtext">t/m {{ $sub->end_date ?? 'Doorlopend' }}</div>
                    </td>
                    <td>&euro; {{ number_format($sub->price, 2, ',', '.') }}</td>
                    <td><span class="table-emphasis">{{ $sub->station_count }}</span></td>
                    <td><code>{{ substr($sub->token ?? '', 0, 8) }}&hellip;</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</article>

{{-- Aanbod overzicht --}}
<article class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div>
            <h2>Aanbod in het kort</h2>
            <p class="muted">Compact overzicht van het aanbod. Klik op een kaart om de detailpagina te openen.</p>
        </div>
    </div>
    <div class="story-grid offer-grid">
        @foreach ($types as $type)
        <a class="story-card offer-card" href="{{ route('subscription-types.index') }}">
            <div class="story-meta-row">
                <span class="story-chip">&euro; {{ number_format($type->price_per_station, 2, ',', '.') }}</span>
                <span class="story-chip neutral">
                    @if ($type->continuous)
                        Continu
                    @elseif ($type->frequency_in_hours)
                        Per {{ $type->frequency_in_hours }} uur
                    @else
                        Per {{ $type->frequency_in_days }} dag(en)
                    @endif
                </span>
            </div>
            <h3>{{ $type->name }}</h3>
            <p class="muted">{{ $type->description }}</p>
        </a>
        @endforeach
    </div>
</article>

@endsection
