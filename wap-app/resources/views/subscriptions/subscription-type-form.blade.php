{{--
    Formulier voor abonnementtypes.

    Als je velden toevoegt aan subscription_types, pas dan ook
    SubscriptionController@validateType aan.
--}}
@extends('layouts.iwa')

@section('title', $type ? 'Abonnementtype wijzigen' : 'Abonnementtype toevoegen')
@section('eyebrow', 'Abonnementenaanbod')
@section('page-title', $type ? 'Abonnementtype wijzigen' : 'Abonnementtype toevoegen')
@section('page-subtitle', 'Beheer van naam, frequentie en prijs per station.')

@section('back-button')
    <a class="secondary-button" href="{{ route('subscription-types.index') }}">Terug naar types</a>
@endsection

@section('content')
<article class="panel">
    <form method="POST" action="{{ $type ? route('subscription-types.update', $type->id) : route('subscription-types.store') }}">
        @csrf
        @if($type) @method('PUT') @endif
        <div class="details-grid">
            <div><strong>Naam</strong><input name="name" value="{{ old('name', $type->name ?? '') }}"></div>
            <div><strong>Beschrijving</strong><input name="description" value="{{ old('description', $type->description ?? '') }}"></div>
            <div><strong>Aantal stations</strong><input name="nr_stations" type="number" value="{{ old('nr_stations', $type->nr_stations ?? '') }}"></div>
            <div><strong>Frequentie in uren</strong><input name="frequency_in_hours" type="number" value="{{ old('frequency_in_hours', $type->frequency_in_hours ?? '') }}"></div>
            <div><strong>Frequentie in dagen</strong><input name="frequency_in_days" type="number" value="{{ old('frequency_in_days', $type->frequency_in_days ?? '') }}"></div>
            <div><strong>Prijs per station</strong><input name="price_per_station" type="number" step="0.01" value="{{ old('price_per_station', $type->price_per_station ?? '') }}"></div>
            <div><strong>Geldig tot</strong><input name="valid_through" type="date" value="{{ old('valid_through', $type->valid_through ?? '') }}"></div>
            <div><strong>Continu</strong><input name="continuous" type="checkbox" value="1" {{ old('continuous', $type->continuous ?? 0) ? 'checked' : '' }}></div>
        </div>
        <div class="inline-form" style="margin-top:18px;"><button class="primary-button" type="submit">Opslaan</button></div>
    </form>
</article>
@endsection
