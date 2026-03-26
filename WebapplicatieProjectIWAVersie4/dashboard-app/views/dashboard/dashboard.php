<?php require __DIR__ . '/../shared/partials.php'; ?>
<?php $role = $_SESSION['user_role'] ?? 'customer'; $displayName = $_SESSION['display_name'] ?? 'Gebruiker'; ?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>IWA Weerdashboard</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php render_brand_header('Internationale Weer Agentschap', 'Weerdashboard', 'Ingelogd als ' . $displayName . ' · Rol: ' . $role); ?>
<div class="header-actions"><div class="live-pill" id="liveIndicator">Live</div><form method="post" action="/logout"><button class="secondary-button" type="submit">Uitloggen</button></form></div></header>
<?php render_nav('dashboard'); ?>
<main class="dashboard-shell">
<section class="metrics-grid clickable-metrics">
<a class="metric-card metric-link" href="/insights/stations-online"><div class="metric-icon">◢</div><div><span class="metric-label">Stations online</span><strong class="metric-value" id="metricStationCount"><?= e((string) ($overview['station_count'] ?? 0)) ?></strong><span class="metric-change positive">Klik voor het volledige stationsoverzicht en statusdetails.</span></div></a>
<a class="metric-card metric-link" href="/insights/readings"><div class="metric-icon">▣</div><div><span class="metric-label">Readings</span><strong class="metric-value" id="metricReadingCount"><?= e((string) ($overview['reading_count'] ?? 0)) ?></strong><span class="metric-change positive">Klik om recente metingen en verwerking duidelijk te bekijken.</span></div></a>
<a class="metric-card metric-link" href="/insights/temperature"><div class="metric-icon">℃</div><div><span class="metric-label">Gemiddelde temperatuur</span><strong class="metric-value" id="metricAverageTemp"><?= e((string) ($overview['average_temp'] ?? '-')) ?></strong><span class="metric-change info">Klik voor uitleg van de trendgrafiek en minima/maxima.</span></div></a>
<a class="metric-card metric-link" href="/insights/data-quality"><div class="metric-icon">⌁</div><div><span class="metric-label">Datakwaliteit</span><strong class="metric-value"><span id="metricPeakCount"><?= e((string) ($overview['peak_count'] ?? 0)) ?></span> / <span id="metricMissingCount"><?= e((string) ($overview['missing_count'] ?? 0)) ?></span></strong><span class="metric-change warning">Klik voor correcties, oorspronkelijke waarden en signaleringen.</span></div></a>
</section>
<?php if ($role !== 'customer'): ?>
<section class="quick-links-grid">
<a class="quick-link-card" href="/subscriptions"><strong>Abonnementen</strong><span>Bestanden en abonnementen zoals aangeleverd via file-transfer, inclusief klanten, prijzen en tokens.</span></a>
<a class="quick-link-card" href="/subscription-types"><strong>Aanbod</strong><span>Bekijk welke abonnementen IWA aanbiedt, wie ze afneemt en wijzig type-informatie als admin.</span></a>
<a class="quick-link-card" href="/contracts"><strong>Contracten</strong><span>Losse contractpagina’s met REST-API broninformatie, looptijd en activiteit.</span></a>
<?php if ($role === 'admin'): ?><a class="quick-link-card" href="/companies"><strong>Bedrijven & contactpersonen</strong><span>Beheer relaties, contactpersonen en gekoppelde abonnementen vanuit één plek.</span></a><?php endif; ?>
</section>
<?php endif; ?>
<section class="tab-strip">
    <button class="tab-button active" type="button" data-tab-target="overviewTab">Overzicht</button>
    <button class="tab-button" type="button" data-tab-target="stationsTab">Stations</button>
    <button class="tab-button" type="button" data-tab-target="qualityTab">Registratie & controles</button>
