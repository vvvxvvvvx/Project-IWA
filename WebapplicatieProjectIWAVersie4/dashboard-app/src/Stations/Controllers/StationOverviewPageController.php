<?php

declare(strict_types=1);

namespace App\Stations\Controllers;

use App\Core\Http\HttpResponse;
use App\Stations\Repositories\StationRepository;
use App\Core\Support\PhpViewRenderer;

final class StationOverviewPageController
{
    public function __construct(private readonly StationRepository $stations = new StationRepository()) {}

    public function index(): void
    {
        HttpResponse::html(PhpViewRenderer::render('stations/index', [
            'stations' => $this->stations->latestWithSummaries(500),
        ]));
    }
}
