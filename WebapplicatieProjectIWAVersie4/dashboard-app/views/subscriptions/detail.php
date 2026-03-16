<?php require __DIR__ . '/../shared/partials.php'; ?>
<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title><?= e($subscription['identifier']) ?> - Abonnement</title><link rel="stylesheet" href="/assets/styles.css"></head><body>
<?php render_brand_header('Abonnement detail', $subscription['identifier'], 'Hier zie je op welk aanbod dit abonnement is gebaseerd, wie geabonneerd is, welke stations beschikbaar zijn en hoe de toegang werkt.'); ?>
<div class="header-actions"><a class="secondary-button" href="/subscriptions">Terug naar abonnementen</a></div></header>
<?php render_nav('subscriptions'); ?>
<main class="dashboard-shell station-shell">
<?php $isActive = empty($subscription['end_date']) || (string)$subscription['end_date'] >= gmdate('Y-m-d'); ?>
<section class="summary-grid subscription-summary-grid subscription-detail-summary-grid">
    <article class="summary-card"><span class="summary-label">Bedrijf</span><strong class="summary-value summary-value-text"><?= e($subscription['company']['name'] ?? '-') ?></strong><span class="summary-subtext"><?= e($subscription['company']['city'] ?? '-') ?></span></article>
    <article class="summary-card"><span class="summary-label">Status</span><strong class="summary-value"><?= $isActive ? 'Actief' : 'Verlopen' ?></strong><span class="summary-subtext">Loopt tot <?= e((string)($subscription['end_date'] ?? 'doorlopend')) ?></span></article>
    <article class="summary-card"><span class="summary-label">Prijs</span><strong class="summary-value">€ <?= e(number_format((float)$subscription['price'], 2, ',', '.')) ?></strong><span class="summary-subtext">Type: <?= e($subscription['type']['name'] ?? '-') ?></span></article>
    <article class="summary-card"><span class="summary-label">Stations</span><strong class="summary-value"><?= e((string)$subscription['station_count']) ?></strong><span class="summary-subtext">Beschikbaar via dashboard en REST-API.</span></article>
</section>

<section class="panel">
    <div class="panel-header panel-header-stack">
        <div><h2>Abonnementsoverzicht</h2><p class="muted">Basisgegevens, commerciële notities en tokeninformatie voor dit abonnement.</p></div>
        <span class="status-badge <?= $isActive ? 'success' : 'warning' ?>"><?= $isActive ? 'Actief' : 'Verlopen' ?></span>
    </div>
    <div class="details-grid details-grid-2">
        <div><strong>Bedrijf</strong><div><?= e($subscription['company']['name'] ?? '-') ?></div></div>
        <div><strong>Abonnementtype</strong><div><a href="/subscription-types/<?= e((string)($subscription['type']['id'] ?? 0)) ?>"><?= e($subscription['type']['name'] ?? '-') ?></a></div></div>
        <div><strong>Prijs</strong><div>€ <?= e(number_format((float)$subscription['price'], 2, ',', '.')) ?></div></div>
        <div><strong>Periode</strong><div><?= e((string)$subscription['start_date']) ?> t/m <?= e((string)($subscription['end_date'] ?? 'Doorlopend')) ?></div></div>
        <div class="span-2"><strong>Beschrijving type</strong><div><?= e($subscription['type']['description'] ?? '-') ?></div></div>
        <div class="span-2"><strong>Token</strong><div><code class="inline-code-block"><?= e($subscription['token']) ?></code></div></div>
        <div class="span-2"><strong>Notities</strong><div><?= e((string)($subscription['notes'] ?? 'Geen notities vastgelegd.')) ?></div></div>
    </div>
    <?php if (current_role() === 'admin'): ?><form class="form-grid" method="post" action="/subscriptions/<?= e($subscription['identifier']) ?>/update"><label>Prijs<input type="number" step="0.01" name="price" value="<?= e((string)$subscription['price']) ?>"></label><label>Einddatum<input type="date" name="end_date" value="<?= e((string)($subscription['end_date'] ?? '')) ?>"></label><label>Abonnementtype<select name="type_id"><?php foreach ($types as $type): ?><option value="<?= e((string)$type['id']) ?>" <?= ((int)$type['id']===(int)$subscription['type_id'])?'selected':'' ?>><?= e($type['name']) ?></option><?php endforeach; ?></select></label><label class="span-2">Notities<textarea name="notes"><?= e((string)($subscription['notes'] ?? '')) ?></textarea></label><div class="inline-form"><button class="primary-button" type="submit">Abonnement wijzigen</button></div></form><form method="post" action="/subscriptions/<?= e($subscription['identifier']) ?>/regenerate-token" class="inline-form"><button class="secondary-button" type="submit">Nieuw token genereren</button></form><?php endif; ?>
</section>
<section class="panel">
    <div class="panel-header"><div><h2>Contract & REST-API</h2><p class="muted">Voor contractinformatie en REST-API bronverkeer is er ook een aparte contractpagina.</p></div></div><div class="inline-form"><a class="primary-button" href="/contracts/<?= e($subscription['identifier']) ?>">Open contractpagina</a></div><div class="api-endpoint-list"><div><code>GET /IWA/abonnement/<?= e($subscription['identifier']) ?>/stations?token=<?= e($subscription['token']) ?></code></div><div><code>GET /IWA/abonnement/<?= e($subscription['identifier']) ?>/station/{naam}?token=<?= e($subscription['token']) ?></code></div><div><code>GET /IWA/abonnement/<?= e($subscription['identifier']) ?>/files?token=<?= e($subscription['token']) ?></code></div></div></section>
<section class="panel"><div class="panel-header"><div><h2>Beschikbare stations</h2><p class="muted">Stations die binnen dit abonnement ontsloten mogen worden.</p></div></div><div class="table-wrapper"><table class="data-table compact-table data-table-comfortable"><thead><tr><th>STN</th><th>Naam</th><th>Locatie</th><th>Breedtegraad</th><th>Lengtegraad</th></tr></thead><tbody><?php foreach ($subscription['stations'] as $station): ?><tr><td><a href="/stations/<?= e($station['stn']) ?>"><?= e($station['stn']) ?></a></td><td><?= e($station['name'] ?? ('Weerstation '.$station['stn'])) ?></td><td><?= e($station['location_label'] ?? 'Onbekend') ?></td><td><?= e((string)($station['lat'] ?? '-')) ?></td><td><?= e((string)($station['lon'] ?? '-')) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="panel"><div class="panel-header"><div><h2>Endpoint-activiteit</h2><p class="muted">Laatste geregistreerde API-aanroepen voor dit abonnement.</p></div></div><div class="table-wrapper"><table class="data-table compact-table data-table-comfortable"><thead><tr><th>Datum</th><th>Tijd</th><th>Endpoint</th><th>Authorized</th><th>Bestanden</th></tr></thead><tbody><?php foreach ($activity as $row): ?><tr><td><?= e((string)$row['activity_date']) ?></td><td><?= e((string)$row['activity_time']) ?></td><td><?= e((string)$row['endpoint_used']) ?></td><td><?= ((int)($row['authorized'] ?? 0) === 1) ? 'Ja' : 'Nee' ?></td><td><?= e((string)($row['files_downloaded'] ?? 0)) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</main></body></html>
