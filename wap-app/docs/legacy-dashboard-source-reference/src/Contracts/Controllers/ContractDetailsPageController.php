<?php

declare(strict_types=1);

namespace App\Contracts\Controllers;

use App\Core\Http\HttpResponse;
use App\Contracts\Repositories\ContractRepository;
use App\Core\Support\PhpViewRenderer;

final class ContractDetailsPageController
{
    public function __construct(private readonly ContractRepository $contracts = new ContractRepository()) {}

    public function show(string $identifier): void
    {
        $contract = $this->contracts->findByIdentifier($identifier);
        if ($contract === null) {
            HttpResponse::html('<h1>Contract niet gevonden</h1>', 404);
            return;
        }
        HttpResponse::html(PhpViewRenderer::render('contracts/detail', [
            'contract' => $contract,
        ]));
    }
}
