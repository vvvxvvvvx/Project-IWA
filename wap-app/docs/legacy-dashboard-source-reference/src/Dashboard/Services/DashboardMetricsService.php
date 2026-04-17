<?php

declare(strict_types=1);

namespace App\Dashboard\Services;

use App\Ingestion\Repositories\CorrectionLogRepository;
use App\Ingestion\Repositories\IngestBatchRepository;
use App\Ingestion\Repositories\OriginalMeasurementRepository;
use App\Stations\Services\StationDataSnapshot;

final class DashboardMetricsService
{
    public function __construct(
        private readonly StationDataSnapshot $snapshot = new StationDataSnapshot(),
        private readonly IngestBatchRepository $batches = new IngestBatchRepository(),
        private readonly CorrectionLogRepository $corrections = new CorrectionLogRepository(),
        private readonly OriginalMeasurementRepository $originals = new OriginalMeasurementRepository(),
    ) {}

    public function summary(): array
    {
        return [
            'overview' => $this->snapshot->overview(),
            'latest_readings' => $this->snapshot->latestReadings(12),
            'chart_points' => $this->originals->latestTemperatureCorrections(20),
            'top_stations' => $this->snapshot->topStations(5),
            'latest_batches' => $this->batches->latest(),
            'stations' => $this->snapshot->summarizedStationsAll(),
            'flagged_readings' => $this->snapshot->latestFlaggedReadings(12),
            'recent_corrections' => $this->corrections->latest(),
            'recent_originals' => $this->originals->latest(),
        ];
    }
}
