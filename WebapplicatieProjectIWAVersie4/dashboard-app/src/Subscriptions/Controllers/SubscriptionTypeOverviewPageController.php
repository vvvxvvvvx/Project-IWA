<?php

declare(strict_types=1);

namespace App\Subscriptions\Controllers;

use App\Core\Http\HttpResponse;
use App\Subscriptions\Repositories\SubscriptionRepository;
use App\Subscriptions\Repositories\SubscriptionTypeRepository;
use App\Core\Support\PhpViewRenderer;

final class SubscriptionTypeOverviewPageController
{
    public function __construct(
        private readonly SubscriptionTypeRepository $types = new SubscriptionTypeRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {}

    public function index(): void
    {
        $types = array_map(function(array $type): array {
            $type['subscriber_count'] = count($this->subscriptions->allByTypeId((int)$type['id']));
            return $type;
        }, $this->types->all());
        HttpResponse::html(PhpViewRenderer::render('subscriptions/types_index', [
            'types' => $types,
        ]));
    }
}
