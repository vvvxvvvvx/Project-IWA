@extends('layouts.iwa')

@section('title', 'Contractinzicht')
@section('eyebrow', 'Contractbeheer')
@section('page-title', 'Contractinzicht')
@section('page-subtitle', 'Samengevat overzicht voor rollen die snel inzicht nodig hebben in contracten, gebruikers en query’s.')

@section('back-button')
    <div class="inline-form">
        <a class="secondary-button compact-button" href="{{ route('contracts.index') }}">Terug naar contracten</a>
        <a class="secondary-button compact-button" href="{{ route('contracts.authorized-users.index') }}">Geautoriseerde gebruikers</a>
    </div>
@endsection

@section('content')
<section class="summary-grid contract-summary-grid">
    <article class="summary-card">
        <span class="summary-label">Contracten</span>
        <strong class="summary-value">{{ $summary['contract_count'] }}</strong>
        <span class="summary-subtext">Totaal aantal geregistreerde contracten.</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Actieve contracten</span>
        <strong class="summary-value">{{ $summary['active_count'] }}</strong>
        <span class="summary-subtext">Lopend of zonder einddatum.</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Geautoriseerde gebruikers</span>
        <strong class="summary-value">{{ $summary['authorized_user_count'] }}</strong>
        <span class="summary-subtext">Gebruikers met vastgelegde toegang.</span>
    </article>
    <article class="summary-card">
        <span class="summary-label">Queries</span>
        <strong class="summary-value">{{ $summary['query_count'] }}</strong>
        <span class="summary-subtext">Ingerichte query’s voor data-ontsluiting.</span>
    </article>
</section>

<section class="quick-links-grid-3">
    <a class="quick-link-card" href="{{ route('contracts.index') }}">
        <strong>Contracten openen</strong>
        <span>Ga naar het volledige contractoverzicht en open direct een detailpagina.</span>
    </a>
    <a class="quick-link-card" href="{{ route('contracts.authorized-users.index') }}">
        <strong>Gebruikersoverzicht</strong>
        <span>Bekijk centraal welke gebruikers toegang hebben per contract.</span>
    </a>
    @if(auth()->user()?->hasTask('manage_contracts'))
        <a class="quick-link-card" href="{{ route('contracts.create') }}">
            <strong>Nieuw contract</strong>
            <span>Maak een nieuw contract aan in dezelfde beheerflow.</span>
        </a>
    @endif
</section>

<article class="panel contract-table-panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Recent toegevoegde of aangepaste contracten</h2>
            <p class="muted">Snelle ingang voor commercieel medewerker of functioneel beheerder zonder direct door de hele lijst te hoeven gaan.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table data-table-comfortable contract-table">
            <thead>
                <tr>
                    <th>Contract</th>
                    <th>Bedrijf</th>
                    <th>Type</th>
                    <th>Looptijd</th>
                    <th>Gebruikers</th>
                    <th>Queries</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentContracts as $contract)
                <tr>
                    <td class="table-emphasis">{{ $contract->identifier }}</td>
                    <td>{{ $contract->company_name }}</td>
                    <td>{{ $contract->type_name }}</td>
                    <td>{{ $contract->start_date ? \Illuminate\Support\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}<div class="table-subtext">Tot {{ $contract->end_date ? \Illuminate\Support\Carbon::parse($contract->end_date)->format('d-m-Y') : 'Doorlopend' }}</div></td>
                    <td>{{ $contract->authorized_user_count }}</td>
                    <td>{{ $contract->query_count }}</td>
                    <td><a class="secondary-button compact-button" href="{{ route('contracts.show', $contract->identifier) }}">Open contract</a></td>
                </tr>
                @empty
                <tr><td colspan="7" class="muted">Er zijn nog geen contracten beschikbaar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
