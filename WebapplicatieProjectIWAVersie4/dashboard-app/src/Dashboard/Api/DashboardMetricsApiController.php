<?php

declare(strict_types=1);

namespace App\Dashboard\Api;

use App\Core\Http\HttpResponse;
use App\Dashboard\Services\DashboardMetricsService;

final class DashboardMetricsApiController
{
    public function __construct(private readonly DashboardMetricsService $metrics = new DashboardMetricsService()) {}

    public function overview(): void
    {
        HttpResponse::json($this->metrics->summary());
    }
}
