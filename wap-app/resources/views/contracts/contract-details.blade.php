{{-- Detailpagina voor één contract met activity-log. --}}
@extends('layouts.iwa')

@section('title', $contract->identifier)
@section('eyebrow', 'Contract detail')
@section('page-title', $contract->identifier)
@section('page-subtitle', 'Hoe het contract samenhangt met REST-API toegang en abonnementgegevens.')

@section('back-button')
    <a class="secondary-button" href="{{ route('contracts.index') }}">Terug naar contracten</a>
@endsection

@section('content')

{{-- Contractsamenvatting --}}
<section class="panel">
    <div class="panel-header"><h2>Contractsamenvatting</h2></div>
    <div class="details-grid">
        <div><strong>Bedrijf</strong><div>{{ $contract->company_name }}</div></div>
        <div><strong>Abonnementtype</strong><div>{{ $contract->type_name }}</div></div>
        <div><strong>Prijs</strong><div>&euro; {{ number_format($contract->price, 2, ',', '.') }}</div></div>
        <div><strong>Looptijd</strong><div>{{ $contract->start_date }} t/m {{ $contract->end_date ?? 'Doorlopend' }}</div></div>
        <div><strong>Token</strong><div><code>{{ $contract->token }}</code></div></div>
        <div><strong>Notities</strong><div>{{ $contract->notes ?? '-' }}</div></div>
    </div>
</section>

{{-- REST-API activiteit --}}
<section class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div>
            <h2>REST-API activiteit</h2>
            <p class="muted">Contracten halen hier hun operationele informatie uit de REST-API/endpointlaag.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Tijd</th>
                    <th>Endpoint</th>
                    <th>Authorized</th>
                    <th>Bestanden</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activity as $row)
                <tr>
                    <td>{{ $row->activity_date }}</td>
                    <td>{{ $row->activity_time }}</td>
                    <td>{{ $row->endpoint_used }}</td>
                    <td>{{ (int)($row->authorized ?? 0) === 1 ? 'Ja' : 'Nee' }}</td>
                    <td>{{ $row->files_downloaded ?? 0 }}</td>
                    <td>{{ $row->data_transferred ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="muted">Geen activiteit geregistreerd.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Link naar abonnement --}}
<section class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div>
            <h2>Bijbehorend abonnement</h2>
            <p class="muted">Het contract verwijst door naar het operationele abonnement met stations en token.</p>
        </div>
    </div>
    <a class="primary-button" href="{{ route('subscriptions.show', $contract->identifier) }}">
        Open abonnement {{ $contract->identifier }}
    </a>
</section>

@endsection
