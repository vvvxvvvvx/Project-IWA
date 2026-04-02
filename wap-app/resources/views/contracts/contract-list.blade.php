@extends('layouts.iwa')

@section('title', 'Contracten')
@section('eyebrow', 'Contractbeheer')
@section('page-title', 'Contractoverzicht')
@section('page-subtitle', 'Volledig geïntegreerd in de Laravel-webapp, in dezelfde huisstijl als de rest van het platform.')

@section('back-button')
    @if(auth()->user()?->hasTask('manage_contracts'))
        <a class="primary-button" href="{{ route('contracts.create') }}">Nieuw contract</a>
    @endif
@endsection

@section('content')
<section class="stats-grid">
    <article class="stat-card"><span class="stat-label">Contracten</span><strong>{{ $summary['contract_count'] }}</strong></article>
    <article class="stat-card"><span class="stat-label">Actieve contracten</span><strong>{{ $summary['active_count'] }}</strong></article>
    <article class="stat-card"><span class="stat-label">Geautoriseerde gebruikers</span><strong>{{ $summary['authorized_user_count'] }}</strong></article>
    <article class="stat-card"><span class="stat-label">Queries</span><strong>{{ $summary['query_count'] }}</strong></article>
</section>

<article class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div>
            <h2>Alle contracten</h2>
            <p class="muted">Deze pagina geeft hetzelfde totaalbeeld als het abonnementenoverzicht, maar dan aangevuld met contractspecifiek beheer.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Contract</th>
                    <th>Bedrijf</th>
                    <th>Type</th>
                    <th>Looptijd</th>
                    <th>Prijs</th>
                    <th>Stations</th>
                    <th>Gebruikers</th>
                    <th>Queries</th>
                    <th>Succesvolle API-calls</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contracts as $contract)
                <tr>
                    <td><a href="{{ route('contracts.show', $contract->identifier) }}">{{ $contract->identifier }}</a></td>
                    <td>{{ $contract->company_name }}</td>
                    <td>{{ $contract->type_name }}</td>
                    <td>{{ $contract->start_date }} – {{ $contract->end_date ?? 'Doorlopend' }}</td>
                    <td>€ {{ number_format((float) $contract->price, 2, ',', '.') }}</td>
                    <td>{{ $contract->station_count }}</td>
                    <td>{{ $contract->authorized_user_count }}</td>
                    <td>{{ $contract->query_count }}</td>
                    <td>{{ $contract->successful_calls ?? 0 }}</td>
                    <td>
                        <div class="inline-form">
                            <a class="secondary-button" href="{{ route('contracts.show', $contract->identifier) }}">Openen</a>
                            @if(auth()->user()?->hasTask('manage_contracts'))
                                <a class="secondary-button" href="{{ route('contracts.edit', $contract->identifier) }}">Wijzigen</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="muted">Er zijn nog geen contracten gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
