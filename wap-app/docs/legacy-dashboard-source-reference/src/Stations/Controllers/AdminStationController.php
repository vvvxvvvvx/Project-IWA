<?php

declare(strict_types=1);

namespace App\Stations\Controllers;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Stations\Repositories\StationRepository;

final class AdminStationController
{
    public function __construct(private readonly StationRepository $stations = new StationRepository()) {}

    public function updateLocation(HttpRequest $request, string $stn): void
    {
        $this->stations->updateLocationLabel($stn, (string) $request->input('location_label', ''));
        HttpResponse::redirect('/?tab=adminTab');
    }
}
