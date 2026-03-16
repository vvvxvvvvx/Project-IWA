<?php

declare(strict_types=1);

namespace App\Stations\Api;

use App\Core\Http\HttpResponse;
use App\Stations\Repositories\StationRepository;
use App\Ingestion\Repositories\WeatherReadingRepository;

final class StationDataApiController
{
    public function __construct(
        private readonly StationRepository $stations = new StationRepository(),
        private readonly WeatherReadingRepository $readings = new WeatherReadingRepository(),
    ) {}

    public function index(): void
    {
        HttpResponse::json(['stations' => $this->stations->latestWithSummaries(200)]);
    }

    public function show(string $stn): void
    {
        $station = $this->stations->findByStn($stn);
        if ($station === null) {
            HttpResponse::json(['message' => 'Station not found.'], 404);
            return;
        }
        HttpResponse::json([
            'station' => $station,
            'readings' => $this->readings->latestByStation($stn, 100),
        ]);
    }
}
