<?php require __DIR__ . '/../shared/partials.php'; ?>
<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title>Abonnementen - IWA Dashboard</title><link rel="stylesheet" href="/assets/styles.css"></head><body>
<?php render_brand_header('Abonnementen & klanttoegang', 'Abonnementenoverzicht', 'Deze sectie laat de commerciële dataset uit de importlaag zien: welke bedrijven gekoppeld zijn, welk aanbod actief is en welke stations per abonnement beschikbaar zijn.'); ?>
<div class="header-actions"><a class="secondary-button" href="/">Terug naar dashboard</a></div></header>
<?php render_nav('subscriptions'); ?>
<main class="dashboard-shell">
<section class="summary-grid subscription-summary-grid">
    <article class="summary-card"><span class="summary-label">Abonnementen totaal</span><strong class="summary-value"><?= e((string)($summary['subscription_count'] ?? 0)) ?></strong><span class="summary-subtext">Volledige importset die nu zichtbaar is in de applicatie.</span></article>
    <article class="summary-card"><span class="summary-label">Actief</span><strong class="summary-value"><?= e((string)($summary['active_count'] ?? 0)) ?></strong><span class="summary-subtext">Abonnementen zonder einddatum of met een einddatum in de toekomst.</span></article>
    <article class="summary-card"><span class="summary-label">Aanbodtypes</span><strong class="summary-value"><?= e((string)($summary['type_count'] ?? 0)) ?></strong><span class="summary-subtext">Beschikbare abonnementsvormen voor verkoop en beheer.</span></article>
    <article class="summary-card"><span class="summary-label">Gekoppelde stations</span><strong class="summary-value"><?= e((string)($summary['total_station_links'] ?? 0)) ?></strong><span class="summary-subtext">Totaal aantal station-koppelingen over alle abonnementen heen.</span></article>
</section>

<section class="quick-links-grid quick-links-grid-3">
    <a class="quick-link-card" href="/subscription-types"><strong>Bekijk aanbod</strong><span>Zie alle abonnementsvormen met prijs, frequentie en aangesloten klanten.</span></a>
    <a class="quick-link-card" href="/contracts"><strong>Contracten</strong><span>Open de contractlaag voor REST-API gebruik, looptijd en endpoint-activiteit.</span></a>
    <a class="quick-link-card" href="/companies"><strong>Bedrijven</strong><span>Bekijk alle bedrijven en contactpersonen uit de geïmporteerde commerciële dataset.</span></a>
</section>

<article class="panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Alle abonnementen</h2>
            <p class="muted">Hier zie je per klant welk abonnement actief is, wat de looptijd is, hoeveel stations gekoppeld zijn en welk token bij de REST-laag hoort.</p>
        </div>
        <div class="header-badges">
            <span class="info-pill">Omzetindicatie: € <?= e(number_format((float)($summary['total_revenue'] ?? 0), 2, ',', '.')) ?></span>
            <span class="info-pill muted-pill">Data uit import + runtime koppelingen</span>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table data-table-comfortable">
            <thead><tr><th>Identifier</th><th>Bedrijf</th><th>Type</th><th>Status</th><th>Looptijd</th><th>Prijs</th><th>Stations</th><th>Token</th></tr></thead>
            <tbody><?php foreach ($subscriptions as $subscription): ?>
                <?php $isActive = empty($subscription['end_date']) || (string)$subscription['end_date'] >= gmdate('Y-m-d'); ?>
                <tr>
                    <td>
                        <a href="/subscriptions/<?= e($subscription['identifier']) ?>"><?= e($subscription['identifier']) ?></a>
                        <div class="table-subtext"><?= e((string)($subscription['notes'] ?? 'Geen extra notitie')) ?></div>
                    </td>
                    <td>
                        <?= e($subscription['company']['name'] ?? '-') ?>
                        <div class="table-subtext"><?= e($subscription['company']['city'] ?? '-') ?></div>
                    </td>
                    <td>
                        <a href="/subscription-types/<?= e((string)($subscription['type']['id'] ?? 0)) ?>"><?= e($subscription['type']['name'] ?? '-') ?></a>
                        <div class="table-subtext"><?= e(($subscription['type']['continuous'] ?? 0) ? 'Continu' : (($subscription['type']['frequency_in_hours'] ?? null) ? 'Elke '.$subscription['type']['frequency_in_hours'].' uur' : 'Elke '.$subscription['type']['frequency_in_days'].' dag(en)')) ?></div>
                    </td>
                    <td><span class="status-badge <?= $isActive ? 'success' : 'warning' ?>"><?= $isActive ? 'Actief' : 'Verlopen' ?></span></td>
                    <td><?= e((string)($subscription['start_date'] ?? '-')) ?><div class="table-subtext">t/m <?= e((string)($subscription['end_date'] ?? 'Doorlopend')) ?></div></td>
                    <td>€ <?= e(number_format((float)$subscription['price'], 2, ',', '.')) ?></td>
                    <td><span class="table-emphasis"><?= e((string)$subscription['station_count']) ?></span></td>
                    <td><code><?= e(substr((string)$subscription['token'], 0, 8)) ?>…</code></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table>
    </div>
</article>

<article class="panel">
    <div class="panel-header"><div><h2>Aanbod in het kort</h2><p class="muted">Compact overzicht van het aanbod. Klik op een kaart om de detailpagina en wijzigopties te openen.</p></div></div>
    <div class="story-grid offer-grid"><?php foreach ($types as $type): ?><a class="story-card offer-card" href="/subscription-types/<?= e((string)$type['id']) ?>"><div class="story-meta-row"><span class="story-chip">€ <?= e(number_format((float)$type['price_per_station'],2,',','.')) ?></span><span class="story-chip neutral"><?= e(($type['continuous'] ?? 0) ? 'Continu' : (($type['frequency_in_hours'] ?? null) ? 'Per '.$type['frequency_in_hours'].' uur' : 'Per '.$type['frequency_in_days'].' dag(en)')) ?></span></div><h3><?= e($type['name']) ?></h3><p class="muted"><?= e($type['description']) ?></p></a><?php endforeach; ?></div>
</article>
</main></body></html>
