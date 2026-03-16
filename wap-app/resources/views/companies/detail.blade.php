@extends('layouts.iwa')

@section('title', $company->name)
@section('eyebrow', 'Bedrijf detail')
@section('page-title', $company->name)
@section('page-subtitle', 'Bedrijfsgegevens, contactpersonen en gekoppelde abonnementen.')

@section('back-button')
    <a class="secondary-button" href="{{ route('companies.index') }}">Terug naar bedrijven</a>
@endsection

@section('content')

{{-- Bedrijfsgegevens --}}
<section class="panel">
    <div class="panel-header"><h2>Bedrijfsgegevens</h2></div>
    <div class="details-grid">
        <div><strong>Stad</strong><div>{{ $company->city ?? '-' }}</div></div>
        <div><strong>Adres</strong><div>{{ trim(($company->street ?? '') . ' ' . ($company->number ?? '') . ' ' . ($company->number_additional ?? '')) ?: '-' }}</div></div>
        <div><strong>Postcode</strong><div>{{ $company->zip_code ?? '-' }}</div></div>
        <div><strong>Land</strong><div>{{ $company->country_name ?? '-' }}</div></div>
        <div><strong>E-mail</strong><div>{{ $company->email ?? '-' }}</div></div>
    </div>
</section>

{{-- Contactpersonen --}}
<section class="panel" style="margin-top:18px;">
    <div class="panel-header"><h2>Contactpersonen</h2></div>
    <div class="table-wrapper">
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Functie</th>
                    <th>Titel</th>
                    <th>E-mail</th>
                    <th>Telefoon</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contacts as $contact)
                <tr>
                    <td>{{ trim(($contact->title ?? '') . ' ' . ($contact->first_name ?? '') . ' ' . ($contact->prefix ?? '') . ' ' . ($contact->name ?? '')) }}</td>
                    <td>{{ $contact->function ?? '-' }}</td>
                    <td>{{ $contact->title ?? '-' }}</td>
                    <td>{{ $contact->email ?? '-' }}</td>
                    <td>{{ $contact->phone ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="muted">Geen contactpersonen gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Gekoppelde abonnementen --}}
<section class="panel" style="margin-top:18px;">
    <div class="panel-header"><h2>Gekoppelde abonnementen</h2></div>
    <div class="table-wrapper">
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th>Identifier</th>
                    <th>Type</th>
                    <th>Looptijd</th>
                    <th>Prijs</th>
                    <th>Stations</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $sub)
                <tr>
                    <td><a href="{{ route('subscriptions.show', $sub->identifier) }}">{{ $sub->identifier }}</a></td>
                    <td>{{ $sub->type_name }}</td>
                    <td>{{ $sub->start_date }} t/m {{ $sub->end_date ?? 'Doorlopend' }}</td>
                    <td>&euro; {{ number_format($sub->price, 2, ',', '.') }}</td>
                    <td>{{ $sub->station_count }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="muted">Geen abonnementen gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
