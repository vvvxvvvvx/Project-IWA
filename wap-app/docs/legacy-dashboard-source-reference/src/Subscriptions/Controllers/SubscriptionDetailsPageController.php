<?php

declare(strict_types=1);

namespace App\Subscriptions\Controllers;

use App\Companies\Repositories\CompanyRepository;
use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Core\Support\PhpViewRenderer;
use App\Subscriptions\Repositories\EndpointActivityRepository;
use App\Subscriptions\Repositories\SubscriptionRepository;
use App\Subscriptions\Repositories\SubscriptionTypeRepository;

final class SubscriptionDetailsPageController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly SubscriptionTypeRepository $types = new SubscriptionTypeRepository(),
        private readonly EndpointActivityRepository $activity = new EndpointActivityRepository(),
        private readonly CompanyRepository $companies = new CompanyRepository(),
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
            'companies' => $this->companies->all(),
        ]));
    }

    public function regenerateToken(string $identifier): void
    {
        $subscription = $this->subscriptions->regenerateToken($identifier);
        if ($subscription === null) {
            HttpResponse::html('<h1>Abonnement niet gevonden</h1>', 404);
            return;
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Nieuw token gegenereerd.'];
        HttpResponse::redirect('/subscriptions/' . $identifier);
    }

    public function sendToken(string $identifier): void
    {
        $subscription = $this->subscriptions->findByIdentifier($identifier);
        if ($subscription === null) {
            HttpResponse::html('<h1>Abonnement niet gevonden</h1>', 404);
            return;
        }
        $recipient = $subscription['company']['email'] ?? 'onbekend e-mailadres';
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Token klaargezet voor verzending naar ' . $recipient . '.'];
        HttpResponse::redirect('/subscriptions/' . $identifier);
    }

    public function update(HttpRequest $request, string $identifier): void
    {
        $original = $this->subscriptions->findByIdentifier($identifier);
        $subscription = $this->subscriptions->updateByIdentifier($identifier, [
            'company_id' => $request->input('company_id', ''),
            'price' => $request->input('price', ''),
            'notes' => $request->input('notes', ''),
            'end_date' => $request->input('end_date', ''),
            'start_date' => $request->input('start_date', ''),
            'type_id' => $request->input('type_id', ''),
            'identifier' => $request->input('identifier', ''),
            'stations' => $request->input('stations', ''),
        ]);
        if ($subscription === null || $original === null) {
            HttpResponse::html('<h1>Abonnement niet gevonden</h1>', 404);
            return;
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Abonnement bijgewerkt.'];
        HttpResponse::redirect('/subscriptions/' . $subscription['identifier']);
    }

    public function destroy(string $identifier): void
    {
        if (!$this->subscriptions->deleteByIdentifier($identifier)) {
            HttpResponse::html('<h1>Abonnement niet gevonden</h1>', 404);
            return;
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Abonnement verwijderd.'];
        HttpResponse::redirect('/subscriptions');
    }
}
