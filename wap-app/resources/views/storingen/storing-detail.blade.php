{{-- Detailpagina voor één storing: aantekeningen en statusbeheer. --}}
@extends('layouts.iwa')

@section('title', 'Storing #' . $fault->id)
@section('eyebrow', 'Analyse & monitoring')
@section('page-title', $fault->typeLabel() . ' — Storing #' . $fault->id)
@section('page-subtitle', ($station->location_label ?? 'Onbekend') . ' · ' . ($station->country_name ?? '') . ' · STN ' . $fault->station)

@section('back-button')
    <a class="secondary-button" href="{{ route('stations.show', $fault->station) }}">Terug naar station</a>
@endsection

@section('content')

{{-- Status balk bovenaan --}}
<article class="panel" style="margin-bottom:18px;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div style="display:flex; align-items:center; gap:1rem;">
            @if($fault->status === 'open')
                <span style="width:14px;height:14px;border-radius:50%;background:#f59e0b;display:inline-block;flex-shrink:0;"></span>
                <span style="font-weight:600; font-size:1rem;">Open storing</span>
            @elseif($fault->status === 'in_behandeling')
                <span style="width:14px;height:14px;border-radius:50%;background:#3b82f6;display:inline-block;flex-shrink:0;"></span>
                <span style="font-weight:600; font-size:1rem;">In behandeling</span>
            @else
                <span style="width:14px;height:14px;border-radius:50%;background:#22c55e;display:inline-block;flex-shrink:0;"></span>
                <span style="font-weight:600; font-size:1rem;">Opgelost</span>
            @endif
            <span class="muted">Gemeld op {{ $fault->created_at->format('d-m-Y \o\m H:i') }}</span>
        </div>

        {{-- Snelle statuswissel --}}
        <form method="POST" action="{{ route('storingen.status', $fault->id) }}" style="display:flex; gap:0.5rem; align-items:center;">
            @csrf
            <select name="status" class="form-control" style="max-width:180px;">
                <option value="open"           {{ $fault->status === 'open'           ? 'selected' : '' }}>Open</option>
                <option value="in_behandeling" {{ $fault->status === 'in_behandeling' ? 'selected' : '' }}>In behandeling</option>
                <option value="opgelost"       {{ $fault->status === 'opgelost'       ? 'selected' : '' }}>Opgelost</option>
            </select>
            <button type="submit" class="primary-button">Status opslaan</button>
        </form>
    </div>

    @if($fault->description)
        <p style="margin-top:0.75rem; color:#374151;">{{ $fault->description }}</p>
    @endif
</article>

{{-- Details van de storing (aard & ernst) --}}
<article class="panel" style="margin-bottom:18px;">
    <div class="panel-header">
        <h2>Details van de storing</h2>
    </div>

    @if($fault->type === 'offline')
        @if($context['last_seen'])
            <div style="display:flex; gap:2rem; flex-wrap:wrap;">
                <div>
                    <p class="muted" style="margin:0; font-size:0.85rem;">Laatste meting ontvangen</p>
                    <p style="margin:0.2rem 0 0; font-weight:600; font-size:1.1rem;">{{ $context['last_seen'] }} UTC</p>
                </div>
                <div>
                    <p class="muted" style="margin:0; font-size:0.85rem;">Offline sinds</p>
                    <p style="margin:0.2rem 0 0; font-weight:600; font-size:1.1rem; color:#ef4444;">
                        {{ $context['days_offline'] === 0 ? 'Vandaag' : $context['days_offline'] . ' dag(en)' }}
                    </p>
                </div>
            </div>
        @else
            <p class="muted">Geen meetdata beschikbaar voor dit station.</p>
        @endif

    @elseif($fault->type === 'ontbrekende_data')
        <div style="margin-bottom:0.75rem;">
            <span style="font-weight:600;">{{ $context['total_missing'] }} metingen</span> met ontbrekende velden gevonden.
        </div>
        @if($context['missing_fields']->isNotEmpty())
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Ontbrekend veld</th><th>Aantal metingen</th></tr>
                </thead>
                <tbody>
                    @foreach($context['missing_fields'] as $row)
                    <tr>
                        <td>{{ $row->missing_field }}</td>
                        <td>{{ $row->aantal }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    @elseif($fault->type === 'temperatuurcorrectie')
        <div style="margin-bottom:0.75rem;">
            In totaal <span style="font-weight:600;">{{ $context['total_corrections'] }} temperatuurcorrecties</span> geregistreerd voor dit station.
            Onderstaande tabel toont de 20 meest recente correcties.
        </div>
        @if($context['corrections']->isNotEmpty())
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Tijdstip</th><th>Originele waarde (°C)</th><th>Gecorrigeerde waarde (°C)</th><th>Afwijking</th></tr>
                </thead>
                <tbody>
                    @foreach($context['corrections'] as $row)
                    @php $diff = round(abs($row->origineel - $row->gecorrigeerd), 2); @endphp
                    <tr>
                        <td>{{ $row->measured_at }}</td>
                        <td style="color:#ef4444;">{{ round($row->origineel, 2) }}</td>
                        <td>{{ round($row->gecorrigeerd, 2) }}</td>
                        <td>{{ $diff }} °C</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @else
        <p class="muted">Geen aanvullende gegevens beschikbaar voor dit type storing.</p>
    @endif
</article>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">

    {{-- Aantekeningen tijdlijn --}}
    <article class="panel">
        <div class="panel-header">
            <h2>Aantekeningen ({{ $fault->notes->count() }})</h2>
        </div>

        @if($fault->notes->isEmpty())
            <p class="muted">Nog geen aantekeningen toegevoegd.</p>
        @else
            <div style="display:flex; flex-direction:column; gap:0.6rem;">
                @foreach($fault->notes as $note)
                <div style="background:#f8fafc; border-left:3px solid #3b82f6; padding:0.7rem 1rem; border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 0.3rem; color:#1f2937; white-space:pre-wrap;">{{ $note->message }}</p>
                    <span class="muted" style="font-size:0.8rem;">{{ $note->created_at->format('d-m-Y H:i') }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </article>

    {{-- Bericht toevoegen --}}
    <article class="panel">
        <div class="panel-header">
            <h2>Bericht toevoegen</h2>
        </div>
        <form method="POST" action="{{ route('storingen.notes.store', $fault->id) }}" style="display:flex; flex-direction:column; gap:0.75rem;">
            @csrf
            <label>
                <span style="font-weight:600; display:block; margin-bottom:0.35rem;">Bericht</span>
                <textarea name="message" class="form-control" rows="6"
                    placeholder="Beschrijf bijv. het contact met de stationsbeheerder of de voortgang van de afhandeling..."
                    required></textarea>
            </label>
            <div>
                <button type="submit" class="primary-button">Bericht opslaan</button>
            </div>
        </form>

        <hr style="margin:1.5rem 0; border:none; border-top:1px solid #e5e7eb;">

        <form method="POST" action="{{ route('storingen.destroy', $fault->id) }}"
              onsubmit="return confirm('Weet je zeker dat je deze storing wilt verwijderen?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="secondary-button" style="color:#ef4444; border-color:#fca5a5;">Storing verwijderen</button>
        </form>
    </article>
</div>

@endsection
