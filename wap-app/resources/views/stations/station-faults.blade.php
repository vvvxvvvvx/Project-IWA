{{-- Overzichtspagina voor stations met actieve storingen. --}}
@extends('layouts.iwa')

@section('title', 'Actieve storingen')
@section('eyebrow', 'Analyse & monitoring')
@section('page-title', 'Actieve storingen')

@section('back-button')
    <a class="secondary-button" href="{{ route('stations.index') }}">Terug naar stations</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>Stations met actieve storingen</h2>
            <p class="muted" style="margin-top:0.25rem;">
                Stations met actieve storingen
            </p>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <a href="{{ route('stations.index') }}" class="secondary-button">Terug naar stations</a>
            <a href="{{ route('stations.faults.offline') }}" class="secondary-button">Offline</a>
            <a href="{{ route('stations.faults.missing') }}" class="secondary-button">Ontbrekende data</a>
            <a href="{{ route('stations.faults.temperature') }}" class="secondary-button">Temperatuurcorrecties</a>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STN</th>
                    <th>Locatie</th>
                    <th>Land</th>
                    <th>Laatst gemeten</th>
                    <th>Metingen</th>
                    <th>Offline</th>
                    <th>Ontbrekende data</th>
                    <th>Temp. correcties</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stations as $station)
                <tr>
                    <td><a href="{{ route('stations.show', $station->stn) }}">{{ $station->stn }}</a></td>
                    <td>{{ $station->location_label ?? 'Onbekend' }}</td>
                    <td>{{ $station->country_name ?? '-' }}</td>
                    <td>{{ $station->measured_at ? $station->measured_at . ' UTC' : '-' }}</td>
                    <td>{{ $station->reading_count ?? 0 }}</td>
                    <td>
                        @if ((int)($station->is_online ?? 0) === 0)
                            <span class="status-badge warning">Offline</span>
                        @else
                            <span class="status-badge success">Online</span>
                        @endif
                    </td>
                    <td>
                        @if ((int)($station->has_missing_data ?? 0) === 1)
                            <span class="status-badge warning">Ja</span>
                        @else
                            <span class="status-badge success">Nee</span>
                        @endif
                    </td>
                    <td>
                        @if ((int)($station->is_temp_peak ?? 0) === 1)
                            <span class="status-badge warning">Ja</span>
                        @else
                            <span class="status-badge success">Nee</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="muted" style="text-align:center;">Geen actieve storingen gevonden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top: 15px;">
        {{ $stations->links() }}
    </div>
</article>
@endsection
