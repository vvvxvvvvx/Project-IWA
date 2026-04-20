{{-- Overzichtspagina voor alle bedrijven. Als je route('companies.index') wijzigt, pas dan ook CompanyController@index aan. --}}
{{--
    Overzicht van alle bedrijven.
--}}
@extends('layouts.iwa')

@section('title', 'Bedrijven')
@section('eyebrow', 'Klantenbeheer')
@section('page-title', 'Bedrijvenoverzicht')
@section('page-subtitle', 'Overzicht van alle bedrijven zodat medewerkers snel een relatie kunnen opzoeken.')

@section('back-button')
    <a class="secondary-button" href="{{ route('dashboard') }}">Terug naar dashboard</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Alle bedrijven</h2>
            <p class="muted">Klik op een bedrijf voor contactpersonen, gekoppelde abonnementen en contractgerelateerde informatie.</p>
        </div>
        <a class="primary-button" href="{{ route('companies.create') }}">Bedrijf toevoegen</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Stad</th>
                    <th>Land</th>
                    <th>E-mail</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($companies as $company)
                <tr>
                    <td><a href="{{ route('companies.show', $company->id) }}">{{ $company->name }}</a></td>
                    <td>{{ $company->city ?? '-' }}</td>
                    <td>{{ $company->country_name ?? '-' }}</td>
                    <td>{{ $company->email ?? '-' }}</td>
                    <td>
                        <div class="inline-form">
                            <a class="secondary-button" href="{{ route('companies.edit', $company->id) }}">Wijzigen</a>
                            <form method="POST" action="{{ route('companies.destroy', $company->id) }}" onsubmit="return confirm('Bedrijf verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button class="secondary-button" type="submit">Verwijderen</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $companies->links() }}
</article>
@endsection
