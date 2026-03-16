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
            <p class="header-subtitle">@yield('page-subtitle')</p>
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
    @yield('content')
</main>

@stack('scripts')

</body>
</html>
