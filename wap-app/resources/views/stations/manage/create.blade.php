{{-- Aanmaken nieuw station --}}
@extends('layouts.iwa')

@section('title', 'Nieuw station aanmaken')
@section('eyebrow', 'Stationsbeheer')
@section('page-title', 'Nieuw station aanmaken')

@section('back-button')
    <a class="secondary-button" href="{{ route('stations.manage.index') }}">Terug naar beheer</a>
@endsection

@section('content')

<section class="panel" style="max-width: 680px;">
    <div class="panel-header">
        <div>
            <h2>Stationsgegevens</h2>
            <p class="muted">Velden met * zijn verplicht.</p>
        </div>
    </div>

    @if ($errors->any())
        <div style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:12px 16px;border-radius:6px;margin-bottom:16px;">
            <strong>Let op:</strong>
            <ul style="margin:6px 0 0 16px;padding:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('stations.manage.store') }}">
        @csrf

        <fieldset style="border:none;padding:0;margin:0 0 1.5rem 0;">
            <legend style="font-weight:700;font-size:14px;margin-bottom:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Basisgegevens</legend>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Station ID (STN) *</label>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="10" required
                        placeholder="bv. 62400"
                        style="width:100%;border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;">
                    <p style="font-size:11px;color:var(--text-muted);margin-top:3px;">Max. 10 tekens, wordt automatisch hoofdletters.</p>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Hoogte boven NAP (m) *</label>
                    <input type="number" step="0.01" name="elevation" value="{{ old('elevation') }}" required
                        placeholder="bv. 4.3"
                        style="width:100%;border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Breedtegraad (lat) *</label>
                    <input type="number" step="0.000001" name="latitude" value="{{ old('latitude') }}" required
                        placeholder="bv. 52.3183"
                        style="width:100%;border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Lengtegraad (lon) *</label>
                    <input type="number" step="0.000001" name="longitude" value="{{ old('longitude') }}" required
                        placeholder="bv. 4.7899"
                        style="width:100%;border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;">
                </div>
            </div>
        </fieldset>

        <fieldset style="border:none;padding:0;margin:0 0 1.5rem 0;">
            <legend style="font-weight:700;font-size:14px;margin-bottom:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Locatiegegevens</legend>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Land *</label>
                    <select name="country_code" required class="form-control">
                        <option value="">— Selecteer land —</option>
                        @foreach ($countries as $c)
                            <option value="{{ $c->country_code }}" {{ old('country_code') === $c->country_code ? 'selected' : '' }}>
                                {{ $c->country }} ({{ $c->country_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Plaatsnaam</label>
                    <input type="text" name="location" value="{{ old('location') }}" maxlength="100"
                        placeholder="bv. Amsterdam"
                        style="width:100%;border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Regio / Provincie</label>
                    <input type="text" name="region1" value="{{ old('region1') }}" maxlength="100"
                        placeholder="bv. Noord-Holland"
                        style="width:100%;border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Subregion</label>
                    <input type="text" name="region2" value="{{ old('region2') }}" maxlength="100"
                        placeholder="bv. Groot-Amsterdam"
                        style="width:100%;border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;box-sizing:border-box;">
                </div>
            </div>
        </fieldset>

        <div style="display:flex;gap:0.75rem;">
            <button type="submit" class="primary-button">Station aanmaken</button>
            <a href="{{ route('stations.manage.index') }}" class="secondary-button">Annuleren</a>
        </div>
    </form>
</section>

@endsection
