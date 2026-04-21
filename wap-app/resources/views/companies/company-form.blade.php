{{--
    Dit formulier wordt gebruikt voor zowel aanmaken als wijzigen van bedrijven.

    Belangrijke koppelingen:
    - Routes staan in routes/web.php
    - Opslaan loopt via CompanyController@store en @update
    - Contactpersonen komen uit de tabel relations
    - De aparte contact-formulierpagina is resources/views/companies/company-contact-form.blade.php

    Als je contactpersoonvelden of routes wijzigt, pas dan zowel deze view als
    CompanyController@edit, @createContact, @storeContact en @updateContact aan.
--}}
@extends('layouts.iwa')

@section('title', $company ? 'Bedrijf wijzigen' : 'Bedrijf toevoegen')
@section('eyebrow', 'Klantenbeheer')
@section('page-title', $company ? 'Bedrijf wijzigen' : 'Bedrijf toevoegen')
@section('page-subtitle', 'Wijzig alleen de velden die nodig zijn, zodat de groepsstructuur overzichtelijk blijft.')

@section('back-button')
    <a class="secondary-button" href="{{ $company ? route('companies.show', $company->id) : route('companies.index') }}">Terug</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>{{ $company ? 'Bedrijfsgegevens bewerken' : 'Nieuw bedrijf' }}</h2>
            <p class="muted">
                Dit formulier schrijft direct naar de tabel <code>companies</code>.
                Contactpersonen beheer je op deze pagina in de aparte sectie hieronder.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ $company ? route('companies.update', $company->id) : route('companies.store') }}">
        @csrf
        @if($company) @method('PUT') @endif

        <div class="details-grid">
            <div><strong>Naam</strong><input name="name" value="{{ old('name', $company->name ?? '') }}"></div>
            <div><strong>Stad</strong><input name="city" value="{{ old('city', $company->city ?? '') }}"></div>
            <div><strong>Straat</strong><input name="street" value="{{ old('street', $company->street ?? '') }}"></div>
            <div><strong>Nummer</strong><input name="number" type="number" value="{{ old('number', $company->number ?? '') }}"></div>
            <div><strong>Toevoeging</strong><input name="number_additional" value="{{ old('number_additional', $company->number_additional ?? '') }}"></div>
            <div><strong>Postcode</strong><input name="zip_code" value="{{ old('zip_code', $company->zip_code ?? '') }}"></div>
            <div>
                <strong>Land</strong>
                <select name="country">
                    @foreach($countries as $country)
                        <option value="{{ $country->country_code }}" {{ old('country', $company->country ?? '') === $country->country_code ? 'selected' : '' }}>
                            {{ $country->country }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div><strong>E-mail</strong><input name="email" type="email" value="{{ old('email', $company->email ?? '') }}"></div>
        </div>

        <div class="inline-form" style="margin-top:18px;">
            <button class="primary-button" type="submit">Opslaan</button>
        </div>
    </form>
</article>

@if($company)
    {{--
        Deze sectie is alleen zichtbaar op de edit-pagina.
        Contactpersonen worden opgehaald uit relations via CompanyController@edit().
    --}}
    <article class="panel" style="margin-top:18px;">
        <div class="panel-header panel-header-stack">
            <div>
                <h2>Contactpersonen</h2>
                <p class="muted">
                    Dit overzicht komt uit de tabel <code>relations</code> voor dit bedrijf.
                    Je kunt hier direct zien welke contactpersonen al zijn gekoppeld.
                </p>
            </div>
            <a class="secondary-button compact-button" href="{{ route('companies.contacts.create', $company->id) }}">Contactpersoon toevoegen</a>
        </div>

        <div class="table-wrapper">
            <table class="data-table compact-table">
                <thead>
                    <tr>
                        <th>Naam</th>
                        <th>Functie</th>
                        <th>Titel</th>
                        <th>E-mail</th>
                        <th>Telefoon</th>
                        <th>Acties</th>
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
                            <td>
                                <div class="table-actions">
                                    <a class="secondary-button compact-button" href="{{ route('companies.contacts.edit', [$company->id, $contact->id]) }}">Bewerken</a>
                                    <form method="POST" action="{{ route('companies.contacts.destroy', [$company->id, $contact->id]) }}" onsubmit="return confirm('Contactpersoon verwijderen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger-button compact-button" type="submit">Verwijderen</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">Nog geen contactpersonen voor dit bedrijf.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endif
@endsection
