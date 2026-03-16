<?php require __DIR__ . '/../shared/partials.php'; ?>
<!DOCTYPE html>
<html lang="nl"><head><meta charset="UTF-8"><title>Stations - IWA Dashboard</title><link rel="stylesheet" href="/assets/styles.css"></head><body>
<?php render_brand_header('Analyse & monitoring', 'Stationsoverzicht', 'Duidelijke lijst van alle weerstations met locatie, laatste meting en status.'); ?>
<div class="header-actions"><a class="secondary-button" href="/">Terug naar dashboard</a></div></header>
<?php render_nav('stations'); ?>
<main class="dashboard-shell">
<article class="panel">
<div class="panel-header"><div><h2>Alle weerstations</h2><p class="muted">Dit is de primaire stationspagina. Hier zie je welk station welke data stuurt, de laatst ontvangen temperatuur en of er missing data of piekmetingen zijn gedetecteerd.</p></div></div>
<table class="data-table"><thead><tr><th>STN</th><th>Naam</th><th>Locatie</th><th>Coördinaten</th><th>Laatst gemeten</th><th>Temp</th><th>Gem.</th><th>Zicht</th><th>Wind</th><th>Neerslag</th><th>Readings</th><th>Status</th></tr></thead><tbody>
<?php foreach ($stations as $station): ?>
<tr><td><a href="/stations/<?= e($station['stn']) ?>"><?= e($station['stn']) ?></a></td><td><?= e($station['name']) ?></td><td><?= e($station['location_label'] ?? 'Onbekend') ?></td><td><?= e(($station['lat'] ?? null) !== null ? ($station['lat'] . ', ' . $station['lon']) : '-') ?></td><td><?= e(format_datetime((string)($station['measured_at'] ?? ''))) ?></td><td><?= e((string)($station['temp'] ?? '-')) ?></td><td><?= e((string)($station['avg_temp'] ?? '-')) ?></td><td><?= e((string)($station['visib'] ?? '-')) ?></td><td><?= e((string)($station['wdsp'] ?? '-')) ?></td><td><?= e((string)($station['prcp'] ?? '-')) ?></td><td><?= e((string)($station['reading_count'] ?? 0)) ?></td><td><?php if ((int)($station['has_missing_data'] ?? 0)===1): ?><span class="status-badge warning">Missing</span><?php elseif ((int)($station['is_temp_peak'] ?? 0)===1): ?><span class="status-badge info">Peak</span><?php else: ?><span class="status-badge success">OK</span><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></article></main></body></html>
