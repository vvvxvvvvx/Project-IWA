@extends('layouts.iwa')

@section('title', $contract ? 'Contract wijzigen' : 'Contract toevoegen')
@section('eyebrow', 'Contractbeheer')
@section('page-title', $contract ? 'Contract wijzigen' : 'Contract toevoegen')
@section('page-subtitle', 'Beheer contractgegevens in dezelfde stijl als de rest van het platform, met duidelijke scheiding tussen basisgegevens en tokenbeheer.')

@section('back-button')
    <a class="secondary-button compact-button" href="{{ $contract ? route('contracts.show', $contract->identifier) : route('contracts.index') }}">Terug</a>
@endsection

@section('content')
<article class="panel contract-form-panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>{{ $contract ? 'Contract bewerken' : 'Nieuw contract' }}</h2>
            <p class="muted">Dit formulier schrijft naar <code>subscriptions</code>, omdat contracten in dit platform voortbouwen op dezelfde commerciële basis als abonnementen.</p>
        </div>
    </div>

    <form method="POST" action="{{ $contract ? route('contracts.update', $contract->identifier) : route('contracts.store') }}" class="contract-form-layout">
        @csrf
        @if($contract) @method('PUT') @endif

        <div class="form-grid contract-form-grid">
            <label>
                <span>Bedrijf</span>
                <select name="company">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ (string) old('company', $contract->company ?? '') === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Type</span>
                <select name="type">
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ (string) old('type', $contract->type ?? '') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Startdatum</span>
                <input name="start_date" type="date" value="{{ old('start_date', $contract->start_date ?? '') }}">
            </label>

            <label>
                <span>Einddatum</span>
                <input name="end_date" type="date" value="{{ old('end_date', $contract->end_date ?? '') }}">
            </label>

            <label>
                <span>Prijs</span>
                <input name="price" type="number" step="0.01" value="{{ old('price', $contract->price ?? '') }}" placeholder="0,00">
            </label>

            <label>
                <span>Identifier</span>
                <input name="identifier" value="{{ old('identifier', $contract->identifier ?? '') }}" placeholder="Bijvoorbeeld IWA-CONTRACT-001">
            </label>

            <label>
                <span>Token</span>
                <input name="token" value="{{ old('token', $contract->token ?? '') }}" placeholder="Automatisch of handmatig token">
            </label>

            <label>
                <span>Notities</span>
                <textarea name="notes">{{ old('notes', $contract->notes ?? '') }}</textarea>
            </label>
        </div>

        <div class="table-actions contract-form-actions">
            <button class="primary-button compact-button" type="submit">Opslaan</button>
            <a class="secondary-button compact-button" href="{{ $contract ? route('contracts.show', $contract->identifier) : route('contracts.index') }}">Annuleren</a>
        </div>
    </form>
</article>

@if($contract)
<article class="panel contract-token-panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Tokenbeheer</h2>
            <p class="muted">Voer tokenacties uit zonder het contractscherm te verlaten. Dit sluit aan op dezelfde beheerlogica als bij abonnementen.</p>
        </div>
        <div class="table-actions">
            <form method="POST" action="{{ route('contracts.token.regenerate', $contract->identifier) }}">
                @csrf
                <button class="primary-button compact-button" type="submit">Nieuw token genereren</button>
            </form>
            <form method="POST" action="{{ route('contracts.token.sent', $contract->identifier) }}">
                @csrf
                <button class="secondary-button compact-button" type="submit">Token als verstuurd markeren</button>
            </form>
        </div>
    </div>

    <div class="details-grid-2">
        <div>
            <strong>Huidig token</strong>
            <code class="inline-code-block">{{ $contract->token ?: '-' }}</code>
        </div>
        <div>
            <strong>Laatste notitie</strong>
            <div class="inline-code-block contract-note-box">{{ $contract->notes ?: '-' }}</div>
        </div>
    </div>
</article>
@endif
@endsection
