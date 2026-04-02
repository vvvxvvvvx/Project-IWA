<?php

declare(strict_types=1);

namespace App\Subscriptions\Controllers;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Core\Support\PhpViewRenderer;
use App\Subscriptions\Repositories\SubscriptionRepository;
use App\Subscriptions\Repositories\SubscriptionTypeRepository;

final class SubscriptionTypeDetailsPageController
{
    public function __construct(
        private readonly SubscriptionTypeRepository $types = new SubscriptionTypeRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {}

    public function show(string $id): void
    {
        $type = $this->types->findById((int)$id);
        if ($type === null) {
            HttpResponse::html('<h1>Abonnementtype niet gevonden</h1>', 404);
            return;
        }
        HttpResponse::html(PhpViewRenderer::render('subscriptions/type_detail', [
            'type' => $type,
            'subscriptions' => $this->subscriptions->allByTypeId((int)$id),
        ]));
    }

    public function update(HttpRequest $request, string $id): void
    {
        $updated = $this->types->update((int)$id, [
            'name' => $request->input('name', ''),
            'description' => $request->input('description', ''),
            'price_per_station' => $request->input('price_per_station', ''),
            'nr_stations' => $request->input('nr_stations', ''),
            'frequency_in_hours' => $request->input('frequency_in_hours', ''),
            'frequency_in_days' => $request->input('frequency_in_days', ''),
            'continuous' => $request->input('continuous', ''),
            'valid_through' => $request->input('valid_through', ''),
        ]);
        if ($updated === null) {
            HttpResponse::html('<h1>Abonnementtype niet gevonden</h1>', 404);
            return;
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Abonnementtype bijgewerkt.'];
        HttpResponse::redirect('/subscription-types/' . $id);
    }

    public function destroy(string $id): void
    {
        if (!$this->types->delete((int)$id)) {
            HttpResponse::html('<h1>Abonnementtype niet gevonden</h1>', 404);
            return;
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Abonnementtype verwijderd.'];
        HttpResponse::redirect('/subscription-types');
    }
}
