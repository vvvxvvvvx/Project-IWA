{{-- Detailpagina voor één station inclusief recente metingen. --}}
@extends('layouts.iwa')

@section('title', $station->stn)
@section('eyebrow', 'Station detail')
@section('page-title', $station->stn)
@section('page-subtitle', ($station->location_label ?? 'Onbekend') . ' · ' . ($station->country_name ?? 'Onbekend') . ' · STN ' . $station->stn)

@push('head-scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_KEY') }}"></script>
@endpush

@section('back-button')
    <a class="secondary-button" href="{{ route('stations.index') }}">Terug naar stations</a>
@endsection

@section('content')

<style>
    .station-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 18px;
    }
    
    .station-detail-grid .panel {
        display: flex;
        flex-direction: column;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        margin: 15px 0;
    }
    
    .station-metrics {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin: 18px 0;
    }
    
    .metric-box {
        background: #ffffff;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        border: 1px solid var(--border);
        border-left: 4px solid var(--primary);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .metric-box h3 {
        font-size: 14px;
        color: var(--text-muted);
        margin: 0 0 10px 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .metric-value {
        font-size: 32px;
        font-weight: 700;
        margin: 10px 0;
    }
    
    .metric-label {
        font-size: 13px;
        color: var(--text-muted);
    }
    
    #stationMap {
        width: 100%;
        height: 300px;
        border-radius: 8px;
        border: 1px solid var(--border);
        margin: 15px 0;
        background: #e0e0e0;
    }
    
    @media (max-width: 1024px) {
        .station-detail-grid {
            grid-template-columns: 1fr;
        }
        .station-metrics {
            grid-template-columns: 1fr;
        }
    }
</style>

{{-- Top Section: Grafiek + Google Maps --}}
<div class="station-detail-grid">
    {{-- Temperatuurtrend Grafiek --}}
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Temperatuurtrend</h2>
                <p class="muted">Metingen van vandaag met trends en afwijkingen.</p>
            </div>
        </div>
        <div style="position: relative;">
            <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                <div style="font-weight: 600; font-size: 13px; padding-top: 8px;">Periode:</div>
                <button class="secondary-button" onclick="updateChart('day')" id="btn-day" style="padding: 6px 12px; font-size: 13px;">Dag</button>
                <button class="secondary-button" onclick="updateChart('week')" id="btn-week" style="padding: 6px 12px; font-size: 13px;">Week</button>
                <button class="secondary-button" onclick="updateChart('month')" id="btn-month" style="padding: 6px 12px; font-size: 13px;">Maand</button>
                <button class="secondary-button" onclick="updateChart('all')" id="btn-all" style="padding: 6px 12px; font-size: 13px;">Alle data</button>
            </div>

            <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                <select id="metricSelect" onchange="updateMetric()" class="form-control" style="flex: 1; min-width: 150px;">
                    <option value="temperature">Temperatuur</option>
                    <option value="dewpoint">Dauwpunt</option>
                    <option value="pressure">Luchtdruk</option>
                    <option value="visibility">Zicht</option>
                    <option value="wind">Wind</option>
                    <option value="precipitation">Neerslag</option>
                </select>
            </div>
            <div class="chart-container">
                <canvas id="stationTemperatureChart"></canvas>
            </div>
        </div>
    </section>

    {{-- Google Maps --}}
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Locatie</h2>
                <p class="muted">Geografische positie van het station.</p>
            </div>
        </div>
        <div id="stationMap"></div>
        <div style="margin-top: 15px; padding: 10px; background: var(--background-secondary); border-radius: 6px; font-size: 13px;">
            <strong>Coördinaten:</strong> {{ $station->lat }}, {{ $station->lon }}<br>
            <strong>Plaats:</strong> {{ $station->location_label ?? 'Onbekend' }}<br>
            <strong>Land:</strong> {{ $station->country_name ?? 'Onbekend' }}
        </div>
    </section>
</div>

{{-- Middle Section: Data Quality Metrics (3 kolommen) --}}
<div class="station-metrics">
    <div class="metric-box" style="border-left-color: #4CAF50;">
        <h3>Datakwaliteit</h3>
        <div class="metric-value" style="color: #4CAF50;">{{ $qualityPercentage }}%</div>
        <div class="metric-label">Metingen vandaag: {{ $totalReadings }}</div>
    </div>

    <div class="metric-box" style="border-left-color: #FF9800;">
        <h3>Temperatuurcorrecties</h3>
        <div class="metric-value" style="color: #FF9800;">{{ $correctionPercentage }}%</div>
        <div class="metric-label">{{ (int)($correctionPercentage * $totalReadings / 100) }} van {{ $totalReadings }} metingen</div>
    </div>

    <div class="metric-box" style="border-left-color: #F44336;">
        <h3>Ontbrekende Velden</h3>
        <div class="metric-value" style="color: #F44336;">{{ $missingFieldsPercentage }}%</div>
        <div class="metric-label">{{ (int)($missingFieldsPercentage * $totalReadings / 100) }} van {{ $totalReadings }} metingen</div>
    </div>
</div>

{{-- Bottom Section: Detailed Readings Table --}}
<section class="panel" style="margin-top: 18px;">
    <div class="panel-header">
        <div>
            <h2>Gedetailleerde Metingen</h2>
            <p class="muted">Alle metingen met filteropties per periode.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('stations.show', $station->stn) }}" style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap; align-items: end;">
        <div>
            <label style="display:block; font-weight:600; margin-bottom:5px; font-size: 13px;">Periode</label>
            <select name="period" id="period" class="form-control" style="width: 150px;">
                <option value="day" @if(request('period', 'day') === 'day') selected @endif>Vandaag</option>
                <option value="week" @if(request('period') === 'week') selected @endif>Afgelopen week</option>
                <option value="month" @if(request('period') === 'month') selected @endif>Afgelopen maand</option>
                <option value="custom" @if(request('period') === 'custom') selected @endif>Aangepaste periode</option>
            </select>
        </div>
        <div id="customDates" style="display: @if(request('period') === 'custom') block @else none @endif;">
            <label style="display:block; font-weight:600; margin-bottom:5px; font-size: 13px;">Van</label>
            <input type="date" name="from" value="{{ request('from', now()->format('Y-m-d')) }}" style="border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;">
        </div>
        <div id="customDatesTo" style="display: @if(request('period') === 'custom') block @else none @endif;">
            <label style="display:block; font-weight:600; margin-bottom:5px; font-size: 13px;">Tot</label>
            <input type="date" name="to" value="{{ request('to', now()->format('Y-m-d')) }}" style="border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;">
        </div>
        <div>
            <button type="submit" class="primary-button" style="padding: 8px 16px;">Filter toepassen</button>
        </div>
        <div>
            <a href="{{ route('stations.show', $station->stn) }}" class="secondary-button" style="padding: 8px 16px;">Reset</a>
        </div>
    </form>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Moment</th>
                    <th>Temp</th>
                    <th>Dauwpunt</th>
                    <th>Luchtdruk (st.)</th>
                    <th>Luchtdruk (z.n.)</th>
                    <th>Zicht</th>
                    <th>Wind</th>
                    <th>Neerslag</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($readings as $reading)
                    @php 
                        $readingArray = (array)$reading;
                        $isTempCorrected = isset($readingArray['orig_temp']) && $readingArray['orig_temp'] !== null;
                        $isMissing = isset($readingArray['is_missing']) && $readingArray['is_missing'] !== null;
                    @endphp
                    <tr>
                        <td>
                            @if(request('period', 'day') === 'day')
                                {{ $readingArray['measured_at'] ?? '-' }} UTC
                            @elseif(request('period') === 'week')
                                {{ $readingArray['date'] . ' ' . str_pad($readingArray['hour'], 2, '0', STR_PAD_LEFT) . ':00' }} UTC
                            @else
                                {{ $readingArray['date'] ?? (isset($readingArray['measured_at']) ? substr($readingArray['measured_at'], 0, 10) . ' 12:00' : '-') }} UTC
                            @endif
                        </td>
                        <td>
                            @if($isTempCorrected)
                                <span style="background: #FFB74D; padding: 4px 8px; border-radius: 4px; cursor: help;" title="Oorspronkelijke waarde: {{ $readingArray['orig_temp'] }}">
                                    {{ $readingArray['temp'] ?? '-' }}
                                </span>
                            @elseif($isMissing)
                                <span style="background: #EF5350; padding: 4px 8px; border-radius: 4px; color: white;" title="Veld was ontbrekend">
                                    {{ $readingArray['temp'] ?? '-' }}
                                </span>
                            @else
                                {{ $readingArray['temp'] ?? '-' }}
                            @endif
                        </td>
                        <td>{{ $readingArray['dewp'] ?? '-' }}</td>
                        <td>{{ $readingArray['stp'] ?? '-' }}</td>
                        <td>{{ $readingArray['slp'] ?? '-' }}</td>
                        <td>{{ $readingArray['visib'] ?? '-' }}</td>
                        <td>{{ $readingArray['wdsp'] ?? '-' }}</td>
                        <td>{{ $readingArray['prcp'] ?? '-' }}</td>
                    </tr>
                @empty
                <tr>
                    <td colspan="8" class="muted" style="text-align:center;">Geen metingen gevonden voor de geselecteerde periode.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- CSV Download --}}
    <form method="GET" action="{{ route('stations.download', $station->stn) }}" style="margin-top: 15px;">
        <input type="hidden" name="from" value="{{ request('from', now()->format('Y-m-d')) }}">
        <input type="hidden" name="to" value="{{ request('to', now()->format('Y-m-d')) }}">
        <button type="submit" class="secondary-button" style="padding: 8px 16px;">📥 Download als CSV</button>
    </form>