</section>
<section class="tab-panel active" id="overviewTab">
    <section class="content-grid">
        <article class="panel chart-panel">
            <div class="panel-header"><div><h2>Originele vs gecorrigeerde temperatuur</h2><p class="muted">Ruwe temperatuur naast de gecorrigeerde waarde voor recente correcties.</p></div></div>
            <canvas id="temperatureChart" height="160"></canvas>
        </article>
        <article class="panel donut-panel">
            <div class="panel-header"><div><h2>Actiefste stations</h2><p class="muted">Stations met de meeste metingen.</p></div></div>
            <div class="donut-layout">
                <ul class="source-list" id="topStationsList"><?php foreach (($top_stations ?? []) as $station): ?><li><span class="source-name"><?= e((string)$station['name']) ?></span><span class="source-value"><?= e((string)$station['reading_count']) ?></span></li><?php endforeach; ?></ul>
                <div class="donut-wrapper"><canvas id="stationChart" height="180"></canvas></div>
            </div>
        </article>
        <?php
            $stationCount = max((int)($overview['station_count'] ?? 0), 1);
            $readingCount = max((int)($overview['reading_count'] ?? 0), 1);
            $missing = (int)($overview['missing_count'] ?? 0);
            $peaks = (int)($overview['peak_count'] ?? 0);
            $health = max(0, 100 - min(100, $missing * 2));
            $coverage = min(100, (int)round(($readingCount / max(1, $stationCount * 10)) * 100));
            $stability = max(0, 100 - min(100, $peaks * 5));
        ?>
        <article class="panel kpi-panel">
            <div class="panel-header"><div><h2>Datakwaliteit uitgelegd</h2><p class="muted">Health, coverage en stability op basis van de huidige data.</p></div></div>
            <div class="kpi-bar"><div class="kpi-label-row"><span>Data health</span><span id="kpiHealthValue"><?= e((string)$health) ?>%</span></div><div class="bar-track"><div class="bar-fill green" id="kpiHealthBar" style="width: <?= e((string)$health) ?>%"></div></div></div>
            <div class="kpi-bar"><div class="kpi-label-row"><span>Coverage</span><span id="kpiCoverageValue"><?= e((string)$coverage) ?>%</span></div><div class="bar-track"><div class="bar-fill blue" id="kpiCoverageBar" style="width: <?= e((string)$coverage) ?>%"></div></div></div>
            <div class="kpi-bar"><div class="kpi-label-row"><span>Stability</span><span id="kpiStabilityValue"><?= e((string)$stability) ?>%</span></div><div class="bar-track"><div class="bar-fill blue" id="kpiStabilityBar" style="width: <?= e((string)$stability) ?>%"></div></div></div>
        </article>
        <article class="panel table-panel span-2">
            <div class="panel-header"><div><h2>Laatste metingen</h2><p class="muted">Per station de nieuwste meting met temperatuur, wind, zicht en neerslag.</p></div></div>
            <table class="data-table compact-table">
                <thead><tr><th>Station</th><th>Locatie</th><th>Moment</th><th>Temp</th><th>Dauwpunt</th><th>Wind</th><th>Zicht</th><th>Neerslag</th><th>Status</th></tr></thead>
                <tbody id="latestReadingsBody"><?php foreach (($latest_readings ?? []) as $row): ?><tr><td><a href="/stations/<?= e((string)$row['stn']) ?>"><?= e((string)$row['name']) ?></a></td><td><?= e((string)($row['location_label'] ?? 'Onbekend')) ?></td><td><?= e(format_datetime((string)($row['measured_at'] ?? ''))) ?></td><td><?= e((string)($row['temp'] ?? '-')) ?></td><td><?= e((string)($row['dewp'] ?? '-')) ?></td><td><?= e((string)($row['wdsp'] ?? '-')) ?></td><td><?= e((string)($row['visib'] ?? '-')) ?></td><td><?= e((string)($row['prcp'] ?? '-')) ?></td><td><?php if ((int)($row['has_missing_data'] ?? 0) === 1): ?><span class="status-badge warning">Missing data</span><?php elseif ((int)($row['is_temp_peak'] ?? 0) === 1): ?><span class="status-badge info">Peak</span><?php else: ?><span class="status-badge success">OK</span><?php endif; ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </article>
    </section>
