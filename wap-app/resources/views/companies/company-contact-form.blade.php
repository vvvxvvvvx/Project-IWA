{{--
    Apart formulier voor contactpersonen.

    Belangrijke koppelingen:
    - Contactpersonen worden opgeslagen in de tabel relations
    - Routes lopen via companies.contacts.* in routes/web.php
    - CompanyController beheert opslaan, wijzigen en verwijderen
--}}
@extends('layouts.iwa')

@section('title', $contact ? 'Contactpersoon wijzigen' : 'Contactpersoon toevoegen')
@section('eyebrow', 'Klantenbeheer')
@section('page-title', $contact ? 'Contactpersoon wijzigen' : 'Contactpersoon toevoegen')
@section('page-subtitle', 'Contactgegevens voor ' . $company->name)

@section('back-button')
    <a class="secondary-button" href="{{ route('companies.edit', $company->id) }}">Terug naar bedrijf bewerken</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>{{ $contact ? 'Contactpersoon bewerken' : 'Nieuwe contactpersoon' }}</h2>
            <p class="muted">Dit formulier schrijft direct naar de tabel <code>relations</code>.</p>
        </div>
    </div>

    <form method="POST" action="{{ $contact ? route('companies.contacts.update', [$company->id, $contact->id]) : route('companies.contacts.store', $company->id) }}">
        @csrf
        @if($contact) @method('PUT') @endif

        <div class="details-grid">
            <div><strong>Achternaam</strong><input name="name" value="{{ old('name', $contact->name ?? '') }}"></div>
            <div><strong>Voornaam</strong><input name="first_name" value="{{ old('first_name', $contact->first_name ?? '') }}"></div>
            <div><strong>Initialen</strong><input name="initials" value="{{ old('initials', $contact->initials ?? '') }}"></div>
            <div><strong>Tussenvoegsel</strong><input name="prefix" value="{{ old('prefix', $contact->prefix ?? '') }}"></div>
            <div><strong>Functie</strong><input name="function" value="{{ old('function', $contact->function ?? '') }}"></div>
            <div><strong>Titel</strong><input name="title" value="{{ old('title', $contact->title ?? '') }}"></div>
            <div><strong>E-mail</strong><input name="email" type="email" value="{{ old('email', $contact->email ?? '') }}"></div>
            <div><strong>Telefoon</strong><input name="phone" value="{{ old('phone', $contact->phone ?? '') }}"></div>
        </div>

        <div class="inline-form" style="margin-top:18px;">
            <button class="primary-button" type="submit">Opslaan</button>
        </div>
    </form>
</article>
@endsection
