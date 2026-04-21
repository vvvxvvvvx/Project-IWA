{{-- Detailpagina stationsbeheer --}}
@extends('layouts.iwa')

@section('title', 'Station ' . $station->stn)
@section('eyebrow', 'Stationsbeheer')
@section('page-title', $station->stn)

@section('back-button')
    <a class="secondary-button" href="{{ route('stations.manage.index') }}">Terug naar beheer</a>
@endsection

@section('content')

@if (session('success'))
    <div style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;padding:12px 16px;border-radius:6px;margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">

    {{-- Basisgegevens --}}
    <section class="panel">
        <div class="panel-header">
            <div><h2>Basisgegevens</h2></div>
        </div>
        <table class="data-table">
            <tbody>
                <tr><td style="font-weight:600;width:45%;">Station ID</td><td>{{ $station->stn }}</td></tr>
                <tr><td style="font-weight:600;">Breedtegraad</td><td>{{ $station->latitude }}</td></tr>
                <tr><td style="font-weight:600;">Lengtegraad</td><td>{{ $station->longitude }}</td></tr>
                <tr><td style="font-weight:600;">Hoogte (m)</td><td>{{ $station->elevation }}</td></tr>
            </tbody>
        </table>
    </section>

    {{-- Locatiegegevens --}}
    <section class="panel">
        <div class="panel-header">
            <div><h2>Locatiegegevens</h2></div>
        </div>
        <table class="data-table">
            <tbody>
                <tr><td style="font-weight:600;width:45%;">Plaatsnaam</td><td>{{ $station->location_label ?? '—' }}</td></tr>
                <tr><td style="font-weight:600;">Land</td><td>{{ $station->country_name ?? '—' }} ({{ $station->country_code ?? '—' }})</td></tr>
                <tr><td style="font-weight:600;">Regio / Provincie</td><td>{{ $station->region1 ?? '—' }}</td></tr>
                <tr><td style="font-weight:600;">Subregion</td><td>{{ $station->region2 ?? '—' }}</td></tr>
            </tbody>
        </table>
    </section>

    {{-- Metingsstatistieken --}}
    <section class="panel">
        <div class="panel-header">
            <div><h2>Metingen</h2></div>
        </div>
        <table class="data-table">
            <tbody>
                <tr><td style="font-weight:600;width:45%;">Totaal metingen</td><td>{{ number_format($measurementCount) }}</td></tr>
                <tr>
                    <td style="font-weight:600;">Laatste meting</td>
                    <td>{{ $latestMeasurement?->measured_at ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight:600;">Laatste temperatuur</td>
                    <td>{{ $latestMeasurement?->temperature !== null ? $latestMeasurement->temperature . ' °C' : '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight:600;">Laatste windsnelheid</td>
                    <td>{{ $latestMeasurement?->wind_speed !== null ? $latestMeasurement->wind_speed . ' m/s' : '—' }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    {{-- Acties --}}
    <section class="panel">
        <div class="panel-header">
            <div><h2>Acties</h2></div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;padding:4px 0;">
            <a href="{{ route('stations.manage.edit', $station->stn) }}" class="primary-button" style="text-align:center;">
                ✏️ Stationsgegevens wijzigen
            </a>
            <a href="{{ route('stations.show', $station->stn) }}" class="secondary-button" style="text-align:center;">
                📊 Meetdata bekijken
            </a>
            <a href="{{ route('stations.compare') }}?stations[]={{ $station->stn }}" class="secondary-button" style="text-align:center;">
                🔀 Vergelijken met andere stations
            </a>
        </div>
    </section>

</div>

@endsection