</section>

@endsection

@push('scripts')
@php
    // Convert Collections to arrays and cast stdClass objects to arrays
    $readingsTodayArray = $readingsToday->map(fn($item) => (array)$item)->toArray();
    $readingsWeekArray = $readingsWeek->map(fn($item) => (array)$item)->toArray();
    $readingsMonthArray = $readingsMonth->map(fn($item) => (array)$item)->toArray();
    $readingsAllArray = $readingsAll->map(fn($item) => (array)$item)->toArray();
    
    // Helper function to extract metric data
    $getMetricData = function($readings, $metric) {
        $fieldMap = [
            'temperature' => 'temp',
            'dewpoint' => 'dewp',
            'pressure' => 'stp',
            'visibility' => 'visib',
            'wind' => 'wdsp',
            'precipitation' => 'prcp'
        ];
        $field = $fieldMap[$metric] ?? 'temp';
        return array_map(fn($r) => $r[$field] !== null ? (float)$r[$field] : null, $readings);
    };
    
    // Dag data (uren format: HH:MM)
    $labelsDay = array_map(fn($r) => substr((string)$r['measured_at'], 11, 5), $readingsTodayArray);
    
    // Week data (datum + uur: YYYY-MM-DD HH:00)
    $labelsWeek = array_map(fn($r) => $r['date'] . ' ' . str_pad($r['hour'], 2, '0', STR_PAD_LEFT) . ':00', $readingsWeekArray);
    
    // Maand data (alleen datum: YYYY-MM-DD)
    $labelsMonth = array_map(fn($r) => $r['date'], $readingsMonthArray);
    
    // Alle data (alleen datum: YYYY-MM-DD)
    $labelsAll = array_map(fn($r) => $r['date'], $readingsAllArray);
