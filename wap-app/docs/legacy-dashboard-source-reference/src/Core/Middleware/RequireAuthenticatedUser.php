<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;

final class RequireAuthenticatedUser
{
    public function __invoke(HttpRequest $request): void
    {
        if (!isset($_SESSION['user_id'])) {
            HttpResponse::redirect('/login');
        }
    }
}
