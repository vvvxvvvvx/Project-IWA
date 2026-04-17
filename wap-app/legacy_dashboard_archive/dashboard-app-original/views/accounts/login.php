<?php require __DIR__ . '/../shared/partials.php'; ?>
<!DOCTYPE html>
<html lang="nl"><head><meta charset="UTF-8"><title>IWA Dashboard Login</title><link rel="stylesheet" href="/assets/styles.css"></head><body class="login-body">
<main class="login-card brand-login-card">
    <div class="brand-block login-brand"><img class="iwa-logo" src="/assets/iwa-logo.png" alt="IWA logo"><div><p class="eyebrow">Internationale Weer Agentschap</p><h1>IWA Dashboard</h1><p class="muted">Log in om weerdata, stations, abonnementen en contracten te bekijken.</p></div></div>
    <?php if (!empty($error)): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/login" class="login-form">
        <label><span>E-mail</span><input type="email" name="email" value="admin@iwa.local" required></label>
        <label><span>Wachtwoord</span><input type="password" name="password" value="admin123" required></label>
        <button type="submit" class="primary-button">Inloggen</button>
    </form>
    <div class="details-grid login-accounts">
        <div><strong>Admin</strong><div>admin@iwa.local</div><div>admin123</div></div>
        <div><strong>Medewerker</strong><div>medewerker@iwa.local</div><div>medewerker123</div></div>
        <div><strong>Klant</strong><div>klant@iwa.local</div><div>klant123</div></div>
    </div>
</main>
</body></html>