@endphp
<script>
const stn = '{{ $station->stn }}';
const stationLat = {{ $station->lat }};
const stationLon = {{ $station->lon }};
const stationLocation = '{{ $station->location_label ?? "Onbekend" }}';
const stationCountry = '{{ $station->country_name ?? "Onbekend" }}';

// Metric labels for display
const metricLabels = {
    temperature: 'Temperatuur (°C)',
    dewpoint: 'Dauwpunt (°C)',
    pressure: 'Luchtdruk Station (hPa)',
    visibility: 'Zicht (m)',
    wind: 'Wind (m/s)',
    precipitation: 'Neerslag (mm)'
};

// Chart data sets with all metrics
const chartData = {
    day: {
        labels: {!! json_encode($labelsDay) !!},
        temperature: {!! json_encode($getMetricData($readingsTodayArray, 'temperature')) !!},
        dewpoint: {!! json_encode($getMetricData($readingsTodayArray, 'dewpoint')) !!},
        pressure: {!! json_encode($getMetricData($readingsTodayArray, 'pressure')) !!},
        visibility: {!! json_encode($getMetricData($readingsTodayArray, 'visibility')) !!},
        wind: {!! json_encode($getMetricData($readingsTodayArray, 'wind')) !!},
        precipitation: {!! json_encode($getMetricData($readingsTodayArray, 'precipitation')) !!}
    },
    week: {
        labels: {!! json_encode($labelsWeek) !!},
        temperature: {!! json_encode($getMetricData($readingsWeekArray, 'temperature')) !!},
        dewpoint: {!! json_encode($getMetricData($readingsWeekArray, 'dewpoint')) !!},
        pressure: {!! json_encode($getMetricData($readingsWeekArray, 'pressure')) !!},
        visibility: {!! json_encode($getMetricData($readingsWeekArray, 'visibility')) !!},
        wind: {!! json_encode($getMetricData($readingsWeekArray, 'wind')) !!},
        precipitation: {!! json_encode($getMetricData($readingsWeekArray, 'precipitation')) !!}
    },
    month: {
        labels: {!! json_encode($labelsMonth) !!},
        temperature: {!! json_encode($getMetricData($readingsMonthArray, 'temperature')) !!},
        dewpoint: {!! json_encode($getMetricData($readingsMonthArray, 'dewpoint')) !!},
        pressure: {!! json_encode($getMetricData($readingsMonthArray, 'pressure')) !!},
        visibility: {!! json_encode($getMetricData($readingsMonthArray, 'visibility')) !!},
        wind: {!! json_encode($getMetricData($readingsMonthArray, 'wind')) !!},
        precipitation: {!! json_encode($getMetricData($readingsMonthArray, 'precipitation')) !!}
    },
    all: {
        labels: {!! json_encode($labelsAll) !!},
        temperature: {!! json_encode($getMetricData($readingsAllArray, 'temperature')) !!},
        dewpoint: {!! json_encode($getMetricData($readingsAllArray, 'dewpoint')) !!},
        pressure: {!! json_encode($getMetricData($readingsAllArray, 'pressure')) !!},
        visibility: {!! json_encode($getMetricData($readingsAllArray, 'visibility')) !!},
        wind: {!! json_encode($getMetricData($readingsAllArray, 'wind')) !!},
        precipitation: {!! json_encode($getMetricData($readingsAllArray, 'precipitation')) !!}
    }
};

