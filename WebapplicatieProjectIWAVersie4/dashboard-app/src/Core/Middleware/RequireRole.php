<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;

final class RequireRole
{
    public function __construct(private readonly array $roles) {}

    public function __invoke(HttpRequest $request): void
    {
        $role = $_SESSION['user_role'] ?? null;
        if ($role === null || !in_array($role, $this->roles, true)) {
            HttpResponse::html('<h1>403</h1><p>Geen toegang tot deze functie.</p>', 403);
        }
    }
}
