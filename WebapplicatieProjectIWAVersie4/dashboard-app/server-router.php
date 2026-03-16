<?php

declare(strict_types=1);

$applicationRoot = __DIR__;
$publicRoot = $applicationRoot . DIRECTORY_SEPARATOR . 'public';
$publicRootRealPath = realpath($publicRoot) ?: $publicRoot;

$requestPath = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$requestedAbsolutePath = realpath($publicRoot . $requestPath);

if (
    $requestPath !== '/'
    && $requestedAbsolutePath !== false
    && str_starts_with($requestedAbsolutePath, $publicRootRealPath)
    && is_file($requestedAbsolutePath)
) {
    return false;
}

require $publicRoot . DIRECTORY_SEPARATOR . 'index.php';
