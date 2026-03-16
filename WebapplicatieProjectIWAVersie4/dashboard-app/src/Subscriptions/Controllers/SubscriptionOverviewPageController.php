<?php

declare(strict_types=1);

namespace App\Subscriptions\Controllers;

use App\Core\Http\HttpResponse;
use App\Subscriptions\Repositories\SubscriptionRepository;
use App\Subscriptions\Repositories\SubscriptionTypeRepository;
use App\Core\Support\PhpViewRenderer;

final class SubscriptionOverviewPageController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly SubscriptionTypeRepository $types = new SubscriptionTypeRepository(),
    ) {}

    public function index(): void
    {
        $subscriptions = $this->subscriptions->all();
        $types = $this->types->all();
        $activeSubscriptions = array_values(array_filter($subscriptions, static fn(array $subscription): bool => empty($subscription['end_date']) || (string) $subscription['end_date'] >= gmdate('Y-m-d')));
        $totalRevenue = array_sum(array_map(static fn(array $subscription): float => (float) ($subscription['price'] ?? 0), $subscriptions));
        $totalStations = array_sum(array_map(static fn(array $subscription): int => (int) ($subscription['station_count'] ?? 0), $subscriptions));

        HttpResponse::html(PhpViewRenderer::render('subscriptions/index', [
            'subscriptions' => $subscriptions,
            'types' => $types,
            'summary' => [
                'subscription_count' => count($subscriptions),
                'active_count' => count($activeSubscriptions),
                'type_count' => count($types),
                'total_station_links' => $totalStations,
                'total_revenue' => $totalRevenue,
            ],
        ]));
    }
}
