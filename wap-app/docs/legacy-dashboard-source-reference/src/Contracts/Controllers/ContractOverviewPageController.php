<?php

declare(strict_types=1);

namespace App\Contracts\Controllers;

use App\Core\Http\HttpResponse;
use App\Contracts\Repositories\ContractRepository;
use App\Core\Support\PhpViewRenderer;

final class ContractOverviewPageController
{
    public function __construct(private readonly ContractRepository $contracts = new ContractRepository()) {}

    public function index(): void
    {
        HttpResponse::html(PhpViewRenderer::render('contracts/index', [
            'contracts' => $this->contracts->all(),
        ]));
    }
}
