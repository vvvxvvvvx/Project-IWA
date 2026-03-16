<?php

declare(strict_types=1);

namespace App\Subscriptions\Controllers;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Subscriptions\Repositories\EndpointActivityRepository;
use App\Subscriptions\Repositories\SubscriptionRepository;
use App\Subscriptions\Repositories\SubscriptionTypeRepository;
use App\Core\Support\PhpViewRenderer;

final class SubscriptionDetailsPageController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly SubscriptionTypeRepository $types = new SubscriptionTypeRepository(),
        private readonly EndpointActivityRepository $activity = new EndpointActivityRepository(),
    ) {}

    public function show(string $identifier): void
    {
        $subscription = $this->subscriptions->findByIdentifier($identifier);
        if ($subscription === null) {
            HttpResponse::html('<h1>Abonnement niet gevonden</h1>', 404);
            return;
        }
        HttpResponse::html(PhpViewRenderer::render('subscriptions/detail', [
            'subscription' => $subscription,
            'activity' => $this->activity->latestByIdentifier($identifier),
            'types' => $this->types->all(),
        ]));
    }

    public function regenerateToken(string $identifier): void
    {
        $subscription = $this->subscriptions->regenerateToken($identifier);
        if ($subscription === null) {
            HttpResponse::html('<h1>Abonnement niet gevonden</h1>', 404);
            return;
        }
        HttpResponse::redirect('/subscriptions/' . $identifier);
    }

    public function update(HttpRequest $request, string $identifier): void
    {
        $subscription = $this->subscriptions->updateByIdentifier($identifier, [
            'price' => $request->input('price', ''),
            'notes' => $request->input('notes', ''),
            'end_date' => $request->input('end_date', ''),
            'type_id' => $request->input('type_id', ''),
        ]);
        if ($subscription === null) {
            HttpResponse::html('<h1>Abonnement niet gevonden</h1>', 404);
            return;
        }
        HttpResponse::redirect('/subscriptions/' . $identifier);
    }
}
