@extends('layouts.iwa')

@section('title', $station->stn)
@section('eyebrow', 'Station detail')
@section('page-title', $station->stn)
@section('page-subtitle', ($station->location_label ?? 'Onbekend') . ' · STN ' . $station->stn)

@push('head-scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('back-button')
    <a class="secondary-button" href="{{ route('stations.index') }}">Terug naar stations</a>
@endsection

@section('content')

{{-- Temperatuurtrend --}}
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Temperatuurtrend</h2>
            <p class="muted">Laatste metingen van dit station, zodat trends en afwijkingen over tijd zichtbaar worden.</p>
        </div>
    </div>
    <canvas id="stationTemperatureChart" height="120"></canvas>
</section>

{{-- Readings tabel --}}
<section class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div>
            <h2>Laatste readings</h2>
            <p class="muted">Elke regel is een opgeslagen meting na validatie en eventuele correctie. Download een periode als CSV voor verdere analyse.</p>
        </div>
    </div>

    <form class="inline-form" method="GET" action="{{ route('stations.download', $station->stn) }}" style="margin-bottom:18px;">
        <input type="date" name="from" style="border:1px solid var(--border);border-radius:14px;padding:10px 14px;font-size:14px;">
        <input type="date" name="to"   style="border:1px solid var(--border);border-radius:14px;padding:10px 14px;font-size:14px;">
        <button class="primary-button" type="submit">Download periode als CSV</button>
    </form>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Moment</th>
                    <th>Temp</th>
                    <th>Dauwpunt</th>
                    <th>Luchtdruk (st.)</th>
                    <th>Luchtdruk (z.n.)</th>
                    <th>Zicht</th>
                    <th>Wind</th>
                    <th>Neerslag</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($readings as $reading)
                <tr>
                    <td>{{ $reading->measured_at }} UTC</td>
                    <td>{{ $reading->temp ?? '-' }}</td>
                    <td>{{ $reading->dewp ?? '-' }}</td>
                    <td>{{ $reading->stp ?? '-' }}</td>
                    <td>{{ $reading->slp ?? '-' }}</td>
                    <td>{{ $reading->visib ?? '-' }}</td>
                    <td>{{ $reading->wdsp ?? '-' }}</td>
                    <td>{{ $reading->prcp ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@endsection

@push('scripts')
@php
    $labels = array_reverse(array_map(fn($r) => substr((string)$r->measured_at, 11, 5), $readings));
    $values = array_reverse(array_map(fn($r) => $r->temp !== null ? (float)$r->temp : null, $readings));
@endphp
<script>
const stationDetailLabels = {!! json_encode($labels) !!};
const stationDetailValues = {!! json_encode($values) !!};
</script>
<script src="/assets/station.js"></script>
@endpush
