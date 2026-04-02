<?php

declare(strict_types=1);

namespace App\Subscriptions\Controllers;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Core\Support\PhpViewRenderer;
use App\Subscriptions\Repositories\SubscriptionRepository;
use App\Subscriptions\Repositories\SubscriptionTypeRepository;

final class SubscriptionTypeOverviewPageController
{
    public function __construct(
        private readonly SubscriptionTypeRepository $types = new SubscriptionTypeRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {}

    public function index(HttpRequest $request): void
    {
        $query = trim((string)$request->input('q', ''));
        $types = array_map(function(array $type): array {
            $type['subscriber_count'] = count($this->subscriptions->allByTypeId((int)$type['id']));
            return $type;
        }, $this->types->all());
        if ($query !== '') {
            $types = array_values(array_filter($types, static function (array $type) use ($query): bool {
                return str_contains(strtolower((string)$type['name'] . ' ' . (string)$type['description']), strtolower($query));
            }));
        }
        HttpResponse::html(PhpViewRenderer::render('subscriptions/types_index', [
            'types' => $types,
            'filters' => ['q' => $query],
        ]));
    }

    public function store(HttpRequest $request): void
    {
        $type = $this->types->create([
            'name' => $request->input('name', ''),
            'description' => $request->input('description', ''),
            'price_per_station' => $request->input('price_per_station', ''),
            'nr_stations' => $request->input('nr_stations', ''),
            'frequency_in_hours' => $request->input('frequency_in_hours', ''),
            'frequency_in_days' => $request->input('frequency_in_days', ''),
            'continuous' => $request->input('continuous', '0'),
            'valid_through' => $request->input('valid_through', ''),
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Abonnementtype aangemaakt.'];
        HttpResponse::redirect('/subscription-types/' . $type['id']);
    }
}
