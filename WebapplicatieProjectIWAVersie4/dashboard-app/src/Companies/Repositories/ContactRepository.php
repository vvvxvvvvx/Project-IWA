<?php

declare(strict_types=1);

namespace App\Companies\Repositories;

use App\Core\Support\JsonFileStore;

final class ContactRepository
{
    public function findByCompanyId(int $companyId): array
    {
        $contacts = array_values(array_filter(
            JsonFileStore::all('contacts'),
            static fn(array $contact): bool => (int)$contact['company_id'] === $companyId
        ));
        usort($contacts, static fn(array $a, array $b): int => strcmp((string)$a['name'], (string)$b['name']));
        return $contacts;
    }
}
