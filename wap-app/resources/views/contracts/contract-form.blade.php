@extends('layouts.iwa')

@section('title', $contract ? 'Contract wijzigen' : 'Contract toevoegen')
@section('eyebrow', 'Contractbeheer')
@section('page-title', $contract ? 'Contract wijzigen' : 'Contract toevoegen')

@section('back-button')
    <a class="secondary-button compact-button" href="{{ $contract ? route('contracts.show', $contract->identifier) : route('contracts.index') }}">Terug</a>
@endsection

@section('content')
<article class="panel contract-form-panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>{{ $contract ? 'Contract bewerken' : 'Nieuw contract' }}</h2>
            <p class="muted">De contractflow schrijft naar <code>contracts</code> en gebruikt vaste contracttypes, een app-URL en een eerste admin user.</p>
        </div>
    </div>

    <form method="POST" action="{{ $contract ? route('contracts.update', $contract->identifier) : route('contracts.store') }}" class="contract-form-layout stack-form">
        @csrf
        @if($contract) @method('PUT') @endif

        <div class="form-grid contract-form-grid">
            <label>
                <span>Bedrijf</span>
                <select name="company_id">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ (string) old('company_id', $contract->company_id ?? '') === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Contractsoort</span>
                <select name="contract_type_id">
                    @foreach($contractTypes as $type)
                        <option value="{{ $type->id }}" {{ (string) old('contract_type_id', $contract->contract_type_id ?? '') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Status</span>
                <select name="status">
                    @foreach($availableStatuses as $status)
                        <option value="{{ $status }}" {{ old('status', $contract->status ?? 'Concept') === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Identifier</span>
                <input name="identifier" value="{{ old('identifier', $contract->identifier ?? '') }}" placeholder="Bijvoorbeeld IWA-CONTRACT-001">
            </label>

            <label style="grid-column:1 / -1;">
                <span>Omschrijving</span>
                <input name="description" value="{{ old('description', $contract->description ?? '') }}" placeholder="Korte omschrijving van dit contract">
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
                <span>App-URL</span>
                <input name="app_url" value="{{ old('app_url', $contract->app_url ?? '') }}" placeholder="https://app.voorbeeld.nl">
            </label>

            <label>
                <span>Prijs</span>
                <input name="price" type="number" step="0.01" value="{{ old('price', $contract->price ?? '') }}" placeholder="0.00">
            </label>

            <label>
                <span>Contracttoken</span>
                <input name="api_token" value="{{ old('api_token', $contract->api_token ?? '') }}" placeholder="Automatisch of handmatig token">
            </label>

            <label style="grid-column:1 / -1;">
                <span>Notities</span>
                <textarea name="notes">{{ old('notes', $contract->notes ?? '') }}</textarea>
            </label>
        </div>

        @if(! $contract)
        <section class="panel" style="margin-top:18px;">
            <div class="panel-header panel-header-stack">
                <div>
                    <h2>Eerste admin gebruiker</h2>
                </div>
            </div>
            <div class="form-grid contract-form-grid">
                <label>
                    <span>Naam</span>
                    <input name="admin_name" value="{{ old('admin_name') }}">
                </label>
                <label>
                    <span>E-mail</span>
                    <input name="admin_email" type="email" value="{{ old('admin_email') }}">
                </label>
                <label>
                    <span>User identifier</span>
                    <input name="admin_user_identifier" value="{{ old('admin_user_identifier') }}">
                </label>
                <label>
                    <span>Wachtwoord</span>
                    <input name="admin_password" type="password">
                </label>
                <label style="grid-column:1 / -1;">
                    <span>Notities admin</span>
                    <textarea name="admin_notes">{{ old('admin_notes') }}</textarea>
                </label>
            </div>
        </section>
        @endif

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
            <p class="muted">Contract-API’s gebruiken een eigen token/jwt-flow en zijn volledig losgekoppeld van abonnementen.</p>
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
            <code class="inline-code-block">{{ $contract->api_token ?: '-' }}</code>
        </div>
        <div>
            <strong>App-URL</strong>
            <div class="inline-code-block">{{ $contract->app_url ?: '-' }}</div>
        </div>
    </div>
</article>
@endif
@endsection
