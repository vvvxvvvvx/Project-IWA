<?php

declare(strict_types=1);

namespace App\Ingestion\Api;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Ingestion\Services\WeatherPayloadIngestService;

final class WeatherIngestApiController
{
    public function __construct(
        private readonly WeatherPayloadIngestService $ingestion = new WeatherPayloadIngestService(),
    ) {}

    public function store(HttpRequest $request): void
    {
        try {
            $summary = $this->ingestion->ingest($request->json());

            HttpResponse::json([
                'status' => 'ok',
                'message' => 'Payload received.',
                'summary' => $summary,
            ], 201);
        } catch (\Throwable $throwable) {
            HttpResponse::json([
                'status' => 'error',
                'message' => $throwable->getMessage(),
            ], 422);
        }
    }
}
