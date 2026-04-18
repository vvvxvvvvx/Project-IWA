<?php

declare(strict_types=1);

namespace App\Accounts\Services;

use App\Accounts\Repositories\UserRepository;

final class AuthService
{
    public function __construct(private readonly UserRepository $users = new UserRepository()) {}

    public function attemptLogin(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['display_name'] = $user['display_name'] ?? $user['email'];
        session_regenerate_id(true);
        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function currentUser(): ?array
    {
        $userId = $_SESSION['user_id'] ?? null;
        return $userId === null ? null : $this->users->findById((int) $userId);
    }
}
