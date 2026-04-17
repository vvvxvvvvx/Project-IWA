@extends('layouts.iwa')

@section('title', 'Geautoriseerde gebruikers')
@section('eyebrow', 'Gebruikersbeheer')
@section('page-title', 'Gebruikersoverzicht per contract')
@section('page-subtitle', 'Centraal overzicht van alle vastgelegde contractgebruikers binnen de nieuwe contractlaag.')

@section('back-button')
    <div class="inline-form">
        <a class="secondary-button compact-button" href="{{ route('contracts.overview') }}">Contractinzicht</a>
        <a class="secondary-button compact-button" href="{{ route('contracts.index') }}">Terug naar contracten</a>
    </div>
@endsection

@section('content')
<section class="summary-grid contract-summary-grid">
    <article class="summary-card"><span class="summary-label">Gebruikers</span><strong class="summary-value">{{ $summary['authorized_user_count'] }}</strong><span class="summary-subtext">Totaal aantal vastgelegde contractgebruikers.</span></article>
    <article class="summary-card"><span class="summary-label">Contracten met gebruikers</span><strong class="summary-value">{{ $summary['contract_count'] }}</strong><span class="summary-subtext">Aantal contracten waar al gebruikers aan hangen.</span></article>
    <article class="summary-card"><span class="summary-label">Actieve gebruikers</span><strong class="summary-value">{{ $summary['active_count'] }}</strong><span class="summary-subtext">Gebruikers met status Actief.</span></article>
</section>

<article class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div>
            <h2>Overzicht</h2>
            <p class="muted">Gebruik dit overzicht om per contract snel te zien wie toegang heeft en welke rol of status is vastgelegd.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Contract</th>
                    <th>Bedrijf</th>
                    <th>Soort</th>
                    <th>Naam</th>
                    <th>E-mail</th>
                    <th>Login-id</th>
                    <th>Machtiging</th>
                    <th>Rol</th>
                    <th>Status</th>
                    <th>Laatst bijgewerkt</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($authorizedUsers as $user)
                <tr>
                    <td>{{ $user->contract_identifier }}</td>
                    <td>{{ $user->company_name }}</td>
                    <td>{{ $user->type_name }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->user_identifier ?: '—' }}</td>
                    <td>{{ strtoupper($user->permission_level ?: 'user') }}</td>
                    <td>{{ $user->role_label ?: '—' }}</td>
                    <td>{{ $user->status ?: '—' }}</td>
                    <td>{{ $user->updated_at ? \Illuminate\Support\Carbon::parse($user->updated_at)->format('d-m-Y H:i') : '—' }}</td>
                    <td><a class="secondary-button compact-button" href="{{ route('contracts.show', $user->contract_identifier) }}">Open contract</a></td>
                </tr>
                @empty
                <tr><td colspan="11" class="muted">Er zijn nog geen geautoriseerde gebruikers vastgelegd.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
@endsection
