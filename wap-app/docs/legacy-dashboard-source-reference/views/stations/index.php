<?php require __DIR__ . '/../shared/partials.php'; ?>
<!DOCTYPE html>
<html lang="nl"><head><meta charset="UTF-8"><title>Stations - IWA Dashboard</title><link rel="stylesheet" href="/assets/styles.css"></head><body>
<?php render_brand_header('Analyse & monitoring', 'Stationsoverzicht', 'Duidelijke lijst van alle weerstations met locatie, laatste meting en status.'); ?>
<?php render_header_actions('<a class="secondary-button" href="/">Terug naar dashboard</a>'); ?>
<?php render_nav('stations'); ?>
<?php render_flash(); ?>
<main class="dashboard-shell">
<section class="summary-grid">
    <article class="summary-card"><span class="summary-label">Totaal zichtbaar</span><strong class="summary-value"><?= e((string)count($stations)) ?></strong><span class="summary-subtext">Resultaat na filters en zoekopdracht.</span></article>
    <article class="summary-card"><span class="summary-label">OK</span><strong class="summary-value"><?= e((string)count(array_filter($stations, fn(array $station): bool => station_status($station) === 'ok'))) ?></strong><span class="summary-subtext">Stations zonder gedetecteerde afwijking.</span></article>
    <article class="summary-card"><span class="summary-label">Missing data</span><strong class="summary-value"><?= e((string)count(array_filter($stations, fn(array $station): bool => station_status($station) === 'missing'))) ?></strong><span class="summary-subtext">Stations met ontbrekende waarden.</span></article>
    <article class="summary-card"><span class="summary-label">Piekmetingen</span><strong class="summary-value"><?= e((string)count(array_filter($stations, fn(array $station): bool => station_status($station) === 'peak'))) ?></strong><span class="summary-subtext">Stations met opvallende temperatuurpiek.</span></article>
</section>
<article class="panel">
    <div class="panel-header panel-header-stack"><div><h2>Alle weerstations</h2><p class="muted">De tabel is opgeschoond zodat alleen de relevante informatie voor monitoring zichtbaar blijft.</p></div></div>
    <form class="filter-form" method="get" action="/stations">
        <label>Zoeken
            <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Zoek op STN, naam of locatie">
        </label>
        <label>Status
            <select name="status">
                <option value="all" <?= (($filters['status'] ?? 'all') === 'all') ? 'selected' : '' ?>>Alle statussen</option>
                <option value="ok" <?= (($filters['status'] ?? '') === 'ok') ? 'selected' : '' ?>>OK</option>
                <option value="missing" <?= (($filters['status'] ?? '') === 'missing') ? 'selected' : '' ?>>Missing data</option>
                <option value="peak" <?= (($filters['status'] ?? '') === 'peak') ? 'selected' : '' ?>>Piekmeting</option>
            </select>
        </label>
        <div class="action-row">
            <button class="primary-button" type="submit">Filter toepassen</button>
            <a class="secondary-button" href="/stations">Reset</a>
        </div>
    </form>
    <?php if ($stations === []): ?>
        <div class="empty-state">Geen stations gevonden voor de huidige filters.</div>
    <?php else: ?>
    <div class="table-wrapper">
    <table class="data-table data-table-comfortable"><thead><tr><th>STN</th><th>Naam</th><th>Locatie</th><th>Laatst gemeten</th><th>Temperatuur</th><th>Readings</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($stations as $station): ?>
    <tr>
        <td><a href="/stations/<?= e($station['stn']) ?>"><?= e($station['stn']) ?></a></td>
        <td><?= e($station['name']) ?></td>
        <td><?= e($station['location_label'] ?? 'Onbekend') ?></td>
        <td><?= e(format_datetime((string)($station['measured_at'] ?? ''))) ?></td>
        <td><?= e((string)($station['temp'] ?? '-')) ?></td>
        <td><?= e((string)($station['reading_count'] ?? 0)) ?></td>
        <td><span class="status-badge <?= station_status_badge_class($station) ?>"><?= e(station_status_label($station)) ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
    </div>
    <?php endif; ?>
</article></main></body></html>
