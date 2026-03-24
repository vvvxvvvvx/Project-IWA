<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IWA Weerdashboard</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

{{-- ===== HEADER ===== --}}
<header class="app-header">
    <div class="brand-block">
        <img class="iwa-logo" src="/assets/iwa-logo.png" alt="IWA logo">
        <div>
            <p class="eyebrow">Internationale Weer Agentschap</p>
            <h1>Weerdashboard</h1>
            <p class="header-subtitle">Ingelogd als {{ $displayName }} &middot; Rol: {{ $role === 'admin' ? 'Administrator' : 'Medewerker' }}</p>
        </div>
    </div>
    <div class="header-actions">
        <div class="live-pill" id="liveIndicator">Live</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="secondary-button" type="submit">Uitloggen</button>
        </form>
    </div>
</header>

{{-- ===== NAVIGATION ===== --}}
<nav class="page-nav">
    <a class="active" href="{{ route('dashboard') }}">Dashboard</a>
    <a href="{{ route('stations.index') }}">Stations</a>
    <a href="{{ route('subscriptions.index') }}">Abonnementen</a>
    <a href="{{ route('subscription-types.index') }}">Aanbod</a>
    <a href="{{ route('contracts.index') }}">Contracten</a>
    <a href="{{ route('companies.index') }}">Bedrijven</a>
</nav>

