<?php

declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

session_name($config['session_name']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('UTC');

use App\Core\Support\JsonFileStore;

JsonFileStore::bootstrap(__DIR__ . '/storage/data', $config);

return $config;
