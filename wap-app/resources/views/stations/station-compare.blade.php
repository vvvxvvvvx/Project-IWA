{{-- Vergelijkingspagina: meerdere stations naast elkaar voor één meetgegeven. --}}
@extends('layouts.iwa')

@section('title', 'Stations vergelijken')
@section('eyebrow', 'Analyse & monitoring')
@section('page-title', 'Stations vergelijken')

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
        <div class="form-row" style="align-items: flex-start;">
            <div>
                <label for="stations">Stations <span style="font-weight:400;">(meerdere selecteren met Ctrl/Cmd)</span></label>
                <select name="stations[]" id="stations" multiple class="form-control">
                    @foreach ($allStations as $s)
                        <option value="{{ $s->stn }}"
                            data-country="{{ $s->country_code }}"
                            {{ in_array($s->stn, $selectedStns) ? 'selected' : '' }}>
                            {{ $s->location_label ?? $s->stn }} ({{ $s->stn }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label for="country-filter">Filter op land</label>
                    <select name="country_filter" id="country-filter" class="form-control" style="min-width: 200px;">
                        <option value="">Alle landen</option>
                        @foreach ($countries as $c)
                            <option value="{{ $c->country_code }}" {{ $selectedCountry === $c->country_code ? 'selected' : '' }}>
                                {{ $c->country_name }}
                            </option>
                        @endforeach
                    </select>
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

                <div>
                    <label for="period">Periode</label>
                    <select name="period" id="period" class="form-control" style="min-width: 200px;" onchange="toggleDatePicker(this.value)">
                        <option value="day"      {{ $selectedPeriod === 'day'      ? 'selected' : '' }}>Vandaag</option>
                        <option value="specific" {{ $selectedPeriod === 'specific' ? 'selected' : '' }}>Specifieke dag</option>
                        <option value="week"     {{ $selectedPeriod === 'week'     ? 'selected' : '' }}>Afgelopen 7 dagen (gemiddeld)</option>
                        <option value="month"    {{ $selectedPeriod === 'month'    ? 'selected' : '' }}>Afgelopen 30 dagen (gemiddeld)</option>
                    </select>
                </div>

                <div id="date-picker-wrapper" style="display: {{ $selectedPeriod === 'specific' ? 'block' : 'none' }};">
                    <label for="date">Kies een datum</label>
                    <input type="date" name="date" id="date" value="{{ $selectedDate }}"
                        max="{{ now()->format('Y-m-d') }}"
                        class="form-control" style="min-width: 200px;">
                </div>

                <div style="display:flex; gap:0.5rem;">
                    <button type="submit" class="primary-button">Vergelijken</button>
                    <a href="{{ route('stations.compare') }}" class="secondary-button">Resetten</a>
                </div>
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
            <p class="muted">
                @if($selectedPeriod === 'day') Uurdata voor vandaag ({{ now()->format('d-m-Y') }}).
                @elseif($selectedPeriod === 'specific') Uurdata voor {{ \Carbon\Carbon::parse($selectedDate)->format('d-m-Y') }}.
                @elseif($selectedPeriod === 'week') Daggemiddelden — afgelopen 7 dagen.
                @elseif($selectedPeriod === 'month') Daggemiddelden — afgelopen 30 dagen.
                @endif
            </p>
        </div>
    </div>
    <div class="chart-container">
        <canvas id="compareChart"></canvas>
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
{{-- Filter logica --}}
<script>
function toggleDatePicker(value) {
    document.getElementById('date-picker-wrapper').style.display = value === 'specific' ? 'block' : 'none';
}

function applyCountryFilter(selectedCountry) {
    const stationsSelect = document.getElementById('stations');
    const options = stationsSelect.querySelectorAll('option');

    options.forEach(function (option) {
        const countryCode = option.getAttribute('data-country');
        const isSelected  = option.selected;

        // Geselecteerde stations altijd zichtbaar houden
        if (!selectedCountry || countryCode === selectedCountry || isSelected) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
}

document.getElementById('country-filter').addEventListener('change', function () {
    applyCountryFilter(this.value);
});

// Filter direct toepassen bij het laden van de pagina
document.addEventListener('DOMContentLoaded', function () {
    const countryFilter = document.getElementById('country-filter');
    if (countryFilter.value) {
        applyCountryFilter(countryFilter.value);
    }
});
</script>

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
                                                                                                                    