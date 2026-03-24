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

    public function findById(int $id): ?array
    {
        foreach (JsonFileStore::all('contacts') as $contact) {
            if ((int)$contact['id'] === $id) {
                return $contact;
            }
        }
        return null;
    }

    public function create(int $companyId, array $fields): array
    {
        $contacts = JsonFileStore::all('contacts');
        $contact = [
            'id' => JsonFileStore::nextId('contacts'),
            'company_id' => $companyId,
            'name' => trim((string)($fields['name'] ?? '')),
            'first_name' => trim((string)($fields['first_name'] ?? '')),
            'initials' => trim((string)($fields['initials'] ?? '')),
            'prefix' => trim((string)($fields['prefix'] ?? '')) ?: null,
            'function' => trim((string)($fields['function'] ?? '')),
            'title' => trim((string)($fields['title'] ?? '')),
            'email' => trim((string)($fields['email'] ?? '')),
            'phone' => trim((string)($fields['phone'] ?? '')),
        ];
        $contacts[] = $contact;
        JsonFileStore::write('contacts', $contacts);
        return $contact;
    }

    public function update(int $id, array $fields): ?array
    {
        $contacts = JsonFileStore::all('contacts');
        foreach ($contacts as &$contact) {
            if ((int)$contact['id'] !== $id) {
                continue;
            }
            $contact['name'] = trim((string)($fields['name'] ?? $contact['name']));
            $contact['first_name'] = trim((string)($fields['first_name'] ?? $contact['first_name']));
            $contact['initials'] = trim((string)($fields['initials'] ?? ($contact['initials'] ?? '')));
            $contact['prefix'] = trim((string)($fields['prefix'] ?? ($contact['prefix'] ?? ''))) ?: null;
            $contact['function'] = trim((string)($fields['function'] ?? $contact['function']));
            $contact['title'] = trim((string)($fields['title'] ?? $contact['title']));
            $contact['email'] = trim((string)($fields['email'] ?? $contact['email']));
            $contact['phone'] = trim((string)($fields['phone'] ?? $contact['phone']));
            JsonFileStore::write('contacts', $contacts);
            return $contact;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $contacts = JsonFileStore::all('contacts');
        $filtered = array_values(array_filter($contacts, static fn(array $contact): bool => (int)$contact['id'] !== $id));
        if (count($filtered) === count($contacts)) {
            return false;
        }
        JsonFileStore::write('contacts', $filtered);
        return true;
    }
}
