<nav class="page-nav">
    <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
    @hastask('view_stations')
        <a class="{{ request()->routeIs('stations.*') ? 'active' : '' }}" href="{{ route('stations.index') }}">Stations</a>
    @endhastask
    @hastask('view_subscriptions')
        <a class="{{ request()->routeIs('subscriptions.*') ? 'active' : '' }}" href="{{ route('subscriptions.index') }}">Abonnementen</a>
    @endhastask
    @hastask('view_subscription_types')
        <a class="{{ request()->routeIs('subscription-types.*') ? 'active' : '' }}" href="{{ route('subscription-types.index') }}">Aanbod</a>
    @endhastask
    @hastask('view_contracts')
        <a class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}" href="{{ route('contracts.index') }}">Contracten</a>
    @endhastask
    @hastask('view_companies')
        <a class="{{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">Bedrijven</a>
    @endhastask
    @hastask('manage_users')
        <a class="{{ request()->routeIs('super-users.*') ? 'active' : '' }}" href="{{ route('super-users.index') }}">Gebruikers</a>
    @endhastask
    @hastask('manage_roles')
        <a class="{{ request()->routeIs('role-tasks.*') ? 'active' : '' }}" href="{{ route('role-tasks.index') }}">Roltaken</a>
    @endhastask
    <a class="{{ request()->routeIs('endpoints.*') ? 'active' : '' }}" href="{{ route('endpoints.index') }}">API Beheer</a>
</nav>
