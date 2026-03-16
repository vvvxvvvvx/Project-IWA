<?php

declare(strict_types=1);

namespace App\Companies\Controllers;

use App\Core\Http\HttpResponse;
use App\Companies\Repositories\CompanyRepository;
use App\Companies\Repositories\ContactRepository;
use App\Subscriptions\Repositories\SubscriptionRepository;
use App\Core\Support\PhpViewRenderer;

final class CompanyDetailsPageController
{
    public function __construct(
        private readonly CompanyRepository $companies = new CompanyRepository(),
        private readonly ContactRepository $contacts = new ContactRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {}

    public function show(string $id): void
    {
        $company = $this->companies->findById((int)$id);
        if ($company === null) {
            HttpResponse::html('<h1>Bedrijf niet gevonden</h1>', 404);
            return;
        }
        $subscriptions = array_values(array_filter($this->subscriptions->all(), static fn(array $sub): bool => (int)($sub['company_id'] ?? 0) === (int)$company['id']));
        HttpResponse::html(PhpViewRenderer::render('companies/detail', [
            'company' => $company,
            'contacts' => $this->contacts->findByCompanyId((int)$company['id']),
            'subscriptions' => $subscriptions,
        ]));
    }
}
