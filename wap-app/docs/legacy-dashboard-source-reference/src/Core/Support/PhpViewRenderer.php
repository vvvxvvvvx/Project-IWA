<?php

declare(strict_types=1);

namespace App\Core\Support;

final class PhpViewRenderer
{
    public static function render(string $view, array $data = []): string
    {
        $viewFile = __DIR__ . '/../../../views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        return (string) ob_get_clean();
    }
}