let currentChartPeriod = 'day';
let currentMetric = 'temperature';

console.log('Chart data loaded for all metrics');

console.log('Station:', stn, 'Chart data loaded');
console.log('Lat:', stationLat, 'Lon:', stationLon);

// Google Maps initialisatie
let stationMap;
function initStationMap() {
    console.log('Attempting to initialize map...');
    
    const mapElement = document.getElementById('stationMap');
    if (!mapElement) {
        console.error('stationMap element not found!');
        return;
    }
    
    console.log('Map element found, creating map...');
    
    try {
        stationMap = new google.maps.Map(mapElement, {
            zoom: 10,
            center: { lat: stationLat, lng: stationLon }
        });
        
        new google.maps.Marker({
            position: { lat: stationLat, lng: stationLon },
            map: stationMap,
            title: stn + ' - ' + stationLocation
        });
        
        console.log('Map initialized successfully');
    } catch (e) {
        console.error('Map initialization error:', e);
    }
}

// Metric colors
const metricColors = {
    temperature: { border: '#FF6B6B', background: 'rgba(255, 107, 107, 0.1)' },
    dewpoint: { border: '#4ecdc4', background: 'rgba(78, 205, 196, 0.1)' },
    pressure: { border: '#95e1d3', background: 'rgba(149, 225, 211, 0.1)' },
    visibility: { border: '#f38181', background: 'rgba(243, 129, 129, 0.1)' },
    wind: { border: '#aa96da', background: 'rgba(170, 150, 218, 0.1)' },
    precipitation: { border: '#5dade2', background: 'rgba(93, 173, 226, 0.1)' }
};

