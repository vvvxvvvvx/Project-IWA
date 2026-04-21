{{--
    Basislayout voor de IWA-beheerschermen.

    Deze layout toont:
    - algemene header
    - navigatie
    - success/error/validation meldingen

    Als je andere views toevoegt die dezelfde huisstijl moeten gebruiken,
    laat ze dan van deze layout extenden.
--}}
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - IWA Dashboard</title>
    <link rel="stylesheet" href="/assets/styles.css">
    @stack('head-scripts')
</head>
<body>
<header class="app-header">
    <div class="brand-block">
        <img class="iwa-logo" src="/assets/iwa-logo.png" alt="IWA logo">
        <div>
            <p class="eyebrow">@yield('eyebrow', 'Internationale Weer Agentschap')</p>
            <h1>@yield('page-title')</h1>
            @hasSection('page-subtitle')<p class="header-subtitle">@yield('page-subtitle')</p>@endif
        </div>
    </div>
    <div class="header-actions">
        @yield('back-button')
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="secondary-button" type="submit">Uitloggen</button>
        </form>
    </div>
</header>

@include('partials.nav')

<main class="dashboard-shell">
    @if (session('success'))
        <div class="panel" style="margin-bottom: 18px; border-left: 4px solid #22c55e;">
            <strong>Succes</strong>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="panel" style="margin-bottom: 18px; border-left: 4px solid #ef4444;">
            <strong>Fout</strong>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="panel" style="margin-bottom: 18px; border-left: 4px solid #f59e0b;">
            <strong>Controleer de invoer</strong>
            <ul style="margin: 8px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

@stack('scripts')
</body>
</html>
