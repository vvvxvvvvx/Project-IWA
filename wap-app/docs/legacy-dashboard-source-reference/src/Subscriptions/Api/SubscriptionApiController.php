<?php

declare(strict_types=1);

namespace App\Subscriptions\Api;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Subscriptions\Repositories\EndpointActivityRepository;
use App\Stations\Repositories\StationRepository;
use App\Subscriptions\Repositories\SubscriptionRepository;
use App\Ingestion\Repositories\WeatherReadingRepository;

final class SubscriptionApiController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly WeatherReadingRepository $readings = new WeatherReadingRepository(),
        private readonly StationRepository $stations = new StationRepository(),
        private readonly EndpointActivityRepository $activity = new EndpointActivityRepository(),
    ) {}

    public function stations(HttpRequest $request, string $identifier): void
    {
        $subscription = $this->authenticate($request, $identifier, '/IWA/abonnement/' . $identifier . '/stations');
        if ($subscription === null) return;
        HttpResponse::json([
            'identifier' => $subscription['identifier'],
            'company' => $subscription['company']['name'] ?? null,
            'stations' => $subscription['stations'],
        ]);
    }

    public function stationDetails(HttpRequest $request, string $identifier, string $stationName): void
    {
        $subscription = $this->authenticate($request, $identifier, '/IWA/abonnement/' . $identifier . '/station/' . $stationName);
        if ($subscription === null) return;
        $allowed = false;
        foreach ($subscription['stations'] as $station) {
            if ((string)$station['stn'] === (string)$stationName) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            HttpResponse::json(['message' => 'Station valt niet onder dit abonnement.'], 403);
            return;
        }
        $station = $this->stations->findByStn($stationName);
        if ($station === null) {
            HttpResponse::json(['message' => 'Station niet gevonden.'], 404);
            return;
        }
        HttpResponse::json([
            'identifier' => $subscription['identifier'],
            'station' => $station,
            'latest_readings' => $this->readings->latestByStation($stationName, 25),
        ]);
    }

    public function files(HttpRequest $request, string $identifier): void
    {
        $subscription = $this->authenticate($request, $identifier, '/IWA/abonnement/' . $identifier . '/files');
        if ($subscription === null) return;
        $files = [];
        foreach ($subscription['stations'] as $station) {
            $files[] = [
                'name' => 'station_' . $station['stn'] . '_latest.csv',
                'download_url' => '/IWA/abonnement/' . $identifier . '/files/' . 'station_' . $station['stn'] . '_latest.csv' . '?token=' . rawurlencode((string)$subscription['token']),
                'station' => $station['stn'],
                'description' => 'Laatste 50 metingen voor station ' . $station['stn'],
            ];
        }
        HttpResponse::json(['identifier' => $subscription['identifier'], 'files' => $files]);
    }

    public function downloadFile(HttpRequest $request, string $identifier, string $fileName): void
    {
        $subscription = $this->authenticate($request, $identifier, '/IWA/abonnement/' . $identifier . '/files/' . $fileName);
        if ($subscription === null) return;
        if (!preg_match('/^station_(\d+)_latest\.csv$/', $fileName, $matches)) {
            HttpResponse::json(['message' => 'Bestand niet beschikbaar.'], 404);
            return;
        }
        $stationId = $matches[1];
        $allowed = false;
        foreach ($subscription['stations'] as $station) {
            if ((string)$station['stn'] === (string)$stationId) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            HttpResponse::json(['message' => 'Bestand valt niet onder dit abonnement.'], 403);
            return;
        }
        $rows = $this->readings->latestByStation((string)$stationId, 50);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['stn', 'measured_at', 'temp', 'dewp', 'stp', 'slp', 'visib', 'wdsp', 'prcp', 'sndp', 'frshtt', 'cldc', 'wnddir']);
        foreach ($rows as $row) {
            fputcsv($out, [$row['stn'], $row['measured_at'], $row['temp'], $row['dewp'], $row['stp'], $row['slp'], $row['visib'], $row['wdsp'], $row['prcp'], $row['sndp'], $row['frshtt'], $row['cldc'], $row['wnddir']]);
        }
        fclose($out);
    }

    private function authenticate(HttpRequest $request, string $identifier, string $endpoint): ?array
    {
        $token = $request->input('token');
        $authHeader = $request->headers['x-auth-token'] ?? $request->headers['authorization'] ?? null;
        if (($token === null || $token === '') && is_string($authHeader) && $authHeader !== '') {
            $token = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : $authHeader;
        }
        $subscription = $this->subscriptions->authenticate($identifier, is_string($token) ? $token : null);
        $this->activity->create([
            'identifier' => $identifier,
            'endpoint_used' => ltrim($endpoint, '/'),
            'files_downloaded' => str_contains($endpoint, '/files/') ? 1 : 0,
            'activity_date' => gmdate('Y-m-d'),
            'activity_time' => gmdate('H:i:s'),
            'authorized' => $subscription !== null ? 1 : 0,
            'data_transferred' => 0,
        ]);
        if ($subscription === null) {
            HttpResponse::json(['message' => 'Ongeldig abonnement of token.'], 401);
            return null;
        }
        return $subscription;
    }
}
