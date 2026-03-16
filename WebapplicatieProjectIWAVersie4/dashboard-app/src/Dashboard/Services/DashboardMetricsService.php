<?php

declare(strict_types=1);

namespace App\Dashboard\Services;

use App\Ingestion\Repositories\CorrectionLogRepository;
use App\Ingestion\Repositories\IngestBatchRepository;
use App\Ingestion\Repositories\OriginalMeasurementRepository;
use App\Stations\Repositories\StationRepository;
use App\Ingestion\Repositories\WeatherReadingRepository;

final class DashboardMetricsService
{
    public function __construct(
        private readonly WeatherReadingRepository $readings = new WeatherReadingRepository(),
        private readonly StationRepository $stations = new StationRepository(),
        private readonly IngestBatchRepository $batches = new IngestBatchRepository(),
        private readonly CorrectionLogRepository $corrections = new CorrectionLogRepository(),
        private readonly OriginalMeasurementRepository $originals = new OriginalMeasurementRepository(),
    ) {}

    public function summary(): array
    {
        return [
            'overview' => $this->readings->overview(),
            'latest_readings' => $this->readings->latestReadingsTable(),
            'chart_points' => $this->originals->latestTemperatureCorrections(20),
            'top_stations' => $this->stations->topByReadingCount(),
            'latest_batches' => $this->batches->latest(),
            'stations' => $this->stations->latestWithSummaries(),
            'flagged_readings' => $this->readings->latestFlaggedReadings(),
            'recent_corrections' => $this->corrections->latest(),
            'recent_originals' => $this->originals->latest(),
        ];
    }
}