// Chart initialization
let stationChart;
function initChart() {
    console.log('Attempting to initialize chart...');
    
    const chartElement = document.getElementById('stationTemperatureChart');
    if (!chartElement) {
        console.error('Chart element not found!');
        return;
    }
    
    const data = chartData[currentChartPeriod];
    const colors = metricColors[currentMetric];
    console.log('Creating chart with period:', currentChartPeriod, 'Metric:', currentMetric, 'Data points:', data.labels.length);
    
    try {
        const ctx = chartElement.getContext('2d');
        
        stationChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: metricLabels[currentMetric],
                    data: data[currentMetric],
                    borderColor: colors.border,
                    backgroundColor: colors.background,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBorderColor: colors.border,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        title: {
                            display: true,
                            text: metricLabels[currentMetric]
                        }
                    }
                }
            }
        });
        
        console.log('Chart initialized successfully');
    } catch (e) {
        console.error('Chart initialization error:', e);
    }
}

function updateChartData() {
    if (!stationChart) {
        initChart();
        return;
    }
    
    const data = chartData[currentChartPeriod];
    const colors = metricColors[currentMetric];
    console.log('Updating chart to period:', currentChartPeriod, 'Metric:', currentMetric);
    
    stationChart.data.labels = data.labels;
    stationChart.data.datasets[0].data = data[currentMetric];
    stationChart.data.datasets[0].label = metricLabels[currentMetric];
    stationChart.data.datasets[0].borderColor = colors.border;
    stationChart.data.datasets[0].backgroundColor = colors.background;
    stationChart.data.datasets[0].pointBorderColor = colors.border;
    stationChart.update();
    
    // Update button states
    ['day', 'week', 'month', 'all'].forEach(period => {
        const btn = document.getElementById('btn-' + period);
        if (btn) {
            if (period === currentChartPeriod) {
                btn.style.backgroundColor = '#4CAF50';
                btn.style.color = '#fff';
            } else {
                btn.style.backgroundColor = '';
                btn.style.color = '';
            }
        }
    });
}

// Periode selector
function setupPeriodeSelector() {
    const periodSelect = document.getElementById('period');
    if (periodSelect) {
        periodSelect.addEventListener('change', function() {
            const customDates = document.getElementById('customDates');
            const customDatesTo = document.getElementById('customDatesTo');
            if (this.value === 'custom') {
                customDates.style.display = 'block';
                customDatesTo.style.display = 'block';
            } else {
                customDates.style.display = 'none';
                customDatesTo.style.display = 'none';
            }
        });
    }
}

// Chart functie
function updateChart(period) {
    currentChartPeriod = period;
    console.log('Chart period changed to:', period);
    updateChartData();
}

// Metric selector
function updateMetric() {
    const metric = document.getElementById('metricSelect').value;
    currentMetric = metric;
    console.log('Metric changed to:', metric);
    updateChartData();
}

// Main initialization - wait for Google Maps API and DOM to be ready
function initializePageWhenReady() {
    console.log('Checking if Google Maps is loaded...');
    
    if (typeof google !== 'undefined' && google.maps) {
        console.log('Google Maps API is loaded');
        initStationMap();
    } else {
        console.log('Google Maps not loaded yet, retrying in 500ms...');
        setTimeout(initializePageWhenReady, 500);
        return;
    }
    
    initChart();
    setupPeriodeSelector();
    console.log('Page initialization complete');
}

// Start initialization when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded fired');
    setTimeout(initializePageWhenReady, 100);
});

// Also try on window load
window.addEventListener('load', () => {
    console.log('Window load fired, reinitializing if needed');
    setTimeout(initializePageWhenReady, 100);
});
</script>
<script src="/assets/station.js"></script>
@endpush