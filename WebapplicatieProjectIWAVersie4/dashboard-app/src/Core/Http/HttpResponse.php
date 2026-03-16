<?php

declare(strict_types=1);

namespace App\Core\Http;

final class HttpResponse
{
    public static function html(string $content, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
    }

    public static function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
