{{-- Beheeroverzicht van alle stations --}}
@extends('layouts.iwa')

@section('title', 'Stations beheren')
@section('eyebrow', 'Stationsbeheer')
@section('page-title', 'Stations beheren')
@section('page-subtitle', 'Aanmaken, bekijken en wijzigen van weerstations.')

@section('back-button')
    <a class="secondary-button" href="{{ route('stations.index') }}">Terug naar overzicht</a>
@endsection

@section('content')

@if (session('success'))
    <div class="alert alert-success" style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;padding:12px 16px;border-radius:6px;margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif

<section class="panel">
    <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
            <h2>Alle weerstations</h2>
            <p class="muted">{{ $stations->count() }} station(s) gevonden.</p>
        </div>
        <a href="{{ route('stations.manage.create') }}" class="primary-button">+ Nieuw station</a>
    </div>

    {{-- Zoekfilter --}}
    <form method="GET" action="{{ route('stations.manage.index') }}" style="margin-bottom:1rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Zoeken</label>
            <input type="text" name="q" value="{{ $query }}" placeholder="STN of locatienaam"
                style="border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;min-width:200px;">
        </div>
        <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Land</label>
            <select name="country" class="form-control" style="min-width:160px;">
                <option value="">Alle landen</option>
                @foreach ($countries as $c)
                    <option value="{{ $c->country_code }}" {{ $country === $c->country_code ? 'selected' : '' }}>
                        {{ $c->country }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:0.5rem;">
            <button type="submit" class="primary-button">Zoeken</button>
            <a href="{{ route('stations.manage.index') }}" class="secondary-button">Reset</a>
        </div>
    </form>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STN</th>
                    <th>Locatie</th>
                    <th>Land</th>
                    <th>Breedtegraad</th>
                    <th>Lengtegraad</th>
                    <th>Hoogte (m)</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stations as $s)
                <tr>
                    <td><strong>{{ $s->stn }}</strong></td>
                    <td>{{ $s->location_label ?? '—' }}</td>
                    <td>{{ $s->country_name ?? '—' }}</td>
                    <td>{{ $s->latitude }}</td>
                    <td>{{ $s->longitude }}</td>
                    <td>{{ $s->elevation }}</td>
                    <td style="white-space:nowrap;">
                        <a href="{{ route('stations.manage.show', $s->stn) }}" class="secondary-button" style="padding:4px 10px;font-size:12px;">Bekijken</a>
                        <a href="{{ route('stations.manage.edit', $s->stn) }}" class="primary-button" style="padding:4px 10px;font-size:12px;margin-left:4px;">Wijzigen</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align:center;">Geen stations gevonden.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
