{{-- Landingspagina voor alle IWA-medewerkers. --}}
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IWA &mdash; Landingspagina</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_KEY') }}"></script>
</head>
<body>

{{-- ===== HEADER ===== --}}
<header class="app-header">
    <div class="brand-block">
        <img class="iwa-logo" src="/assets/iwa-logo.png" alt="IWA logo">
        <div>
            <p class="eyebrow">Internationale Weer Agentschap</p>
            <h1>Operationeel Overzicht</h1>
            <p class="header-subtitle">
                Welkom, {{ $displayName }}
                &middot;
                {{ now()->isoFormat('dddd D MMMM YYYY') }}
            </p>
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

{{-- ===== NAVIGATIE ===== --}}
<nav class="page-nav">
    <a class="active" href="{{ route('dashboard') }}">Dashboard</a>
    <a href="{{ route('stations.index') }}">Stations</a>
    <a href="{{ route('subscriptions.index') }}">Abonnementen</a>
    <a href="{{ route('subscription-types.index') }}">Aanbod</a>
    <a href="{{ route('contracts.index') }}">Contracten</a>
    <a href="{{ route('companies.index') }}">Bedrijven</a>
</nav>

{{-- ===== HOOFD CONTENT ===== --}}
<main class="dashboard-shell">

    {{-- ===== METRICS RIJ ===== --}}
    <section class="metrics-grid">

        <div class="metric-card">
            <div class="metric-icon">◎</div>
            <div>
                <span class="metric-label">Stations online</span>
                <strong class="metric-value">{{ $overview['station_count'] ?? 0 }}</strong>
                <span class="metric-change neutral">Geregistreerde weerstations</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">⊞</div>
            <div>
                <span class="metric-label">Metingen vandaag</span>
                <strong class="metric-value">{{ number_format($overview['readings_today'] ?? 0) }}</strong>
                <span class="metric-change positive">Ontvangen op {{ now()->format('d-m-Y') }}</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">◈</div>
            <div>
                <span class="metric-label">Actieve abonnementen</span>
                <strong class="metric-value">{{ $overview['active_subscriptions'] ?? 0 }}</strong>
                <span class="metric-change positive">Lopende contracten</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">⌁</div>
            <div>
                <span class="metric-label">Datakwaliteit</span>
                <strong class="metric-value">{{ $overview['quality_pct'] ?? 100 }}%</strong>
                @php $qp = (int)($overview['quality_pct'] ?? 100); @endphp
                <span class="metric-change {{ $qp >= 90 ? 'positive' : ($qp >= 70 ? 'warning' : 'negative') }}">
                    {{ $overview['missing_count'] ?? 0 }} ontbrekend &middot; {{ $overview['peak_count'] ?? 0 }} pieken
                </span>
            </div>
        </div>

    </section>

    {{-- ===== TAB: OVERZICHT ===== --}}
    <section class="tab-panel active" id="overviewTab">
        <section class="content-grid">

            {{-- Stations kaart per land --}}
            <article class="panel map-panel span-2">
                <div class="panel-header">
                    <div>
                        <h2>Stations per land</h2>
                        <p class="muted">Klik op een pin om het aantal stations in dit land te zien.</p>
                    </div>
                </div>
                <div id="stationsMap" style="width: 100%; height: 400px; border-radius: 8px;"></div>
            </article>

            {{-- Ontbrekende velden statistiek --}}
            <article class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Meest voorkomende ontbrekende velden</h2>
                    </div>
                </div>
                <table class="data-table compact-table">
                    <thead>
                        <tr>
                            <th>Veld</th>
                            <th>Aantal ontbrekingen</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($missing_fields_stats as $field)
                        <tr>
                            <td><strong>{{ $field->field_name }}</strong></td>
                            <td>{{ number_format($field->missing_count) }}</td>
                            <td>
                                @php
                                    $percentage = ($field->missing_count / max(1, $field->total_count)) * 100;
                                @endphp
                                <span class="status-badge {{ $percentage > 20 ? 'warning' : 'success' }}">
                                    {{ number_format($percentage, 1) }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="empty-row">Geen ontbrekende velden gevonden.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </article>

            {{-- Station met meeste missing fields --}}
            <article class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Stations met meeste ontbrekende datavelden</h2>
                    </div>
                </div>
                <table class="data-table compact-table">
                    <thead>
                        <tr>
                            <th>Plaats</th>
                            <th>Land</th>
                            <th>Aantal ontbrekingen</th>
                            <th>Metingen totaal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stations_most_missing as $station)
                        <tr>
                            <td><strong>{{ $station->location_label }}</strong></td>
                            <td>{{ $station->country_name }}</td>
                            <td><span class="status-badge warning">{{ number_format($station->missing_count) }}</span></td>
                            <td>{{ number_format($station->reading_count) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="empty-row">Geen data beschikbaar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </article>


        </section>
    </section>

</main>

{{-- ===== DATA DOORGEVEN AAN JS ===== --}}
@php
$bootstrapData = [
    'overview'           => $overview,
    'stationsByCountry'  => $stations_by_country,
];
@endphp
<script>
window.dashboardBootstrap = {!! json_encode($bootstrapData) !!};
</script>
<script>
// Google Maps initialisatie
let map;

function initMap() {
    const mapElement = document.getElementById('stationsMap');
    if (!mapElement) {
        console.error('stationsMap element niet gevonden');
        return;
    }
    
    map = new google.maps.Map(mapElement, {
        zoom: 4,
        center: { lat: 54, lng: 15 }  // Europa (Polen/Duitsland)
    });
    
    const stationsByCountry = window.dashboardBootstrap?.stationsByCountry || [];
    console.log('Stations by country:', stationsByCountry);
    
    if (!stationsByCountry || stationsByCountry.length === 0) {
        console.warn('Geen stations per land gevonden');
        return;
    }
    
    let markersAdded = 0;
    const infoWindows = [];
    
    stationsByCountry.forEach((country, index) => {
        const lat = parseFloat(country.lat);
        const lng = parseFloat(country.lng);
        const stationCount = country.station_count;
        const countryName = country.country_name;
        
        if (isNaN(lat) || isNaN(lng)) {
            console.warn(`Skip country ${countryName}: lat=${country.lat}, lng=${country.lng}`);
            return;
        }
        
        const marker = new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: map,
            title: countryName
        });
        markersAdded++;
        
        const infoWindow = new google.maps.InfoWindow({
            content: `<div style="padding: 8px; font-family: Arial;">
                <strong>${countryName}</strong><br>
                <span>${stationCount} station(s)</span>
            </div>`
        });
        
        marker.addListener('click', () => {
            infoWindows.forEach(iw => iw.close());
            infoWindow.open(map, marker);
            infoWindows.length = 0;
            infoWindows.push(infoWindow);
        });
    });
    
    console.log(`${markersAdded} markers toegevoegd aan kaart`);
}

// Initialiseer kaart wanneer DOM geladen is
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded, initMap wordt aangeroepen');
    initMap();
});

// Tab functionaliteit
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tabTarget = this.getAttribute('data-tab-target');
        
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
        
        this.classList.add('active');
        document.getElementById(tabTarget).classList.add('active');
    });
});
</script>
<script src="/assets/app.js"></script>

</body>
</html>
