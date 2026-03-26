{{--
    Eenvoudige fallback-welkomstpagina.
    Deze vervangt de standaard Laravel demo-pagina met tutorials en voorbeeldlinks.

    Als je de startpagina wilt wijzigen, pas dan ook routes/web.php aan.
--}}
@extends('layouts.iwa')

@section('title', 'Welkom')
@section('eyebrow', 'IWA platform')
@section('page-title', 'Internationale Weer Agentschap')
@section('page-subtitle', 'Gebruik het dashboard om stations, contracten, abonnementen en bedrijven te beheren.')

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>Welkom</h2>
            <p class="muted">
                Deze pagina is alleen een nette fallback. In de normale flow wordt de root-route
                doorgestuurd naar de login- of dashboardpagina.
            </p>
        </div>
    </div>

    <div class="inline-form">
        @auth
            <a class="primary-button" href="{{ route('dashboard') }}">Naar dashboard</a>
        @else
            <a class="primary-button" href="{{ route('login') }}">Inloggen</a>
        @endauth
    </div>
</article>
@endsection
