@extends('layouts.iwa')

@section('title', 'Abonnementaanbod')
@section('eyebrow', 'Abonnementenaanbod')
@section('page-title', 'Beschikbare abonnementtypes')
@section('page-subtitle', 'Overzicht van de abonnementsvormen die IWA aanbiedt.')

@section('back-button')
    <a class="secondary-button" href="{{ route('subscriptions.index') }}">Terug naar abonnementen</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>Abonnementtypes</h2>
            <p class="muted">Prijs per station, frequentie en hoeveel abonnementen dit type momenteel gebruiken.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Beschrijving</th>
                    <th>Prijs per station</th>
                    <th>Frequentie</th>
                    <th>Aantal klanten</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($types as $type)
                <tr>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->description }}</td>
                    <td>&euro; {{ number_format($type->price_per_station, 2, ',', '.') }}</td>
                    <td>
                        @if ($type->continuous)
                            Continu
                        @elseif ($type->frequency_in_hours)
                            Elke {{ $type->frequency_in_hours }} uur
                        @else
                            Elke {{ $type->frequency_in_days }} dag(en)
                        @endif
                    </td>
                    <td>{{ $type->subscriber_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</article>
@endsection
