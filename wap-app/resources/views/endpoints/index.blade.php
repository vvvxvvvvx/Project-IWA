@extends('layouts.iwa')

@section('title', 'REST-API Monitoring')
@section('eyebrow', 'Technisch beheer API')
@section('page-title', 'API Overzicht')
@section('page-subtitle', 'Volledig inzicht in het verbruik en de toegang tot de REST-API (E-11).')

@section('back-button')
    <a class="secondary-button" href="{{ route('dashboard') }}">Naar Dashboard</a>
@endsection

@section('content')

{{-- Algemene API Statistieken --}}
<section class="panel" style="margin-bottom:18px;">
    <div class="panel-header"><h2>Algemene Statistieken</h2></div>
    <div class="details-grid">
        <div><strong>Totaal API-verzoeken</strong><div>{{ number_format($totalCalls, 0, ',', '.') }}</div></div>
        <div><strong>Ongeautoriseerde verzoeken</strong><div style="color: {{ $unauthorizedCalls > 0 ? '#ef4444' : 'inherit' }}">{{ $unauthorizedCalls }}</div></div>
        <div><strong>Gedownloade bestanden</strong><div>{{ number_format($totalFilesDownloaded, 0, ',', '.') }}</div></div>
        <div><strong>Data overdrachts (KB)</strong><div>{{ number_format($totalDataTransferred, 0, ',', '.') }} KB</div></div>
    </div>
</section>

{{-- Verbruik per endpoint --}}
<section class="panel" style="margin-bottom:18px;">
    <div class="panel-header">
        <div>
            <h2>Verbruik per Endpoint</h2>
            <p class="muted">Overzicht van welke delen van de API het meest intensief worden aangesproken.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Endpoint (Route)</th>
                    <th>Totaal aanvragen</th>
                    <th>Succesvol geautoriseerd</th>
                    <th>Dataverkeer (KB)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usagePerEndpoint as $endpoint)
                <tr>
                    <td><code>{{ $endpoint->endpoint_used }}</code></td>
                    <td>{{ $endpoint->total }}</td>
                    <td>{{ $endpoint->successful }}</td>
                    <td>{{ $endpoint->data ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="muted">Nog geen endpoints aangeroepen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Recente Activiteit --}}
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Recente 100 API Aanvragen (Logs)</h2>
            <p class="muted">Live inzage in het verloop van inkomend verkeer. Rode regels vereisen mogelijk aandacht.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table compact-table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Tijd</th>
                    <th>Contract / Identifier</th>
                    <th>Endpoint</th>
                    <th>Authorized</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentActivity as $log)
                <tr style="{{ $log->authorized == 0 ? 'background-color:#fee2e2;' : '' }}">
                    <td>{{ $log->activity_date }}</td>
                    <td>{{ $log->activity_time }}</td>
                    <td>{{ $log->identifier ?? 'Onbekend' }}</td>
                    <td><span title="{{ $log->endpoint_used }}">{{ \Illuminate\Support\Str::limit($log->endpoint_used, 40) }}</span></td>
                    <td>{{ $log->authorized ? 'Ja' : 'Nee' }}</td>
                    <td>{{ $log->data_transferred ?? 0 }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="muted">Geen recente API-logs gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection