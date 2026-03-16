@extends('layouts.iwa')

@section('title', $subscription->identifier)
@section('eyebrow', 'Abonnement detail')
@section('page-title', $subscription->identifier)
@section('page-subtitle', 'Op welk aanbod dit abonnement is gebaseerd, wie geabonneerd is, welke stations beschikbaar zijn en hoe de toegang werkt.')

@section('back-button')
    <a class="secondary-button" href="{{ route('subscriptions.index') }}">Terug naar abonnementen</a>
@endsection

@section('content')

@php $isActive = empty($subscription->end_date) || $subscription->end_date >= now()->format('Y-m-d'); @endphp

{{-- Summary --}}
<section class="summary-grid">
    <article class="summary-card">
        <span class="summary-label">Bedrijf</span>
        <strong class="summary-value summary-value-text">{{ $subscription->company_name }}</strong>
        <span class="summary-subtext">{{ $subscription->company_city ?? '-' }}</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Status</span>
        <strong class="summary-value">{{ $isActive ? 'Actief' : 'Verlopen' }}</strong>
        <span class="summary-subtext">Loopt tot {{ $subscription->end_date ?? 'doorlopend' }}</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Prijs</span>
        <strong class="summary-value">&euro; {{ number_format($subscription->price, 2, ',', '.') }}</strong>
        <span class="summary-subtext">Type: {{ $subscription->type_name }}</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Stations</span>
        <strong class="summary-value">{{ $stations->count() }}</strong>
        <span class="summary-subtext">Beschikbaar via dashboard en REST-API.</span>
    </article>
</section>

{{-- Abonnementsoverzicht --}}
<section class="panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Abonnementsoverzicht</h2>
            <p class="muted">Basisgegevens, commerciële notities en tokeninformatie voor dit abonnement.</p>
        </div>
        <span class="status-badge {{ $isActive ? 'success' : 'warning' }}">{{ $isActive ? 'Actief' : 'Verlopen' }}</span>
    </div>
    <div class="details-grid details-grid-2">
        <div><strong>Bedrijf</strong><div>{{ $subscription->company_name }}</div></div>
        <div><strong>Abonnementtype</strong><div>{{ $subscription->type_name }}</div></div>
        <div><strong>Prijs</strong><div>&euro; {{ number_format($subscription->price, 2, ',', '.') }}</div></div>
        <div><strong>Periode</strong><div>{{ $subscription->start_date }} t/m {{ $subscription->end_date ?? 'Doorlopend' }}</div></div>
        <div class="span-2"><strong>Beschrijving type</strong><div>{{ $subscription->type_description ?? '-' }}</div></div>
        <div class="span-2"><strong>Token</strong><div><code class="inline-code-block">{{ $subscription->token }}</code></div></div>
        <div class="span-2"><strong>Notities</strong><div>{{ $subscription->notes ?? 'Geen notities vastgelegd.' }}</div></div>
    </div>
</section>

{{-- Contract & REST-API --}}
<section class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div>
            <h2>Contract &amp; REST-API</h2>
            <p class="muted">Voor contractinformatie en REST-API bronverkeer is er ook een aparte contractpagina.</p>
        </div>
    </div>
    <div class="inline-form" style="margin-bottom:18px;">
        <a class="primary-button" href="{{ route('contracts.show', $subscription->identifier) }}">Open contractpagina</a>
    </div>
    <div class="api-endpoint-list">
        <div><code>GET /IWA/abonnement/{{ $subscription->identifier }}/stations?token={{ $subscription->token }}</code></div>
        <div><code>GET /IWA/abonnement/{{ $subscription->identifier }}/station/{naam}?token={{ $subscription->token }}</code></div>
        <div><code>GET /IWA/abonnement/{{ $subscription->identifier }}/files?token={{ $subscription->token }}</code></div>
    </div>
</section>

{{-- Beschikbare stations --}}
<section class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div>
            <h2>Beschikbare stations</h2>
            <p class="muted">Stations die binnen dit abonnement ontsloten mogen worden.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table compact-table data-table-comfortable">
            <thead>
                <tr>
                    <th>STN</th>
                    <th>Locatie</th>
                    <th>Breedtegraad</th>
                    <th>Lengtegraad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stations as $station)
                <tr>
                    <td><a href="{{ route('stations.show', $station->stn) }}">{{ $station->stn }}</a></td>
                    <td>{{ $station->location_label ?? 'Onbekend' }}</td>
                    <td>{{ $station->lat ?? '-' }}</td>
                    <td>{{ $station->lon ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

{{-- Endpoint-activiteit --}}
<section class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div>
            <h2>Endpoint-activiteit</h2>
            <p class="muted">Laatste geregistreerde API-aanroepen voor dit abonnement.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table compact-table data-table-comfortable">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Tijd</th>
                    <th>Endpoint</th>
                    <th>Authorized</th>
                    <th>Bestanden</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activity as $row)
                <tr>
                    <td>{{ $row->activity_date }}</td>
                    <td>{{ $row->activity_time }}</td>
                    <td>{{ $row->endpoint_used }}</td>
                    <td>{{ (int)($row->authorized ?? 0) === 1 ? 'Ja' : 'Nee' }}</td>
                    <td>{{ $row->files_downloaded ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="muted">Geen activiteit geregistreerd.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
