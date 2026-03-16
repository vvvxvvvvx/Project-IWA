<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Subscriptions\Repositories\EndpointActivityRepository;
use App\Subscriptions\Repositories\SubscriptionRepository;

final class ContractRepository
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly EndpointActivityRepository $activity = new EndpointActivityRepository(),
    ) {}

    public function all(): array
    {
        return array_map(function(array $subscription): array {
            return $this->mapContract($subscription);
        }, $this->subscriptions->all());
    }

    public function findByIdentifier(string $identifier): ?array
    {
        $subscription = $this->subscriptions->findByIdentifier($identifier);
        return $subscription ? $this->mapContract($subscription) : null;
    }

    private function mapContract(array $subscription): array
    {
        $activity = $this->activity->latestByIdentifier((string)$subscription['identifier']);
        $successfulCalls = count(array_filter($activity, static fn(array $row): bool => (int)($row['authorized'] ?? 0) === 1));
        return [
            'identifier' => $subscription['identifier'],
            'title' => 'Contract ' . $subscription['identifier'],
            'company' => $subscription['company'],
            'type' => $subscription['type'],
            'price' => $subscription['price'],
            'start_date' => $subscription['start_date'],
            'end_date' => $subscription['end_date'],
            'notes' => $subscription['notes'],
            'subscription' => $subscription,
            'rest_api_source' => '/IWA/abonnement/' . $subscription['identifier'],
            'activity' => $activity,
            'successful_calls' => $successfulCalls,
            'station_count' => $subscription['station_count'],
        ];
    }
}
