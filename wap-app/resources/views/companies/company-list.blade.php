{{-- Overzichtspagina voor alle bedrijven. Als je route('companies.index') wijzigt, pas dan ook CompanyController@index aan. --}}
{{--
    Overzicht van alle bedrijven.
--}}
@extends('layouts.iwa')

@section('title', 'Bedrijven')
@section('eyebrow', 'Klantenbeheer')
@section('page-title', 'Bedrijvenoverzicht')


@section('content')
<article class="panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Alle bedrijven</h2>
            <p class="muted">Klik op een bedrijf voor contactpersonen, gekoppelde abonnementen en contractgerelateerde informatie.</p>
        </div>
        <a class="secondary-button compact-button" href="{{ route('companies.create') }}">Bedrijf toevoegen</a>
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
                @forelse ($companies as $company)
                <tr>
                    <td><a href="{{ route('companies.show', $company->id) }}">{{ $company->name }}</a></td>
                    <td>{{ $company->city ?? '-' }}</td>
                    <td>{{ $company->country_name ?? '-' }}</td>
                    <td>{{ $company->email ?? '-' }}</td>
                    <td>
                        <div class="table-actions">
                            <a class="secondary-button compact-button" href="{{ route('companies.edit', $company->id) }}">Bewerken</a>
                            <form method="POST" action="{{ route('companies.destroy', $company->id) }}" onsubmit="return confirm('Bedrijf verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button class="danger-button compact-button" type="submit">Verwijderen</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <span class="muted">Nog geen bedrijven gevonden.</span>
                            <a class="secondary-button compact-button" href="{{ route('companies.create') }}">Voeg je eerste bedrijf toe</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $companies->links() }}
</article>
@endsection
