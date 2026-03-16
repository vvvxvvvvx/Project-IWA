@extends('layouts.iwa')

@section('title', 'Stations')
@section('eyebrow', 'Analyse & monitoring')
@section('page-title', 'Stationsoverzicht')
@section('page-subtitle', 'Duidelijke lijst van alle weerstations met locatie, laatste meting en status.')

@section('back-button')
    <a class="secondary-button" href="{{ route('dashboard') }}">Terug naar dashboard</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>Alle weerstations</h2>
            <p class="muted">Welk station stuurt welke data, de laatst ontvangen temperatuur en of er missing data of piekmetingen zijn gedetecteerd.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STN</th>
                    <th>Locatie</th>
                    <th>Co&ouml;rdinaten</th>
                    <th>Laatst gemeten</th>
                    <th>Temp</th>
                    <th>Gem.</th>
                    <th>Zicht</th>
                    <th>Wind</th>
                    <th>Neerslag</th>
                    <th>Metingen</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stations as $station)
                <tr>
                    <td><a href="{{ route('stations.show', $station->stn) }}">{{ $station->stn }}</a></td>
                    <td>{{ $station->location_label ?? 'Onbekend' }}</td>
                    <td>{{ $station->lat !== null ? $station->lat . ', ' . $station->lon : '-' }}</td>
                    <td>{{ $station->measured_at ? $station->measured_at . ' UTC' : '-' }}</td>
                    <td>{{ $station->temp ?? '-' }}</td>
                    <td>{{ $station->avg_temp ?? '-' }}</td>
                    <td>{{ $station->visib ?? '-' }}</td>
                    <td>{{ $station->wdsp ?? '-' }}</td>
                    <td>{{ $station->prcp ?? '-' }}</td>
                    <td>{{ $station->reading_count ?? 0 }}</td>
                    <td>
                        @if ((int)($station->has_missing_data ?? 0) === 1)
                            <span class="status-badge warning">Missing</span>
                        @elseif ((int)($station->is_temp_peak ?? 0) === 1)
                            <span class="status-badge info">Peak</span>
                        @else
                            <span class="status-badge success">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</article>
@endsection
