<nav class="page-nav">
    <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
    <a class="{{ request()->routeIs('stations.*') ? 'active' : '' }}" href="{{ route('stations.index') }}">Stations</a>
    <a class="{{ request()->routeIs('subscriptions.*') ? 'active' : '' }}" href="{{ route('subscriptions.index') }}">Abonnementen</a>
    <a class="{{ request()->routeIs('subscription-types.*') ? 'active' : '' }}" href="{{ route('subscription-types.index') }}">Aanbod</a>
    <a class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}" href="{{ route('contracts.index') }}">Contracten</a>
    <a class="{{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">Bedrijven</a>
</nav>
