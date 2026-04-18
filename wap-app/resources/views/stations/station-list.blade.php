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
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="{{ route('stations.faults') }}" class="secondary-button">Actieve storingen</a>
        </div>
    </div>

    {{-- Hier tot beneden is de nieuwe FILTER sectie toegevoegd voor de Land en Plaats filters. --}}

    <form method="GET" action="{{ route('stations.index') }}" class="filter-form" style="margin-bottom: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
            <div>
                <label for="country" style="display:block; font-weight:600; margin-bottom:0.35rem;">Filter op land</label>
                <select name="country" id="country" class="form-control" onchange="this.form.submit()">
                    @foreach ($countries as $c)
                        <option value="{{ $c->country_code }}" {{ $selectedCountry === $c->country_code ? 'selected' : '' }}>
                            {{ $c->country }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="location" style="display:block; font-weight:600; margin-bottom:0.35rem;">Filter op plaats</label>
                <select name="location" id="location" class="form-control" onchange="this.form.submit()">
                    <option value="">Alle plaatsen</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->name }}" {{ $selectedLocation === $loc->name ? 'selected' : '' }}>
                            {{ $loc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex; gap:0.5rem;">
                <a href="{{ route('stations.index') }}" class="secondary-button">Reset</a>
            </div>
        </div>
    </form>

     {{-- Tot hier --}}

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STN</th>
                    <th>Locatie</th>
                    <th>Land</th>
                    <th>Laatst gemeten</th>
                    <th>Temp</th>
                    <th>Gem.</th>
                    <th>Zicht</th>
                    <th>Wind</th>
                    <th>Windrichting</th>
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
                    <td>{{ $station->country_name ?? '-' }}</td>
                    <td>{{ $station->measured_at ? $station->measured_at . ' UTC' : '-' }}</td>
                    <td>{{ $station->temp ?? '-' }}</td>
                    <td>{{ $station->avg_temp ?? '-' }}</td>
                    <td>{{ $station->visib ?? '-' }}</td>
                    <td>{{ $station->wdsp ?? '-' }}</td>
                    <td>
                        @php
                            $deg = $station->wnddir ?? null;
                            if ($deg !== null && $deg !== '' && is_numeric($deg)) {
                                $deg = (float) $deg;
                                $dirs = ['N','NO','O','ZO','Z','ZW','W','NW'];
                                $label = $dirs[round($deg / 45) % 8];
                                $arrow = '<span style="display:inline-block;transform:rotate(' . $deg . 'deg);font-size:1.1em;">↑</span>';
                            } else {
                                $label = '-';
                                $arrow = '';
                            }
                        @endphp
                        {!! $arrow !!} {{ $label }}
                    </td>
                    <td>{{ $station->prcp ?? '-' }}</td>
                    <td>{{ $station->reading_count ?? 0 }}</td>
                    <td>
                        @if ((int)($station->is_online ?? 0) === 1)
                            <span class="status-badge success">Online</span>
                        @else
                            <span class="status-badge warning">Offline</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="muted" style="text-align:center;">Geen stations gevonden voor deze filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
