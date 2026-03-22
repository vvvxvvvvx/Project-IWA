@extends('layouts.iwa')

@section('title', 'Superusers')
@section('eyebrow', 'Gebruikersbeheer')
@section('page-title', 'Superusers Overzicht')
@section('page-subtitle', 'Overzicht van alle users in het systeem.')

@section('back-button')
    <a class="secondary-button" href="{{ route('dashboard') }}">Terug naar dashboard</a>
@endsection

@section('content')
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>Alle users</h2>
            <p class="muted">Overzicht van alle users met hun rollen.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Voornaam</th>
                    <th>Achternaam</th>
                    <th>Rol</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->first_name ?? '-' }}</td>
                    <td>{{ $user->name ?? '-' }}</td>
                    <td>{{ $user->role ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</article>
@endsection
