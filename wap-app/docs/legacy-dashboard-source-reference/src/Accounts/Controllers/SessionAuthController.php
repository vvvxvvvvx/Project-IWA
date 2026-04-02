<?php

declare(strict_types=1);

namespace App\Accounts\Controllers;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Accounts\Services\AuthService;
use App\Core\Support\PhpViewRenderer;

final class SessionAuthController
{
    public function __construct(private readonly AuthService $auth = new AuthService()) {}

    public function showLogin(): void
    {
        HttpResponse::html(PhpViewRenderer::render('accounts/login', [
            'error' => $_SESSION['login_error'] ?? null,
        ]));
        unset($_SESSION['login_error']);
    }

    public function login(HttpRequest $request): void
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        if (!$this->auth->attemptLogin($email, $password)) {
            $_SESSION['login_error'] = 'Ongeldige inloggegevens.';
            HttpResponse::redirect('/login');
        }
        HttpResponse::redirect('/');
    }

    public function logout(): void
    {
        $this->auth->logout();
        HttpResponse::redirect('/login');
    }
}
