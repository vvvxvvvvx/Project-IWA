<?php

declare(strict_types=1);

namespace App\Companies\Repositories;

use App\Core\Support\JsonFileStore;

final class CompanyRepository
{
    public function all(): array
    {
        $companies = JsonFileStore::all('companies');
        $countries = [];
        foreach (JsonFileStore::all('countries') as $country) {
            $countries[$country['country_code']] = $country['country_name'];
        }
        usort($companies, static fn(array $a, array $b): int => strcmp((string)$a['name'], (string)$b['name']));
        return array_map(static function(array $company) use ($countries): array {
            $company['country_name'] = $countries[$company['country_code']] ?? $company['country_code'];
            return $company;
        }, $companies);
    }

    public function findById(int $id): ?array
    {
        foreach ($this->all() as $company) {
            if ((int)$company['id'] === $id) {
                return $company;
            }
        }
        return null;
    }

    public function create(array $fields): array
    {
        $companies = JsonFileStore::all('companies');
        $company = [
            'id' => JsonFileStore::nextId('companies'),
            'name' => trim((string)($fields['name'] ?? 'Nieuw bedrijf')),
            'city' => trim((string)($fields['city'] ?? '')),
            'street' => trim((string)($fields['street'] ?? '')),
            'number' => trim((string)($fields['number'] ?? '')),
            'number_additional' => trim((string)($fields['number_additional'] ?? '')) ?: null,
            'zip_code' => trim((string)($fields['zip_code'] ?? '')),
            'country_code' => strtoupper(trim((string)($fields['country_code'] ?? 'NL'))) ?: 'NL',
            'email' => trim((string)($fields['email'] ?? '')),
        ];
        $companies[] = $company;
        JsonFileStore::write('companies', $companies);
        return $this->findById((int)$company['id']) ?? $company;
    }

    public function update(int $id, array $fields): ?array
    {
        $companies = JsonFileStore::all('companies');
        foreach ($companies as &$company) {
            if ((int)$company['id'] !== $id) {
                continue;
            }
            $company['name'] = trim((string)($fields['name'] ?? $company['name']));
            $company['city'] = trim((string)($fields['city'] ?? $company['city']));
            $company['street'] = trim((string)($fields['street'] ?? $company['street']));
            $company['number'] = trim((string)($fields['number'] ?? $company['number']));
            $company['number_additional'] = trim((string)($fields['number_additional'] ?? ($company['number_additional'] ?? ''))) ?: null;
            $company['zip_code'] = trim((string)($fields['zip_code'] ?? $company['zip_code']));
            $company['country_code'] = strtoupper(trim((string)($fields['country_code'] ?? $company['country_code'])));
            $company['email'] = trim((string)($fields['email'] ?? $company['email']));
            JsonFileStore::write('companies', $companies);
            return $this->findById($id);
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $companies = JsonFileStore::all('companies');
        $filtered = array_values(array_filter($companies, static fn(array $company): bool => (int)$company['id'] !== $id));
        if (count($filtered) === count($companies)) {
            return false;
        }
        JsonFileStore::write('companies', $filtered);
        return true;
    }
}
