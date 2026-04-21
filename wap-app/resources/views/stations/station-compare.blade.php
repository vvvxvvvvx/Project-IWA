{{-- Vergelijkingspagina: meerdere stations naast elkaar voor één meetgegeven. --}}
@extends('layouts.iwa')

@section('title', 'Stations vergelijken')
@section('eyebrow', 'Analyse & monitoring')
@section('page-title', 'Stations vergelijken')
@section('page-subtitle', 'Bekijk één meetgegeven van meerdere stations tegelijk naast elkaar.')

@push('head-scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('back-button')
    <a class="secondary-button" href="{{ route('stations.index') }}">Terug naar stations</a>
@endsection

@section('content')

<style>
    .compare-form select[multiple] {
        height: 180px;
        min-width: 220px;
    }
    .compare-form .form-row {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
        align-items: flex-end;
        margin-bottom: 1.25rem;
    }
    .compare-form label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.35rem;
        font-size: 13px;
    }
    .color-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 6px;
        vertical-align: middle;
    }
    .chart-container {
        position: relative;
        height: 380px;
        width: 100%;
        margin: 15px 0;
    }
    .compare-table td, .compare-table th {
        white-space: nowrap;
    }
    .hint {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 4px;
    }
</style>

{{-- Filter formulier --}}
<section class="panel" style="margin-bottom: 18px;">
    <div class="panel-header">
        <div>
            <h2>Selectie</h2>
            <p class="muted">Kies twee of meer stations en het meetgegeven dat je wilt vergelijken.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('stations.compare') }}" class="compare-form">
        <div class="form-row">
            <div>
                <label for="stations">Stations <span style="font-weight:400;">(meerdere selecteren met Ctrl/Cmd)</span></label>
                <select name="stations[]" id="stations" multiple class="form-control">
                    @foreach ($allStations as $s)
                        <option value="{{ $s->stn }}"
                            {{ in_array($s->stn, $selectedStns) ? 'selected' : '' }}>
                            {{ $s->location_label ?? $s->stn }} ({{ $s->stn }})
                        </option>
                    @endforeach
                </select>
                <p class="hint">Houd Ctrl (Windows) of Cmd (Mac) ingedrukt om meerdere stations te selecteren.</p>
            </div>

            <div>
                <label for="metric">Meetgegeven</label>
                <select name="metric" id="metric" class="form-control" style="min-width: 200px;">
                    @foreach ($metrics as $key => $meta)
                        <option value="{{ $key }}" {{ $selectedMetric === $key ? 'selected' : '' }}>
                            {{ $meta['label'] }} ({{ $meta['unit'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex; gap:0.5rem; align-items:flex-end;">
                <button type="submit" class="primary-button">Vergelijken</button>
                <a href="{{ route('stations.compare') }}" class="secondary-button">Resetten</a>
            </div>
        </div>
    </form>
</section>

@if (count($selectedStns) > 0 && count($chartDatasets) > 0)

{{-- Grafiek --}}
<section class="panel" style="margin-bottom: 18px;">
    <div class="panel-header">
        <div>
            <h2>{{ $metrics[$selectedMetric]['label'] }} — vergelijking</h2>
            <p class="muted">Metingen van de meest recente beschikbare dag per station.</p>
        </div>
    </div>
    <div class="chart-container">
        <canvas id="compareChart"></canvas>
    </div>
</section>

{{-- Vergelijkingstabel --}}
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Laatste meting per station</h2>
            <p class="muted">Meest recente opgeslagen waarden voor alle geselecteerde stations.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table compare-table">
            <thead>
                <tr>
                    <th>Station</th>
                    <th>Locatie</th>
                    <th>Moment</th>
                    <th>Temp (°C)</th>
                    <th>Dauwpunt (°C)</th>
                    <th>Luchtdruk st. (hPa)</th>
                    <th>Luchtdruk z.n. (hPa)</th>
                    <th>Zicht</th>
                    <th>Wind (m/s)</th>
                    <th>Windrichting</th>
                    <th>Neerslag (mm)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tableRows as $row)
                <tr>
                    <td>
                        <span class="color-dot" style="background:{{ $row['color'] }};"></span>
                        <a href="{{ route('stations.show', $row['stn']) }}">{{ $row['stn'] }}</a>
                    </td>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['latest']?->measured_at ?? '-' }}</td>
                    <td>{{ $row['latest']?->temperature ?? '-' }}</td>
                    <td>{{ $row['latest']?->dewpoint_temperature ?? '-' }}</td>
                    <td>{{ $row['latest']?->air_pressure_station ?? '-' }}</td>
                    <td>{{ $row['latest']?->air_pressure_sea_level ?? '-' }}</td>
                    <td>{{ $row['latest']?->visibility ?? '-' }}</td>
                    <td>{{ $row['latest']?->wind_speed ?? '-' }}</td>
                    <td>
                        @php
                            $deg = $row['latest']?->wind_direction ?? null;
                            if ($deg !== null && is_numeric($deg)) {
                                $deg = (float) $deg;
                                $dirs = ['N','NO','O','ZO','Z','ZW','W','NW'];
                                $label = $dirs[round($deg / 45) % 8];
                                echo '<span style="display:inline-block;transform:rotate(' . $deg . 'deg);font-size:1.1em;">↑</span> ' . $label;
                            } else {
                                echo '-';
                            }
                        @endphp
                    </td>
                    <td>{{ $row['latest']?->percipation ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@elseif (count($selectedStns) > 0)
<div class="panel" style="padding: 2rem; text-align:center; color: var(--text-muted);">
    Geen meetdata gevonden voor de geselecteerde stations.
</div>
@else
<div class="panel" style="padding: 2rem; text-align:center; color: var(--text-muted);">
    Selecteer minimaal twee stations om de vergelijking te starten.
</div>
@endif

@endsection

@push('scripts')
@if (count($chartDatasets) > 0)
<script>
const sharedLabels = {!! json_encode($sharedLabels) !!};
const rawDatasets  = {!! json_encode($chartDatasets) !!};
const metricLabel  = '{{ $metrics[$selectedMetric]['label'] }} ({{ $metrics[$selectedMetric]['unit'] }})';

// Bouw Chart.js datasets, gebruik de gedeelde tijdlabels
const datasets = rawDatasets.map(ds => ({
    label:           ds.label,
    data:            ds.data,
    borderColor:     ds.borderColor,
    backgroundColor: ds.backgroundColor,
    borderWidth:     ds.borderWidth,
    tension:         ds.tension,
    fill:            ds.fill,
    pointRadius:     ds.pointRadius,
    pointBorderColor: ds.borderColor,
    pointBackgroundColor: '#fff',
    pointBorderWidth: 2,
}));

const ctx = document.getElementById('compareChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: sharedLabels,
        datasets: datasets,
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    title: (items) => 'Tijd: ' + items[0].label,
                }
            }
        },
        scales: {
            y: {
                title: {
                    display: true,
                    text: metricLabel,
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Tijd (UTC)',
                }
            }
        }
    }
});
</script>
@endif
@endpush
