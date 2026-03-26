<?php require __DIR__ . '/../shared/partials.php'; ?>
<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title>Bedrijven - IWA Dashboard</title><link rel="stylesheet" href="/assets/styles.css"></head><body>
<?php render_brand_header('Klantenbeheer', 'Bedrijvenoverzicht', 'Overzicht van alle bedrijven zodat commerciële medewerkers en super users snel een relatie kunnen opzoeken.'); ?>
<?php render_header_actions('<a class="secondary-button" href="/">Terug naar dashboard</a>'); ?>
<?php render_nav('companies'); ?>
<?php render_flash(); ?>
<main class="dashboard-shell">
<article class="panel">
    <div class="panel-header panel-header-stack"><div><h2>Alle bedrijven</h2><p class="muted">Zoek snel een relatie op of voeg direct een nieuw bedrijf toe.</p></div></div>
    <form class="filter-form" method="get" action="/companies">
        <label>Zoeken
            <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Zoek op naam, stad, land of e-mail">
        </label>
        <div class="action-row"><button class="primary-button" type="submit">Zoeken</button><a class="secondary-button" href="/companies">Reset</a></div>
    </form>
    <div class="table-wrapper"><table class="data-table data-table-comfortable"><thead><tr><th>Naam</th><th>Stad</th><th>Land</th><th>E-mail</th></tr></thead><tbody><?php foreach ($companies as $company): ?><tr><td><a href="/companies/<?= e((string)$company['id']) ?>"><?= e($company['name']) ?></a></td><td><?= e($company['city']) ?></td><td><?= e($company['country_name']) ?></td><td><?= e($company['email']) ?></td></tr><?php endforeach; ?></tbody></table></div>
</article>
<?php if (current_role() === 'admin'): ?>
<article class="panel">
    <div class="panel-header"><div><h2>Nieuw bedrijf aanmaken</h2><p class="muted">Implementeert de user story voor bedrijven aanmaken en beheren.</p></div></div>
    <form class="form-grid" method="post" action="/companies/create">
        <label>Naam<input type="text" name="name" required></label>
        <label>E-mail<input type="email" name="email"></label>
        <label>Stad<input type="text" name="city"></label>
        <label>Landcode<input type="text" name="country_code" value="NL" maxlength="2"></label>
        <label>Straat<input type="text" name="street"></label>
        <label>Huisnummer<input type="text" name="number"></label>
        <label>Toevoeging<input type="text" name="number_additional"></label>
        <label>Postcode<input type="text" name="zip_code"></label>
        <div class="action-row"><button class="primary-button" type="submit">Bedrijf aanmaken</button></div>
    </form>
</article>
<?php endif; ?>
</main></body></html>
