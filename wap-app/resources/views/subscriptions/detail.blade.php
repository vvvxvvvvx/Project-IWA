{{--
    Detailpagina van een abonnement.

    Belangrijke koppelingen:
    - Tokenknoppen gebruiken dezelfde routes als het edit-formulier
    - Als je tokenlogica wijzigt, pas dan ook resources/views/subscriptions/form.blade.php aan
--}}
@extends('layouts.iwa')

@section('title', $subscription->identifier)
@section('eyebrow', 'Abonnement detail')
@section('page-title', $subscription->identifier)
@section('page-subtitle', 'Detailinformatie over looptijd, gekoppelde stations en endpointactiviteit.')

@section('back-button')
    <a class="secondary-button" href="{{ route('subscriptions.index') }}">Terug naar abonnementen</a>
@endsection

@section('content')
<section class="panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Abonnement</h2>
            <p class="muted">Gebruik deze pagina voor beheer van token, type en gekoppelde contractinformatie.</p>
        </div>
        <div class="inline-form">
            <a class="secondary-button" href="{{ route('subscriptions.edit', $subscription->identifier) }}">Abonnement wijzigen</a>
            <form method="POST" action="{{ route('subscriptions.destroy', $subscription->identifier) }}" onsubmit="return confirm('Abonnement verwijderen?');">
                @csrf @method('DELETE')
                <button class="secondary-button" type="submit">Verwijderen</button>
            </form>
        </div>
    </div>
    <div class="details-grid">
        <div><strong>Bedrijf</strong><div>{{ $subscription->company_name }}</div></div>
        <div><strong>Type</strong><div>{{ $subscription->type_name }}</div></div>
        <div><strong>Startdatum</strong><div>{{ $subscription->start_date }}</div></div>
        <div><strong>Einddatum</strong><div>{{ $subscription->end_date ?? 'Doorlopend' }}</div></div>
        <div><strong>Prijs</strong><div>&euro; {{ number_format($subscription->price, 2, ',', '.') }}</div></div>
        <div><strong>Token</strong><div><code>{{ $subscription->token }}</code></div></div>
        <div><strong>Notities</strong><div>{{ $subscription->notes ?? '-' }}</div></div>
    </div>
    <div class="inline-form" style="margin-top:18px;">
        <form method="POST" action="{{ route('subscriptions.token.regenerate', $subscription->identifier) }}">@csrf<button class="primary-button" type="submit">Nieuw token genereren</button></form>
        <form method="POST" action="{{ route('subscriptions.token.sent', $subscription->identifier) }}">@csrf<button class="secondary-button" type="submit">Token als verstuurd markeren</button></form>
        <a class="secondary-button" href="{{ route('contracts.show', $subscription->identifier) }}">Open contractpagina</a>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header"><h2>Beschikbare stations</h2></div>
    <div class="table-wrapper">
        <table class="data-table compact-table data-table-comfortable">
            <thead><tr><th>STN</th><th>Locatie</th><th>Breedtegraad</th><th>Lengtegraad</th></tr></thead>
            <tbody>
                @foreach ($stations as $station)
                <tr>
                    <td><a href="{{ route('stations.show', $station->stn) }}">{{ $station->stn }}</a></td>
                    <td>{{ $station->location_label ?? 'Onbekend' }}</td>
                    <td>{{ $station->lat ?? '-' }}</td>
                    <td>{{ $station->lon ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="panel" style="margin-top:18px;">
    <div class="panel-header"><h2>Endpoint-activiteit</h2></div>
    <div class="table-wrapper">
        <table class="data-table compact-table data-table-comfortable">
            <thead><tr><th>Datum</th><th>Tijd</th><th>Endpoint</th><th>Authorized</th><th>Bestanden</th></tr></thead>
            <tbody>
                @forelse ($activity as $row)
                <tr><td>{{ $row->activity_date }}</td><td>{{ $row->activity_time }}</td><td>{{ $row->endpoint_used }}</td><td>{{ (int)($row->authorized ?? 0) === 1 ? 'Ja' : 'Nee' }}</td><td>{{ $row->files_downloaded ?? 0 }}</td></tr>
                @empty
                <tr><td colspan="5" class="muted">Geen activiteit geregistreerd.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
