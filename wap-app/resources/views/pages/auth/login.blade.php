<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IWA Dashboard &ndash; Inloggen</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="login-body">

<main class="login-card brand-login-card">

    <div class="brand-block login-brand">
        <img class="iwa-logo" src="/assets/iwa-logo.png" alt="IWA logo">
        <div>
            <p class="eyebrow">Internationale Weer Agentschap</p>
            <h1>IWA Dashboard</h1>
            <p class="muted">Log in om weerdata, stations, abonnementen en contracten te bekijken.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('status'))
        <div class="alert error" style="background:rgba(22,163,74,.08);color:#166534;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="login-form">
        @csrf

        <label>
            <span>Medewerkerscode</span>
            <input
                type="text"
                name="employee_code"
                value="{{ old('employee_code') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="A0001"
            >
        </label>

        <label>
            <span>Wachtwoord</span>
            <input
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Wachtwoord"
            >
        </label>

        <button type="submit" class="primary-button" style="width:100%;font-size:15px;">
            Inloggen
        </button>
    </form>

</main>

</body>
</html>
