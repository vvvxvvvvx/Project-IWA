<?php

declare(strict_types=1);

namespace App\Stations\Repositories;

use App\Core\Support\JsonFileStore;

final class StationMetadataRepository
{
    public function all(): array
    {
        return JsonFileStore::all('station_metadata');
    }

    public function findByStn(string $stn): ?array
    {
        $all = $this->all();
        return $all[$stn] ?? null;
    }
}