</section>
<section class="tab-panel" id="stationsTab">
    <article class="panel">
        <div class="panel-header panel-header-stack"><div><h2>Stations en weerstations</h2><p class="muted">Alleen de relevante gegevens voor monitoring blijven zichtbaar. Coördinaten, zicht, wind, neerslag en gemiddelde temperatuur zijn uit dit overzicht gehaald omdat ze de tabel onnodig breed en onrustig maakten.</p></div><div class="header-badges"><span class="info-pill">Zichtbaar: <span id="dashboardStationVisibleCount"><?= e((string)count($stations ?? [])) ?></span></span></div></div>
        <form class="filter-form" id="dashboardStationFilterForm">
            <label>Zoeken<input type="text" id="dashboardStationSearch" placeholder="Zoek op STN, naam of locatie"></label>
            <label>Status<select id="dashboardStationStatus"><option value="all">Alle statussen</option><option value="ok">OK</option><option value="missing">Missing data</option><option value="peak">Piekmeting</option></select></label>
            <div class="action-row"><button class="primary-button" type="submit">Filter toepassen</button><button class="secondary-button" id="dashboardStationReset" type="button">Reset</button></div>
        </form>
        <div class="table-wrapper">
            <table class="data-table data-table-comfortable">
                <thead><tr><th>STN</th><th>Naam</th><th>Locatie</th><th>Laatst gemeten</th><th>Temperatuur</th><th>Readings</th><th>Status</th></tr></thead>
                <tbody id="stationsTableBody"><?php foreach (($stations ?? []) as $station): ?><tr><td><a href="/stations/<?= e((string)$station['stn']) ?>"><?= e((string)$station['stn']) ?></a></td><td><?= e((string)$station['name']) ?></td><td><?= e((string)($station['location_label'] ?? 'Onbekend')) ?></td><td><?= e(format_datetime((string)($station['measured_at'] ?? ''))) ?></td><td><?= e((string)($station['temp'] ?? '-')) ?></td><td><?= e((string)($station['reading_count'] ?? 0)) ?></td><td><?php if ((int)($station['has_missing_data'] ?? 0) === 1): ?><span class="status-badge warning">Missing data</span><?php elseif ((int)($station['is_temp_peak'] ?? 0) === 1): ?><span class="status-badge info">Peak</span><?php else: ?><span class="status-badge success">OK</span><?php endif; ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </div>
    </article>
</section>
<?php if ($role !== 'customer'): ?>
<section class="tab-panel" id="qualityTab"><section class="quality-grid">
<article class="panel"><div class="panel-header"><div><h2>Laatste signaleringen</h2><p class="muted">Recente gevallen van ontbrekende data of piekmetingen.</p></div></div><table class="data-table compact-table"><thead><tr><th>Station</th><th>Moment</th><th>Temp</th><th>Signalering</th></tr></thead><tbody id="flaggedReadingsBody"><?php foreach (($flagged_readings ?? []) as $row): ?><tr><td><a href="/stations/<?= e((string)$row['stn']) ?>"><?= e((string)$row['name']) ?></a></td><td><?= e(format_datetime((string)($row['measured_at'] ?? ''))) ?></td><td><?= e((string)($row['temp'] ?? '-')) ?></td><td><?php if ((int)($row['has_missing_data'] ?? 0) === 1): ?><span class="status-badge warning">Missing data</span><?php else: ?><span class="status-badge info">Temp peak</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></article>
<article class="panel"><div class="panel-header"><div><h2>Gecorrigeerde waarden</h2><p class="muted">Correctielog met oorspronkelijke en gecorrigeerde temperatuurwaarden.</p></div></div><div class="two-col"><article class="panel nested-panel"><table class="data-table compact-table"><thead><tr><th>Station</th><th>Veld</th><th>Reden</th><th>Oorspronkelijk</th><th>Gecorrigeerd</th><th>Moment</th></tr></thead><tbody id="correctionsBody"><?php foreach (($recent_corrections ?? []) as $row): ?><tr><td><?= e((string)$row['stn']) ?></td><td><?= e((string)$row['field']) ?></td><td><?= e((string)$row['reason']) ?></td><td><?= e((string)($row['original_value'] ?? '-')) ?></td><td><?= e((string)($row['corrected_value'] ?? '-')) ?></td><td><?= e(format_datetime((string)$row['created_at'])) ?></td></tr><?php endforeach; ?></tbody></table></article><article class="panel nested-panel"><table class="data-table compact-table"><thead><tr><th>Station</th><th>Veld</th><th>Oorspronkelijk</th><th>Opgeslagen</th><th>Moment</th></tr></thead><tbody id="originalsBody"><?php foreach (($recent_originals ?? []) as $row): ?><tr><td><?= e((string)$row['stn']) ?></td><td><?= e((string)$row['field']) ?></td><td><?= e((string)($row['original_value'] ?? '-')) ?></td><td><?= e((string)($row['corrected_value'] ?? '-')) ?></td><td><?= e(format_datetime((string)$row['created_at'])) ?></td></tr><?php endforeach; ?></tbody></table></article></div></article>
</section></section>
<?php endif; ?>
</main>
<script>window.dashboardBootstrap = <?= json_encode(['chart_points'=>$chart_points,'top_stations'=>$top_stations,'overview'=>$overview,'latest_readings'=>$latest_readings,'stations'=>$stations,'latest_batches'=>$latest_batches,'flagged_readings'=>$flagged_readings,'recent_corrections'=>$recent_corrections ?? [],'recent_originals'=>$recent_originals ?? []], JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="/assets/app.js"></script>
</body>
</html>
