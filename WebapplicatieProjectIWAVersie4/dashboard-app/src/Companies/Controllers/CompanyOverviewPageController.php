<?php

declare(strict_types=1);

namespace App\Companies\Controllers;

use App\Core\Http\HttpResponse;
use App\Companies\Repositories\CompanyRepository;
use App\Core\Support\PhpViewRenderer;

final class CompanyOverviewPageController
{
    public function __construct(private readonly CompanyRepository $companies = new CompanyRepository()) {}

    public function index(): void
    {
        HttpResponse::html(PhpViewRenderer::render('companies/index', [
            'companies' => $this->companies->all(),
        ]));
    }
}
