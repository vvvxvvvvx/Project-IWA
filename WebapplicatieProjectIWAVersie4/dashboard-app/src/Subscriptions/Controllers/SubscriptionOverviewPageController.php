<?php

declare(strict_types=1);

namespace App\Subscriptions\Controllers;

use App\Companies\Repositories\CompanyRepository;
use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Core\Support\PhpViewRenderer;
use App\Subscriptions\Repositories\SubscriptionRepository;
use App\Subscriptions\Repositories\SubscriptionTypeRepository;

final class SubscriptionOverviewPageController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly SubscriptionTypeRepository $types = new SubscriptionTypeRepository(),
        private readonly CompanyRepository $companies = new CompanyRepository(),
    ) {}

    public function index(HttpRequest $request): void
    {
        $subscriptions = $this->subscriptions->all();
        $types = $this->types->all();
        $query = trim((string)$request->input('q', ''));
        $status = trim((string)$request->input('status', 'all'));

        $subscriptions = array_values(array_filter($subscriptions, static function (array $subscription) use ($query, $status): bool {
            $isActive = empty($subscription['end_date']) || (string) $subscription['end_date'] >= gmdate('Y-m-d');
            $currentStatus = $isActive ? 'actief' : 'verlopen';
            if ($status !== '' && $status !== 'all' && $status !== $currentStatus) {
                return false;
            }
            if ($query === '') {
                return true;
            }
            $haystack = strtolower(implode(' ', [
                (string)($subscription['identifier'] ?? ''),
                (string)($subscription['company']['name'] ?? ''),
                (string)($subscription['type']['name'] ?? ''),
                (string)($subscription['notes'] ?? ''),
            ]));
            return str_contains($haystack, strtolower($query));
        }));

        $activeSubscriptions = array_values(array_filter($subscriptions, static fn(array $subscription): bool => empty($subscription['end_date']) || (string) $subscription['end_date'] >= gmdate('Y-m-d')));
        $totalRevenue = array_sum(array_map(static fn(array $subscription): float => (float) ($subscription['price'] ?? 0), $subscriptions));
        $totalStations = array_sum(array_map(static fn(array $subscription): int => (int) ($subscription['station_count'] ?? 0), $subscriptions));

        HttpResponse::html(PhpViewRenderer::render('subscriptions/index', [
            'subscriptions' => $subscriptions,
            'types' => $types,
            'companies' => $this->companies->all(),
            'filters' => ['q' => $query, 'status' => $status],
            'summary' => [
                'subscription_count' => count($subscriptions),
                'active_count' => count($activeSubscriptions),
                'type_count' => count($types),
                'total_station_links' => $totalStations,
                'total_revenue' => $totalRevenue,
            ],
        ]));
    }

    public function store(HttpRequest $request): void
    {
        $subscription = $this->subscriptions->create([
            'company_id' => $request->input('company_id', ''),
            'type_id' => $request->input('type_id', ''),
            'start_date' => $request->input('start_date', gmdate('Y-m-d')),
            'end_date' => $request->input('end_date', ''),
            'price' => $request->input('price', ''),
            'notes' => $request->input('notes', ''),
            'identifier' => $request->input('identifier', ''),
            'stations' => $request->input('stations', ''),
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Abonnement aangemaakt.'];
        HttpResponse::redirect('/subscriptions/' . $subscription['identifier']);
    }
}
