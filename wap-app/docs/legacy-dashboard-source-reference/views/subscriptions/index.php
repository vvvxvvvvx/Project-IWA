<?php require __DIR__ . '/../shared/partials.php'; ?>
<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title>Abonnementen - IWA Dashboard</title><link rel="stylesheet" href="/assets/styles.css"></head><body>
<?php render_brand_header('Abonnementen & klanttoegang', 'Abonnementenoverzicht', 'Beheer lopende abonnementen, tokens en gekoppelde stations vanuit één pagina.'); ?>
<?php render_header_actions('<a class="secondary-button" href="/">Terug naar dashboard</a>'); ?>
<?php render_nav('subscriptions'); ?>
<?php render_flash(); ?>
<main class="dashboard-shell">
<section class="summary-grid subscription-summary-grid">
    <article class="summary-card"><span class="summary-label">Abonnementen totaal</span><strong class="summary-value"><?= e((string)($summary['subscription_count'] ?? 0)) ?></strong><span class="summary-subtext">Resultaat na huidige filters.</span></article>
    <article class="summary-card"><span class="summary-label">Actief</span><strong class="summary-value"><?= e((string)($summary['active_count'] ?? 0)) ?></strong><span class="summary-subtext">Abonnementen zonder einddatum of nog binnen de looptijd.</span></article>
    <article class="summary-card"><span class="summary-label">Aanbodtypes</span><strong class="summary-value"><?= e((string)($summary['type_count'] ?? 0)) ?></strong><span class="summary-subtext">Beschikbare abonnementsvormen voor beheer.</span></article>
    <article class="summary-card"><span class="summary-label">Gekoppelde stations</span><strong class="summary-value"><?= e((string)($summary['total_station_links'] ?? 0)) ?></strong><span class="summary-subtext">Totaal aantal station-koppelingen.</span></article>
</section>

<section class="quick-links-grid">
    <a class="quick-link-card" href="/subscription-types"><strong>Bekijk aanbod</strong><span>Beheer abonnementtypes en prijzen.</span></a>
    <a class="quick-link-card" href="/contracts"><strong>Contracten</strong><span>Bekijk contractlaag voor REST-API gebruik en looptijd.</span></a>
    <a class="quick-link-card" href="/companies"><strong>Bedrijven</strong><span>Open bedrijfsgegevens en contactpersonen.</span></a>
</section>

<article class="panel">
    <div class="panel-header panel-header-stack">
        <div><h2>Alle abonnementen</h2><p class="muted">Implementeert overzicht, zoeken, statusfilter en beheer voor de user stories rond abonnementen.</p></div>
        <div class="header-badges"><span class="info-pill">Omzetindicatie: € <?= e(number_format((float)($summary['total_revenue'] ?? 0), 2, ',', '.')) ?></span></div>
    </div>
    <form class="filter-form" method="get" action="/subscriptions">
        <label>Zoeken<input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Zoek op identifier, bedrijf of type"></label>
        <label>Status<select name="status"><option value="all" <?= (($filters['status'] ?? 'all') === 'all') ? 'selected' : '' ?>>Alle statussen</option><option value="actief" <?= (($filters['status'] ?? '') === 'actief') ? 'selected' : '' ?>>Actief</option><option value="verlopen" <?= (($filters['status'] ?? '') === 'verlopen') ? 'selected' : '' ?>>Verlopen</option></select></label>
        <div class="action-row"><button class="primary-button" type="submit">Filter toepassen</button><a class="secondary-button" href="/subscriptions">Reset</a></div>
    </form>
    <div class="table-wrapper">
        <table class="data-table data-table-comfortable">
            <thead><tr><th>Identifier</th><th>Bedrijf</th><th>Type</th><th>Status</th><th>Looptijd</th><th>Prijs</th><th>Stations</th><th>Token</th></tr></thead>
            <tbody><?php foreach ($subscriptions as $subscription): ?>
                <?php $isActive = subscription_status($subscription) === 'actief'; ?>
                <tr>
                    <td><a href="/subscriptions/<?= e($subscription['identifier']) ?>"><?= e($subscription['identifier']) ?></a><div class="table-subtext"><?= e((string)($subscription['notes'] ?? 'Geen extra notitie')) ?></div></td>
                    <td><?= e($subscription['company']['name'] ?? '-') ?><div class="table-subtext"><?= e($subscription['company']['city'] ?? '-') ?></div></td>
                    <td><a href="/subscription-types/<?= e((string)($subscription['type']['id'] ?? 0)) ?>"><?= e($subscription['type']['name'] ?? '-') ?></a></td>
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

<?php if (current_role() === 'admin'): ?>
<article class="panel">
    <div class="panel-header"><div><h2>Nieuw abonnement</h2><p class="muted">Aanmaken van abonnementen inclusief gekoppelde stations.</p></div></div>
    <form class="form-grid" method="post" action="/subscriptions/create">
        <label>Identifier<input type="text" name="identifier" required></label>
        <label>Bedrijf<select name="company_id"><?php foreach ($companies as $company): ?><option value="<?= e((string)$company['id']) ?>"><?= e($company['name']) ?></option><?php endforeach; ?></select></label>
        <label>Type<select name="type_id"><?php foreach ($types as $type): ?><option value="<?= e((string)$type['id']) ?>"><?= e($type['name']) ?></option><?php endforeach; ?></select></label>
        <label>Prijs<input type="number" step="0.01" name="price"></label>
        <label>Startdatum<input type="date" name="start_date" value="<?= e(gmdate('Y-m-d')) ?>"></label>
        <label>Einddatum<input type="date" name="end_date"></label>
        <label class="span-2">Stations (komma of spatie gescheiden STN-codes)<input type="text" name="stations" placeholder="bijv. 62250, 62350, 62400"></label>
        <label class="span-2">Notities<textarea name="notes"></textarea></label>
        <div class="action-row"><button class="primary-button" type="submit">Abonnement aanmaken</button></div>
    </form>
</article>
<?php endif; ?>
</main></body></html>
