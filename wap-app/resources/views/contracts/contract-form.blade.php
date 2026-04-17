@extends('layouts.iwa')

@section('title', $contract ? 'Contract wijzigen' : 'Contract toevoegen')
@section('eyebrow', 'Contractbeheer')
@section('page-title', $contract ? 'Contract wijzigen' : 'Contract toevoegen')
@section('page-subtitle', 'Contracten gebruiken dezelfde huisstijl en beheerlogica als abonnementen, aangevuld met geautoriseerde gebruikers en queries.')

@section('back-button')
    <a class="secondary-button" href="{{ $contract ? route('contracts.show', $contract->identifier) : route('contracts.index') }}">Terug</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>{{ $contract ? 'Contract bewerken' : 'Nieuw contract' }}</h2>
            <p class="muted">Dit formulier schrijft naar <code>subscriptions</code>, omdat contracten in dit platform het commerciële en technische contractdomein bovenop abonnementgegevens vormen.</p>
        </div>
    </div>

    <form method="POST" action="{{ $contract ? route('contracts.update', $contract->identifier) : route('contracts.store') }}">
        @csrf
        @if($contract) @method('PUT') @endif

        <div class="details-grid">
            <div>
                <strong>Bedrijf</strong>
                <select name="company">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ (string) old('company', $contract->company ?? '') === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <strong>Type</strong>
                <select name="type">
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ (string) old('type', $contract->type ?? '') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div><strong>Startdatum</strong><input name="start_date" type="date" value="{{ old('start_date', $contract->start_date ?? '') }}"></div>
            <div><strong>Einddatum</strong><input name="end_date" type="date" value="{{ old('end_date', $contract->end_date ?? '') }}"></div>
            <div><strong>Prijs</strong><input name="price" type="number" step="0.01" value="{{ old('price', $contract->price ?? '') }}"></div>
            <div><strong>Identifier</strong><input name="identifier" value="{{ old('identifier', $contract->identifier ?? '') }}"></div>
            <div><strong>Token</strong><input name="token" value="{{ old('token', $contract->token ?? '') }}"></div>
            <div><strong>Notities</strong><input name="notes" value="{{ old('notes', $contract->notes ?? '') }}"></div>
        </div>

        <div class="inline-form" style="margin-top:18px;">
            <button class="primary-button" type="submit">Opslaan</button>
        </div>
    </form>
</article>

@if($contract)
<article class="panel" style="margin-top:18px;">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Tokenbeheer</h2>
            <p class="muted">Deze acties gedragen zich hetzelfde als bij abonnementen, maar zijn nu rechtstreeks beschikbaar binnen contractbeheer.</p>
        </div>
        <div class="inline-form">
            <form method="POST" action="{{ route('contracts.token.regenerate', $contract->identifier) }}">
                @csrf
                <button class="primary-button" type="submit">Nieuw token genereren</button>
            </form>
            <form method="POST" action="{{ route('contracts.token.sent', $contract->identifier) }}">
                @csrf
                <button class="secondary-button" type="submit">Token als verstuurd markeren</button>
            </form>
        </div>
    </div>
    <div class="details-grid">
        <div><strong>Huidig token</strong><div><code>{{ $contract->token ?: '-' }}</code></div></div>
        <div><strong>Laatste notitie</strong><div>{{ $contract->notes ?: '-' }}</div></div>
    </div>
</article>
@endif
@endsection
