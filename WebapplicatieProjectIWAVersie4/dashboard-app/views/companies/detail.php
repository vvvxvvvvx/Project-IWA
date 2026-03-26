<?php require __DIR__ . '/../shared/partials.php'; ?>
<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title><?= e($company['name']) ?> - Bedrijf</title><link rel="stylesheet" href="/assets/styles.css"></head><body>
<?php render_brand_header('Bedrijf detail', $company['name'], 'Bedrijfsgegevens, contactpersonen en gekoppelde abonnementen.'); ?>
<?php render_header_actions('<a class="secondary-button" href="/companies">Terug naar bedrijven</a>'); ?>
<?php render_nav('companies'); ?>
<?php render_flash(); ?>
<main class="dashboard-shell">
<section class="panel"><div class="panel-header"><h2>Bedrijfsgegevens</h2><?php if (current_role() === 'admin'): ?><form method="post" action="/companies/<?= e((string)$company['id']) ?>/delete" onsubmit="return confirm('Weet je zeker dat je dit bedrijf wilt verwijderen?');"><button class="danger-button" type="submit">Verwijderen</button></form><?php endif; ?></div><div class="details-grid">
<div><strong>Stad</strong><div><?= e($company['city']) ?></div></div>
<div><strong>Adres</strong><div><?= e(trim(($company['street'] ?? '') . ' ' . ($company['number'] ?? '') . ' ' . ($company['number_additional'] ?? ''))) ?></div></div>
<div><strong>Postcode</strong><div><?= e($company['zip_code']) ?></div></div>
<div><strong>Land</strong><div><?= e($company['country_name']) ?></div></div>
<div><strong>E-mail</strong><div><?= e($company['email']) ?></div></div>
</div>
<?php if (current_role() === 'admin'): ?>
<form class="form-grid" method="post" action="/companies/<?= e((string)$company['id']) ?>/update">
<label>Naam<input type="text" name="name" value="<?= e($company['name']) ?>"></label>
<label>E-mail<input type="email" name="email" value="<?= e($company['email']) ?>"></label>
<label>Stad<input type="text" name="city" value="<?= e($company['city']) ?>"></label>
<label>Landcode<input type="text" name="country_code" value="<?= e($company['country_code']) ?>" maxlength="2"></label>
<label>Straat<input type="text" name="street" value="<?= e($company['street']) ?>"></label>
<label>Huisnummer<input type="text" name="number" value="<?= e((string)$company['number']) ?>"></label>
<label>Toevoeging<input type="text" name="number_additional" value="<?= e((string)($company['number_additional'] ?? '')) ?>"></label>
<label>Postcode<input type="text" name="zip_code" value="<?= e($company['zip_code']) ?>"></label>
<div class="action-row"><button class="primary-button" type="submit">Bedrijf opslaan</button></div>
</form>
<?php endif; ?>
</section>
<section class="panel"><div class="panel-header"><h2>Contactpersonen</h2></div>
<?php if ($contacts === []): ?><div class="empty-state">Nog geen contactpersonen vastgelegd.</div><?php else: ?>
<div class="table-wrapper"><table class="data-table compact-table data-table-comfortable"><thead><tr><th>Naam</th><th>Functie</th><th>E-mail</th><th>Telefoon</th><?php if (current_role() === 'admin'): ?><th>Acties</th><?php endif; ?></tr></thead><tbody><?php foreach ($contacts as $contact): ?><tr>
<td><?= e(trim(($contact['title'] ?? '') . ' ' . ($contact['first_name'] ?? '') . ' ' . ($contact['prefix'] ?? '') . ' ' . ($contact['name'] ?? ''))) ?></td>
<td><?= e($contact['function']) ?></td><td><?= e($contact['email']) ?></td><td><?= e($contact['phone']) ?></td>
<?php if (current_role() === 'admin'): ?><td><details><summary class="secondary-button">Bewerken</summary><form class="stack-form" method="post" action="/companies/<?= e((string)$company['id']) ?>/contacts/<?= e((string)$contact['id']) ?>/update"><label>Achternaam<input type="text" name="name" value="<?= e($contact['name']) ?>"></label><label>Voornaam<input type="text" name="first_name" value="<?= e($contact['first_name']) ?>"></label><label>Initialen<input type="text" name="initials" value="<?= e((string)($contact['initials'] ?? '')) ?>"></label><label>Tussenvoegsel<input type="text" name="prefix" value="<?= e((string)($contact['prefix'] ?? '')) ?>"></label><label>Titel<input type="text" name="title" value="<?= e($contact['title']) ?>"></label><label>Functie<input type="text" name="function" value="<?= e($contact['function']) ?>"></label><label>E-mail<input type="email" name="email" value="<?= e($contact['email']) ?>"></label><label>Telefoon<input type="text" name="phone" value="<?= e($contact['phone']) ?>"></label><div class="action-row"><button class="primary-button" type="submit">Opslaan</button></div></form><form method="post" action="/companies/<?= e((string)$company['id']) ?>/contacts/<?= e((string)$contact['id']) ?>/delete" onsubmit="return confirm('Contactpersoon verwijderen?');"><button class="danger-button" type="submit">Verwijderen</button></form></details></td><?php endif; ?>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php if (current_role() === 'admin'): ?>
<form class="form-grid" method="post" action="/companies/<?= e((string)$company['id']) ?>/contacts/create">
<label>Achternaam<input type="text" name="name" required></label>
<label>Voornaam<input type="text" name="first_name" required></label>
<label>Initialen<input type="text" name="initials"></label>
<label>Tussenvoegsel<input type="text" name="prefix"></label>
<label>Titel<input type="text" name="title"></label>
<label>Functie<input type="text" name="function"></label>
<label>E-mail<input type="email" name="email"></label>
<label>Telefoon<input type="text" name="phone"></label>
<div class="action-row"><button class="primary-button" type="submit">Contactpersoon toevoegen</button></div>
</form>
<?php endif; ?>
</section>
<section class="panel"><div class="panel-header"><h2>Gekoppelde abonnementen</h2></div><?php if ($subscriptions === []): ?><div class="empty-state">Voor dit bedrijf zijn nog geen abonnementen gekoppeld.</div><?php else: ?><div class="table-wrapper"><table class="data-table compact-table data-table-comfortable"><thead><tr><th>Identifier</th><th>Type</th><th>Status</th><th>Prijs</th><th>Stations</th></tr></thead><tbody><?php foreach ($subscriptions as $subscription): ?><tr><td><a href="/subscriptions/<?= e($subscription['identifier']) ?>"><?= e($subscription['identifier']) ?></a></td><td><?= e($subscription['type']['name'] ?? '-') ?></td><td><span class="status-badge <?= subscription_status($subscription) === 'actief' ? 'success' : 'warning' ?>"><?= e(ucfirst(subscription_status($subscription))) ?></span></td><td>€ <?= e(number_format((float)$subscription['price'], 2, ',', '.')) ?></td><td><?= e((string)$subscription['station_count']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
</main></body></html>
