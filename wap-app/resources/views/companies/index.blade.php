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
    <div class="panel-header">
        <div>
            <h2>Alle bedrijven</h2>
            <p class="muted">Klik op een bedrijf voor contactpersonen, gekoppelde abonnementen en contractgerelateerde informatie.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Stad</th>
                    <th>Land</th>
                    <th>E-mail</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($companies as $company)
                <tr>
                    <td><a href="{{ route('companies.show', $company->id) }}">{{ $company->name }}</a></td>
                    <td>{{ $company->city ?? '-' }}</td>
                    <td>{{ $company->country_name ?? '-' }}</td>
                    <td>{{ $company->email ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</article>
@endsection
