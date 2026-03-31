<?php

require(__DIR__ . '/bootstrap/app.php');

$app = require_once(__DIR__ . '/bootstrap/app.php');
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Clear all cache
$kernel->call('cache:clear');
echo "Cache cleared!\n";

// Also clear config cache if exists
$kernel->call('config:clear');
echo "Config cleared!\n";
