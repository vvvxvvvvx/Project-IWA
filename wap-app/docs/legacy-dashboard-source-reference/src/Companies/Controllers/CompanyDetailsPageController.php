<?php

declare(strict_types=1);

namespace App\Companies\Controllers;

use App\Companies\Repositories\CompanyRepository;
use App\Companies\Repositories\ContactRepository;
use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Core\Support\PhpViewRenderer;
use App\Subscriptions\Repositories\SubscriptionRepository;

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

    public function update(HttpRequest $request, string $id): void
    {
        $updated = $this->companies->update((int)$id, [
            'name' => $request->input('name', ''),
            'city' => $request->input('city', ''),
            'street' => $request->input('street', ''),
            'number' => $request->input('number', ''),
            'number_additional' => $request->input('number_additional', ''),
            'zip_code' => $request->input('zip_code', ''),
            'country_code' => $request->input('country_code', 'NL'),
            'email' => $request->input('email', ''),
        ]);
        if ($updated === null) {
            HttpResponse::html('<h1>Bedrijf niet gevonden</h1>', 404);
            return;
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Bedrijf bijgewerkt.'];
        HttpResponse::redirect('/companies/' . $id);
    }

    public function destroy(string $id): void
    {
        if (!$this->companies->delete((int)$id)) {
            HttpResponse::html('<h1>Bedrijf niet gevonden</h1>', 404);
            return;
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Bedrijf verwijderd.'];
        HttpResponse::redirect('/companies');
    }

    public function addContact(HttpRequest $request, string $id): void
    {
        $company = $this->companies->findById((int)$id);
        if ($company === null) {
            HttpResponse::html('<h1>Bedrijf niet gevonden</h1>', 404);
            return;
        }
        $this->contacts->create((int)$id, [
            'name' => $request->input('name', ''),
            'first_name' => $request->input('first_name', ''),
            'initials' => $request->input('initials', ''),
            'prefix' => $request->input('prefix', ''),
            'function' => $request->input('function', ''),
            'title' => $request->input('title', ''),
            'email' => $request->input('email', ''),
            'phone' => $request->input('phone', ''),
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Contactpersoon toegevoegd.'];
        HttpResponse::redirect('/companies/' . $id);
    }

    public function updateContact(HttpRequest $request, string $id, string $contactId): void
    {
        $updated = $this->contacts->update((int)$contactId, [
            'name' => $request->input('name', ''),
            'first_name' => $request->input('first_name', ''),
            'initials' => $request->input('initials', ''),
            'prefix' => $request->input('prefix', ''),
            'function' => $request->input('function', ''),
            'title' => $request->input('title', ''),
            'email' => $request->input('email', ''),
            'phone' => $request->input('phone', ''),
        ]);
        if ($updated === null) {
            HttpResponse::html('<h1>Contactpersoon niet gevonden</h1>', 404);
            return;
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Contactpersoon bijgewerkt.'];
        HttpResponse::redirect('/companies/' . $id);
    }

    public function deleteContact(string $id, string $contactId): void
    {
        if (!$this->contacts->delete((int)$contactId)) {
            HttpResponse::html('<h1>Contactpersoon niet gevonden</h1>', 404);
            return;
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Contactpersoon verwijderd.'];
        HttpResponse::redirect('/companies/' . $id);
    }
}
