<?php

declare(strict_types=1);

namespace App\Dashboard\Controllers;

use App\Core\Http\HttpResponse;
use App\Dashboard\Services\DashboardMetricsService;
use App\Core\Support\PhpViewRenderer;

final class DashboardPageController
{
    public function __construct(private readonly DashboardMetricsService $metrics = new DashboardMetricsService()) {}

    public function index(): void
    {
        HttpResponse::html(PhpViewRenderer::render('dashboard/dashboard', $this->metrics->summary()));
    }
}
