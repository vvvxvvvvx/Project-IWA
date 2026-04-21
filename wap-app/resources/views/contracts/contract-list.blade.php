@extends('layouts.iwa')

@section('title', 'Contracten')
@section('eyebrow', 'Contractbeheer')
@section('page-title', 'Contracten')

@section('back-button')
    <div class="inline-form">
        <a class="secondary-button compact-button" href="{{ route('contracts.overview') }}">Contractinzicht</a>
        <a class="secondary-button compact-button" href="{{ route('contracts.authorized-users.index') }}">Geautoriseerde gebruikers</a>
        @if(auth()->user()?->hasTask('manage_contracts'))
            <a class="secondary-button compact-button" href="{{ route('contracts.create') }}">Nieuw contract</a>
        @endif
    </div>
@endsection


@section('content')
<section class="panel contract-hero-panel">
    <div class="panel-header panel-header-stack contract-hero-header">
        <div>
            <h2>Contractoverzicht</h2>
            <p class="muted">Per contract zie je direct het bedrijf, de contractsoort, looptijd, status en de gekoppelde gebruikers en query’s.</p>
        </div>
    </div>
</section>

<article class="panel contract-table-panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Alle contracten</h2>
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
                    <th>Prijs</th>
                    <th>Gebruikers</th>
                    <th>Queries</th>
                    <th>API-calls</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contracts as $contract)
                <tr>
                    <td>
                        <a class="table-emphasis" href="{{ route('contracts.show', $contract->identifier) }}">{{ $contract->identifier }}</a>
                        <div class="table-subtext">{{ $contract->notes ? \Illuminate\Support\Str::limit($contract->notes, 70) : 'Geen extra notities vastgelegd.' }}</div>
                    </td>
                    <td>{{ $contract->company_name }}</td>
                    <td>{{ $contract->type_name }}</td>
                    <td>{{ $contract->status ?: '—' }}</td>
                    <td>
                        <div>{{ $contract->start_date ? \Illuminate\Support\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}</div>
                        <div class="table-subtext">Tot {{ $contract->end_date ? \Illuminate\Support\Carbon::parse($contract->end_date)->format('d-m-Y') : 'Doorlopend' }}</div>
                    </td>
                    <td class="table-emphasis">€ {{ number_format((float) $contract->price, 2, ',', '.') }}</td>
                    <td>{{ $contract->authorized_user_count }}</td>
                    <td>{{ $contract->query_count }}</td>
                    <td>{{ $contract->successful_calls ?? 0 }}</td>
                    <td>
                        <div class="table-actions">
                            <a class="secondary-button compact-button" href="{{ route('contracts.show', $contract->identifier) }}">Openen</a>
                            @if(auth()->user()?->hasTask('manage_contracts'))
                                <a class="secondary-button compact-button" href="{{ route('contracts.edit', $contract->identifier) }}">Bewerken</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">
                        <div class="empty-state">
                            <span class="muted">Er zijn nog geen contracten gevonden.</span>
                            @if(auth()->user()?->hasTask('manage_contracts'))
                                <a class="secondary-button compact-button" href="{{ route('contracts.create') }}">Maak je eerste contract</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
