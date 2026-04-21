{{-- Overzicht van offline stations. --}}
@extends('layouts.iwa')

@section('title', 'Offline stations')
@section('eyebrow', 'Analyse & monitoring')
@section('page-title', 'Offline stations')

@section('back-button')
    <a class="secondary-button" href="{{ route('stations.faults') }}">Terug naar storingen</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>Offline stations</h2>
            <p class="muted" style="margin-top:0.25rem;">
                {{ $stations->count() }} {{ $stations->count() === 1 ? 'station' : 'stations' }} gevonden
            </p>
        </div>
        <a href="{{ route('stations.faults') }}" class="secondary-button">Terug naar storingen</a>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STN</th>
                    <th>Locatie</th>
                    <th>Land</th>
                    <th>Laatste meting</th>
                    <th>Totaal metingen</th>
                    <th>Status</th>
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
                    <td><span class="status-badge warning">Offline</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="muted" style="text-align:center;">Geen offline stations gevonden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
