<?php

declare(strict_types=1);

namespace App\Dashboard\Controllers;

use App\Core\Http\HttpResponse;
use App\Dashboard\Services\DashboardMetricsService;
use App\Core\Support\PhpViewRenderer;

final class MetricInsightPageController
{
    public function __construct(private readonly DashboardMetricsService $metrics = new DashboardMetricsService()) {}

    public function show(string $slug): void
    {
        $summary = $this->metrics->summary();
        $config = match ($slug) {
            'stations-online' => [
                'title' => 'Stations online',
                'subtitle' => 'Overzicht van alle stations die data hebben aangeleverd.',
                'description' => 'Deze pagina laat zien welke weerstations online zijn, wanneer ze voor het laatst data stuurden en wat hun laatste meetwaarden waren.',
                'table_type' => 'stations',
                'rows' => $summary['stations'],
            ],
            'readings' => [
                'title' => 'Readings',
                'subtitle' => 'Laatste opgeslagen metingen na validatie en correctie.',
                'description' => 'Hier zie je de meest recente individuele readings. Handig om snel te controleren of nieuwe generator-data correct binnenkomt.',
                'table_type' => 'latest',
                'rows' => $summary['latest_readings'],
            ],
            'temperature' => [
                'title' => 'Originele versus gecorrigeerde temperatuur',
                'subtitle' => 'Laatste temperatuurcorrecties op basis van extrapolatie en 20%-begrenzing.',
                'description' => 'Deze grafiek toont de originele temperatuurwaarde naast de gecorrigeerde waarde wanneer een temperatuur ontbrak of 20% of meer afweek van de geëxtrapoleerde verwachting.',
                'table_type' => 'chart',
                'rows' => $summary['chart_points'],
                'overview' => $summary['overview'],
            ],
            'data-quality' => [
                'title' => 'Datakwaliteit',
                'subtitle' => 'Correcties, ontbrekende waarden en signaleringen.',
                'description' => 'Data quality combineert twee soorten signalen: piekmetingen en ontbrekende velden. Daarnaast zie je welke waarden automatisch gecorrigeerd zijn en welke oorspronkelijke waarden apart zijn opgeslagen.',
                'table_type' => 'quality',
                'rows' => $summary['recent_corrections'],
                'originals' => $summary['recent_originals'],
                'flagged' => $summary['flagged_readings'],
                'overview' => $summary['overview'],
            ],
            default => null,
        };

        if ($config === null) {
            HttpResponse::html('<h1>Inzicht niet gevonden</h1>', 404);
            return;
        }

        HttpResponse::html(PhpViewRenderer::render('dashboard/metric', $config));
    }
}
