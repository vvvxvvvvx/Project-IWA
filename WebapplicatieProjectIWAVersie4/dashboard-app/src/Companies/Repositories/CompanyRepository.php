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
}
