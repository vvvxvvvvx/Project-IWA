<?php
function e(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function format_datetime(?string $value): string {
    if ($value === null || $value === '') return '-';
    try {
        $dt = new DateTimeImmutable($value);
        return $dt->format('d-m-Y H:i:s') . ' UTC';
    } catch (Throwable) {
        return str_replace('T', ' ', str_replace('+00:00', ' UTC', (string)$value));
    }
}
function current_role(): ?string { return $_SESSION['user_role'] ?? null; }
function render_brand_header(string $eyebrow, string $title, string $subtitle = ''): void {
    echo '<header class="app-header">';
    echo '<div class="brand-block">';
    echo '<img class="iwa-logo" src="/assets/iwa-logo.png" alt="IWA logo">';
    echo '<div><p class="eyebrow">' . e($eyebrow) . '</p><h1>' . e($title) . '</h1>';
    if ($subtitle !== '') echo '<p class="header-subtitle">' . e($subtitle) . '</p>';
    echo '</div></div>';
}
function render_header_actions(string $content = ''): void {
    echo '<div class="header-actions">' . $content . '</div></header>';
}
function render_nav(string $active): void {
    $role = current_role();
    echo '<nav class="page-nav">';
    $items = [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '/'],
        ['id' => 'stations', 'label' => 'Stations', 'href' => '/stations'],
    ];
    foreach ($items as $item) {
        $class = $active === $item['id'] ? 'active' : '';
        echo '<a class="' . $class . '" href="' . $item['href'] . '">' . e($item['label']) . '</a>';
    }
    if ($role === 'employee' || $role === 'admin') {
        echo '<a class="' . ($active === 'subscriptions' ? 'active' : '') . '" href="/subscriptions">Abonnementen</a>';
        echo '<a class="' . ($active === 'subscription-types' ? 'active' : '') . '" href="/subscription-types">Aanbod</a>';
        echo '<a class="' . ($active === 'contracts' ? 'active' : '') . '" href="/contracts">Contracten</a>';
        echo '<a class="' . ($active === 'companies' ? 'active' : '') . '" href="/companies">Bedrijven</a>';
    }
    echo '</nav>';
}
