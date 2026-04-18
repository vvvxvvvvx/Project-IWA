<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$config = require $basePath . '/config/config.php';

spl_autoload_register(static function (string $class) use ($basePath): void {
    $prefix = 'App\\';
    $baseDir = $basePath . '/src/';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Support\JsonFileStore;

JsonFileStore::bootstrap($basePath . '/storage/data', $config);
echo "MySQL bootstrap klaar. Seeddata staat nu in app_json_store.\n";
