@extends('layouts.iwa')

@section('title', 'Contractinzicht')
@section('eyebrow', 'Contractbeheer')
@section('page-title', 'Contractinzicht')

@section('back-button')
    <div class="inline-form">
        <a class="secondary-button compact-button" href="{{ route('contracts.index') }}">Terug naar contracten</a>
        <a class="secondary-button compact-button" href="{{ route('contracts.authorized-users.index') }}">Geautoriseerde gebruikers</a>
    </div>
@endsection

@section('content')
<section class="summary-grid contract-summary-grid">
    <article class="summary-card"><span class="summary-label">Contracten</span><strong class="summary-value">{{ $summary['contract_count'] }}</strong><span class="summary-subtext">Totaal geregistreerde contracten.</span></article>
    <article class="summary-card"><span class="summary-label">Actieve contracten</span><strong class="summary-value">{{ $summary['active_count'] }}</strong><span class="summary-subtext">Lopend of zonder einddatum.</span></article>
    <article class="summary-card"><span class="summary-label">Geautoriseerde gebruikers</span><strong class="summary-value">{{ $summary['authorized_user_count'] }}</strong><span class="summary-subtext">Toegang op contractniveau.</span></article>
    <article class="summary-card"><span class="summary-label">Queries</span><strong class="summary-value">{{ $summary['query_count'] }}</strong><span class="summary-subtext">Gedefinieerde contractqueries.</span></article>
</section>

<article class="panel contract-table-panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Recente contracten</h2>
            <p class="muted">Snelle ingang naar de laatst zichtbare contractrecords.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table data-table-comfortable contract-table">
            <thead>
                <tr>
                    <th>Contract</th>
                    <th>Bedrijf</th>
                    <th>Soort</th>
                    <th>Status</th>
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
                    <td>{{ $contract->status ?: '—' }}</td>
                    <td>{{ $contract->start_date ? \Illuminate\Support\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}<div class="table-subtext">Tot {{ $contract->end_date ? \Illuminate\Support\Carbon::parse($contract->end_date)->format('d-m-Y') : 'Doorlopend' }}</div></td>
                    <td>{{ $contract->authorized_user_count }}</td>
                    <td>{{ $contract->query_count }}</td>
                    <td><a class="secondary-button compact-button" href="{{ route('contracts.show', $contract->identifier) }}">Open contract</a></td>
                </tr>
                @empty
                <tr><td colspan="8" class="muted">Er zijn nog geen contracten beschikbaar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
