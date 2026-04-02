<?php

declare(strict_types=1);

namespace App\Accounts\Repositories;

use App\Core\Support\JsonFileStore;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        foreach (JsonFileStore::all('users') as $user) {
            if (($user['email'] ?? null) === $email) {
                return $user;
            }
        }
        return null;
    }

    public function findById(int $id): ?array
    {
        foreach (JsonFileStore::all('users') as $user) {
            if ((int) ($user['id'] ?? 0) === $id) {
                return $user;
            }
        }
        return null;
    }
}
