<?php

declare(strict_types=1);

namespace App\Stations\Controllers;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Stations\Repositories\StationRepository;
use App\Ingestion\Repositories\WeatherReadingRepository;
use App\Core\Support\PhpViewRenderer;

final class StationDetailsPageController
{
    public function __construct(
        private readonly StationRepository $stations = new StationRepository(),
        private readonly WeatherReadingRepository $readings = new WeatherReadingRepository(),
    ) {}

    public function show(string $stn): void
    {
        $station = $this->stations->findByStn($stn);
        if ($station === null) {
            HttpResponse::html('<h1>Station niet gevonden</h1>', 404);
            return;
        }
        HttpResponse::html(PhpViewRenderer::render('stations/detail', [
            'station' => $station,
            'readings' => $this->readings->latestByStation($stn, 100),
        ]));
    }

    public function downloadCsv(HttpRequest $request, string $stn): void
    {
        $rows = $this->readings->byStationPeriod($stn, (string)$request->input('from', ''), (string)$request->input('to', ''), 1000);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="station_' . $stn . '_period.csv"');
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['stn', 'measured_at', 'temp', 'dewp', 'stp', 'slp', 'visib', 'wdsp', 'prcp', 'sndp', 'frshtt', 'cldc', 'wnddir']);
        foreach ($rows as $row) {
            fputcsv($out, [$row['stn'], $row['measured_at'], $row['temp'], $row['dewp'], $row['stp'], $row['slp'], $row['visib'], $row['wdsp'], $row['prcp'], $row['sndp'], $row['frshtt'], $row['cldc'], $row['wnddir']]);
        }
        fclose($out);
    }
}
