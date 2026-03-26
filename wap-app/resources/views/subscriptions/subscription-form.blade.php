{{--
    Dit formulier wordt gebruikt voor zowel aanmaken als wijzigen van abonnementen.

    Belangrijke koppelingen:
    - Routes staan in routes/web.php
    - Opslaan loopt via SubscriptionController@store en @update
    - Token-acties lopen via SubscriptionController@regenerateToken en @markTokenSent

    Als je route-namen of controller-methodes wijzigt, pas deze view dan ook aan.
--}}
@extends('layouts.iwa')

@section('title', $subscription ? 'Abonnement wijzigen' : 'Abonnement toevoegen')
@section('eyebrow', 'Abonnementenbeheer')
@section('page-title', $subscription ? 'Abonnement wijzigen' : 'Abonnement toevoegen')
@section('page-subtitle', 'Beheer van klant, type, looptijd en token in één formulier.')

@section('back-button')
    <a class="secondary-button" href="{{ $subscription ? route('subscriptions.show', $subscription->identifier) : route('subscriptions.index') }}">Terug</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>{{ $subscription ? 'Abonnement bewerken' : 'Nieuw abonnement' }}</h2>
            <p class="muted">
                Dit formulier schrijft direct naar de tabel <code>subscriptions</code>.
                Bij een bestaand abonnement kun je hieronder ook een nieuw token genereren of als verstuurd markeren.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ $subscription ? route('subscriptions.update', $subscription->identifier) : route('subscriptions.store') }}">
        @csrf
        @if($subscription) @method('PUT') @endif

        <div class="details-grid">
            <div>
                <strong>Bedrijf</strong>
                <select name="company">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ (string)old('company', $subscription->company ?? '') === (string)$company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <strong>Type</strong>
                <select name="type">
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ (string)old('type', $subscription->type ?? '') === (string)$type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div><strong>Startdatum</strong><input name="start_date" type="date" value="{{ old('start_date', $subscription->start_date ?? '') }}"></div>
            <div><strong>Einddatum</strong><input name="end_date" type="date" value="{{ old('end_date', $subscription->end_date ?? '') }}"></div>
            <div><strong>Prijs</strong><input name="price" type="number" step="0.01" value="{{ old('price', $subscription->price ?? '') }}"></div>
            <div><strong>Identifier</strong><input name="identifier" value="{{ old('identifier', $subscription->identifier ?? '') }}"></div>
            <div><strong>Token</strong><input name="token" value="{{ old('token', $subscription->token ?? '') }}"></div>
            <div><strong>Notities</strong><input name="notes" value="{{ old('notes', $subscription->notes ?? '') }}"></div>
        </div>

        <div class="inline-form" style="margin-top:18px;">
            <button class="primary-button" type="submit">Opslaan</button>
        </div>
    </form>
</article>

@if($subscription)
    {{--
        Deze sectie is alleen zichtbaar op de edit-pagina.
        - "Nieuw token genereren" werkt direct de kolom subscriptions.token bij.
        - "Token als verstuurd markeren" voegt een tijdstempel toe aan subscriptions.notes.
    --}}
    <article class="panel" style="margin-top:18px;">
        <div class="panel-header panel-header-stack">
            <div>
                <h2>Tokenbeheer</h2>
                <p class="muted">
                    Gebruik deze acties als het abonnement nieuwe toegangsgegevens nodig heeft.
                    Beide knoppen schrijven direct weg naar de database.
                </p>
            </div>
            <div class="inline-form">
                <form method="POST" action="{{ route('subscriptions.token.regenerate', $subscription->identifier) }}">
                    @csrf
                    <button class="primary-button" type="submit">Nieuw token genereren</button>
                </form>

                <form method="POST" action="{{ route('subscriptions.token.sent', $subscription->identifier) }}">
                    @csrf
                    <button class="secondary-button" type="submit">Token als verstuurd markeren</button>
                </form>
            </div>
        </div>

        <div class="details-grid">
            <div>
                <strong>Huidig token</strong>
                <div><code>{{ $subscription->token ?: '-' }}</code></div>
            </div>
            <div>
                <strong>Laatste notitie</strong>
                <div>{{ $subscription->notes ?: '-' }}</div>
            </div>
        </div>
    </article>
@endif
@endsection
