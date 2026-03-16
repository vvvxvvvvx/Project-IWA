@extends('layouts.iwa')

@section('title', 'Contracten')
@section('eyebrow', 'Contracten via REST-API')
@section('page-title', 'Contractoverzicht')
@section('page-subtitle', 'Contractinformatie los getrokken van abonnementen, zodat contracten als eigen domein bekeken kunnen worden.')

@section('back-button')
    <a class="secondary-button" href="{{ route('subscriptions.index') }}">Terug naar abonnementen</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>Alle contracten</h2>
            <p class="muted">Contractgegevens relevant voor REST-API toegang: identifier, bronendpoint, geldigheid, prijs en recent API-gebruik.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Contract</th>
                    <th>Bedrijf</th>
                    <th>Type</th>
                    <th>Looptijd</th>
                    <th>Prijs</th>
                    <th>Stations</th>
                    <th>Succesvolle API-calls</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contracts as $contract)
                <tr>
                    <td><a href="{{ route('contracts.show', $contract->identifier) }}">{{ $contract->identifier }}</a></td>
                    <td>{{ $contract->company_name }}</td>
                    <td>{{ $contract->type_name }}</td>
                    <td>{{ $contract->start_date }} &ndash; {{ $contract->end_date ?? 'Doorlopend' }}</td>
                    <td>&euro; {{ number_format($contract->price, 2, ',', '.') }}</td>
                    <td>{{ $contract->station_count }}</td>
                    <td>{{ $contract->successful_calls ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</article>
@endsection
