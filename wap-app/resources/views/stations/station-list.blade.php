{{-- Overzichtspagina voor stations met statusfilter. --}}
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
            <p class="muted">
                Overzicht van alle weerstations met hun laatste meting en de huidige datastatus.
                Gebruik de filter om alleen stations met een bepaalde status te tonen.
            </p>
        </div>
    </div>

    {{-- Hier tot beneden is de nieuwe FILTER sectie toegevoegd voor de status van de stations. De rest van de code is ongewijzigd gebleven. --}}

    <form method="GET" action="{{ route('stations.index') }}" class="filter-form" style="margin-bottom: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
            <div>
                <label for="status" style="display:block; font-weight:600; margin-bottom:0.35rem;">Filter op status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">Alle statussen</option>
                    <option value="ok" {{ request('status') === 'ok' ? 'selected' : '' }}>OK</option>
                    <option value="missing" {{ request('status') === 'missing' ? 'selected' : '' }}>Missing</option>
                    <option value="peak" {{ request('status') === 'peak' ? 'selected' : '' }}>Peak</option>
                </select>
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button type="submit" class="primary-button">Filter toepassen</button>
                <a href="{{ route('stations.index') }}" class="secondary-button">Reset</a>
            </div>
        </div>

        <div class="muted" style="margin-top: 0.75rem;">
            <strong>Betekenis van de statussen:</strong><br>
            <strong>OK</strong> = Er is geen ontbrekende data en geen piekmeting gevonden.<br>
            <strong>Missing</strong> = Er ontbreekt meetdata in de laatste meting.<br>
            <strong>Peak</strong> = Er is een opvallende piek in de temperatuur gedetecteerd.
        </div>
    </form>

     {{-- Tot hier --}}

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
                @forelse ($stations as $station)
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
                @empty
                <tr>
                    <td colspan="11" class="muted" style="text-align:center;">Geen stations gevonden voor deze filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
