<?php

declare(strict_types=1);

namespace App\Stations\Controllers;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Core\Support\PhpViewRenderer;
use App\Stations\Repositories\StationRepository;

final class StationOverviewPageController
{
    public function __construct(private readonly StationRepository $stations = new StationRepository()) {}

    public function index(HttpRequest $request): void
    {
        $query = trim((string)$request->input('q', ''));
        $status = trim((string)$request->input('status', 'all'));
        $stations = $this->stations->latestWithSummaries(500);

        $stations = array_values(array_filter($stations, static function (array $station) use ($query, $status): bool {
            $stationStatus = ((int)($station['has_missing_data'] ?? 0) === 1) ? 'missing' : (((int)($station['is_temp_peak'] ?? 0) === 1) ? 'peak' : 'ok');
            if ($status !== '' && $status !== 'all' && $stationStatus !== $status) {
                return false;
            }
            if ($query === '') {
                return true;
            }
            $haystack = strtolower(implode(' ', [
                (string)($station['stn'] ?? ''),
                (string)($station['name'] ?? ''),
                (string)($station['location_label'] ?? ''),
                (string)($station['country'] ?? ''),
            ]));
            return str_contains($haystack, strtolower($query));
        }));

        usort($stations, static function (array $a, array $b): int {
            return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        });

        HttpResponse::html(PhpViewRenderer::render('stations/index', [
            'stations' => $stations,
            'filters' => ['q' => $query, 'status' => $status],
        ]));
    }
}
