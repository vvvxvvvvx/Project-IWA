<?php

declare(strict_types=1);

namespace App\Companies\Controllers;

use App\Core\Http\HttpRequest;
use App\Core\Http\HttpResponse;
use App\Core\Support\PhpViewRenderer;
use App\Companies\Repositories\CompanyRepository;

final class CompanyOverviewPageController
{
    public function __construct(private readonly CompanyRepository $companies = new CompanyRepository()) {}

    public function index(HttpRequest $request): void
    {
        $query = trim((string)$request->input('q', ''));
        $companies = $this->companies->all();
        if ($query !== '') {
            $companies = array_values(array_filter($companies, static function (array $company) use ($query): bool {
                $haystack = strtolower(implode(' ', [
                    (string)($company['name'] ?? ''),
                    (string)($company['city'] ?? ''),
                    (string)($company['country_name'] ?? ''),
                    (string)($company['email'] ?? ''),
                ]));
                return str_contains($haystack, strtolower($query));
            }));
        }
        HttpResponse::html(PhpViewRenderer::render('companies/index', [
            'companies' => $companies,
            'filters' => ['q' => $query],
        ]));
    }

    public function store(HttpRequest $request): void
    {
        $company = $this->companies->create([
            'name' => $request->input('name', ''),
            'city' => $request->input('city', ''),
            'street' => $request->input('street', ''),
            'number' => $request->input('number', ''),
            'number_additional' => $request->input('number_additional', ''),
            'zip_code' => $request->input('zip_code', ''),
            'country_code' => $request->input('country_code', 'NL'),
            'email' => $request->input('email', ''),
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Bedrijf aangemaakt.'];
        HttpResponse::redirect('/companies/' . $company['id']);
    }
}