{{-- ===== MAIN SHELL ===== --}}
<main class="dashboard-shell">

    {{-- Metrics grid --}}
    <section class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">◢</div>
            <div>
                <span class="metric-label">Stations online</span>
                <strong class="metric-value" id="metricStationCount">{{ $overview['station_count'] ?? 0 }}</strong>
                <span class="metric-change positive">Totaal aantal weerstations</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon">▣</div>
            <div>
                <span class="metric-label">Metingen</span>
                <strong class="metric-value" id="metricReadingCount">{{ $overview['reading_count'] ?? 0 }}</strong>
                <span class="metric-change positive">Totaal opgeslagen metingen</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon">℃</div>
            <div>
                <span class="metric-label">Gemiddelde temperatuur</span>
                <strong class="metric-value" id="metricAverageTemp">{{ $overview['average_temp'] ?? '-' }}</strong>
                <span class="metric-change info">Over alle stations</span>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon">⌁</div>
            <div>
                <span class="metric-label">Datakwaliteit</span>
                <strong class="metric-value">
                    <span id="metricPeakCount">{{ $overview['peak_count'] ?? 0 }}</span>
                    /
                    <span id="metricMissingCount">{{ $overview['missing_count'] ?? 0 }}</span>
                </strong>
                <span class="metric-change warning">Pieken / ontbrekend</span>
            </div>
        </div>
    </section>

    {{-- Quick links --}}
    <section class="quick-links-grid">
        <a class="quick-link-card" href="{{ route('subscriptions.index') }}">
            <strong>Abonnementen</strong>
            <span>Bestanden en abonnementen inclusief klanten, prijzen en tokens.</span>
        </a>
        <a class="quick-link-card" href="{{ route('subscription-types.index') }}">
            <strong>Aanbod</strong>
            <span>Bekijk welke abonnementen IWA aanbiedt en wijzig type-informatie.</span>
        </a>
        <a class="quick-link-card" href="{{ route('contracts.index') }}">
            <strong>Contracten</strong>
            <span>Contractpagina's met looptijd en activiteitsinformatie.</span>
        </a>
        <a class="quick-link-card" href="{{ route('companies.index') }}">
            <strong>Bedrijven &amp; contactpersonen</strong>
            <span>Beheer relaties, contactpersonen en gekoppelde abonnementen.</span>
        </a>
    </section>

    {{-- Tab strip --}}
    <section class="tab-strip">
        <button class="tab-button active" type="button" data-tab-target="overviewTab">Overzicht</button>
        <button class="tab-button" type="button" data-tab-target="stationsTab">Stations</button>
        <button class="tab-button" type="button" data-tab-target="qualityTab">Registratie &amp; controles</button>
    </section>

    {{-- ===== TAB: OVERZICHT ===== --}}
    <section class="tab-panel active" id="overviewTab">
        <section class="content-grid">

            {{-- Temperatuurgrafiek --}}
            <article class="panel chart-panel">
                <div class="panel-header">
                    <div>
                        <h2>Originele vs gecorrigeerde temperatuur</h2>
                        <p class="muted">Ruwe temperatuur naast de gecorrigeerde waarde voor recente correcties.</p>
                    </div>
                </div>
                <canvas id="temperatureChart" height="160"></canvas>
            </article>

            {{-- Actiefste stations donut --}}
            <article class="panel donut-panel">
                <div class="panel-header">
                    <div>
                        <h2>Actiefste stations</h2>
                        <p class="muted">Stations met de meeste metingen.</p>
                    </div>
                </div>
                <div class="donut-layout">
                    <ul class="source-list" id="topStationsList">
                        @foreach ($top_stations as $station)
                        <li>
                            <span class="source-name">{{ $station->name }}</span>
                            <span class="source-value">{{ $station->reading_count }}</span>
                        </li>
                        @endforeach
                    </ul>
                    <div class="donut-wrapper">
                        <canvas id="stationChart" height="180"></canvas>
                    </div>
                </div>
            </article>

            {{-- KPI bars --}}
            @php
                $stationCount = max((int)($overview['station_count'] ?? 0), 1);
                $readingCount = max((int)($overview['reading_count'] ?? 0), 1);
                $missing      = (int)($overview['missing_count'] ?? 0);
                $peaks        = (int)($overview['peak_count'] ?? 0);
                $health       = max(0, 100 - min(100, $missing * 2));
                $coverage     = min(100, (int)round(($readingCount / max(1, $stationCount * 10)) * 100));
                $stability    = max(0, 100 - min(100, $peaks * 5));
            @endphp
            <article class="panel kpi-panel">
                <div class="panel-header">
                    <div>
                        <h2>Datakwaliteit uitgelegd</h2>
                        <p class="muted">Health, coverage en stability op basis van de huidige data.</p>
                    </div>
                </div>
                <div class="kpi-bar">
                    <div class="kpi-label-row">
                        <span>Data health</span>
                        <span id="kpiHealthValue">{{ $health }}%</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill green" id="kpiHealthBar" style="width: {{ $health }}%"></div>
                    </div>
                </div>
                <div class="kpi-bar">
                    <div class="kpi-label-row">
                        <span>Coverage</span>
                        <span id="kpiCoverageValue">{{ $coverage }}%</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill blue" id="kpiCoverageBar" style="width: {{ $coverage }}%"></div>
                    </div>
                </div>
                <div class="kpi-bar">
                    <div class="kpi-label-row">
                        <span>Stability</span>
                        <span id="kpiStabilityValue">{{ $stability }}%</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill blue" id="kpiStabilityBar" style="width: {{ $stability }}%"></div>
                    </div>
                </div>
            </article>

            {{-- Laatste metingen tabel --}}
            <article class="panel table-panel span-2">
                <div class="panel-header">
                    <div>
                        <h2>Laatste metingen</h2>
                        <p class="muted">Per station de nieuwste meting met temperatuur, wind, zicht en neerslag.</p>
                    </div>
                </div>
                <table class="data-table compact-table">
                    <thead>
                        <tr>
                            <th>Station</th>
                            <th>Locatie</th>
                            <th>Moment</th>
                            <th>Temp</th>
                            <th>Dauwpunt</th>
                            <th>Wind</th>
                            <th>Zicht</th>
                            <th>Neerslag</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="latestReadingsBody">
                        @foreach ($latest_readings as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->location_label ?? 'Onbekend' }}</td>
                            <td>{{ $row->measured_at }} UTC</td>
                            <td>{{ $row->temp ?? '-' }}</td>
                            <td>{{ $row->dewp ?? '-' }}</td>
                            <td>{{ $row->wdsp ?? '-' }}</td>
                            <td>{{ $row->visib ?? '-' }}</td>
                            <td>{{ $row->prcp ?? '-' }}</td>
                            <td>
                                @if ((int)($row->has_missing_data ?? 0) === 1)
                                    <span class="status-badge warning">Missing data</span>
                                @elseif ((int)($row->is_temp_peak ?? 0) === 1)
                                    <span class="status-badge info">Peak</span>
                                @else
                                    <span class="status-badge success">OK</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </article>

        </section>
    </section>

    {{-- ===== TAB: STATIONS ===== --}}
    <section class="tab-panel" id="stationsTab">
        <article class="panel">
            <div class="panel-header">
                <div>
                    <h2>Stations en weerstations</h2>
                    <p class="muted">Overzicht van alle weerstations met hun locatie en meetstatistieken.</p>
                </div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Station</th>
                        <th>Locatie</th>
                        <th>Co&ouml;rdinaten</th>
                        <th>Laatst gemeten</th>
                        <th>Temp</th>
                        <th>Gem. temp</th>
                        <th>Metingen</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="stationsTableBody">
                    @foreach ($stations as $station)
                    <tr>
                        <td>{{ $station->stn }}</td>
                        <td>{{ $station->location_label ?? 'Onbekend' }}</td>
                        <td>{{ $station->lat !== null ? $station->lat . ', ' . $station->lon : '-' }}</td>
                        <td>{{ $station->measured_at ? $station->measured_at . ' UTC' : '-' }}</td>
                        <td>{{ $station->temp ?? '-' }}</td>
                        <td>{{ $station->avg_temp ?? '-' }}</td>
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
        </article>
    </section>

    {{-- ===== TAB: KWALITEIT & CONTROLES ===== --}}
    <section class="tab-panel" id="qualityTab">
        <section class="content-grid quality-grid">

            {{-- Signaleringen --}}
            <article class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Laatste signaleringen</h2>
                        <p class="muted">Recente gevallen van ontbrekende data of piekmetingen.</p>
                    </div>
                </div>
                <table class="data-table compact-table">
                    <thead>
                        <tr>
                            <th>Station</th>
                            <th>Moment</th>
                            <th>Temp</th>
                            <th>Signalering</th>
                        </tr>
                    </thead>
                    <tbody id="flaggedReadingsBody">
                        @foreach ($flagged_readings as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->measured_at }} UTC</td>
                            <td>{{ $row->temp ?? '-' }}</td>
                            <td>
                                @if ((int)($row->has_missing_data ?? 0) === 1)
                                    <span class="status-badge warning">Missing data</span>
                                @else
                                    <span class="status-badge info">Temp peak</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </article>

            {{-- Correcties --}}
            <article class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Gecorrigeerde waarden</h2>
                        <p class="muted">Correctielog met oorspronkelijke en gecorrigeerde temperatuurwaarden.</p>
                    </div>
                </div>
                <table class="data-table compact-table">
                    <thead>
                        <tr>
                            <th>Station</th>
                            <th>Veld</th>
                            <th>Reden</th>
                            <th>Oorspronkelijk</th>
                            <th>Gecorrigeerd</th>
                            <th>Moment</th>
                        </tr>
                    </thead>
                    <tbody id="correctionsBody">
                        @foreach ($recent_corrections as $row)
                        <tr>
                            <td>{{ $row->stn }}</td>
                            <td>{{ $row->field }}</td>
                            <td>{{ $row->reason }}</td>
                            <td>{{ $row->original_value ?? '-' }}</td>
                            <td>{{ $row->corrected_value ?? '-' }}</td>
                            <td>{{ $row->created_at }} UTC</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </article>

        </section>
        allusers();

    </section>

</main>

{{-- Gegevens doorgeven aan Chart.js --}}
@php
$bootstrapData = [
    'overview'           => $overview,
    'top_stations'       => $top_stations,
    'chart_points'       => $chart_points,
    'latest_readings'    => $latest_readings,
    'stations'           => $stations,
    'flagged_readings'   => $flagged_readings,
    'recent_corrections' => $recent_corrections,
];
@endphp
<script>
window.dashboardBootstrap = {!! json_encode($bootstrapData) !!};
</script>
<script src="/assets/app.js"></script>

</body>
</html>
