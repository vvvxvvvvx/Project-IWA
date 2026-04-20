{{-- Beheerpagina voor super users / gebruikersoverzicht. --}}
@extends('layouts.iwa')

@section('title', 'Superusers')
@section('eyebrow', 'Gebruikersbeheer')
@section('page-title', 'Superusers Overzicht')
@section('page-subtitle', 'Overzicht van alle users in het systeem.')


@section('content')
<article class="panel">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>Alle gebruikers</h2>
            <p class="muted">Overzicht van alle gebruikers met hun rollen.</p>
        </div>
        <button type="button" class="secondary-button compact-button" onclick="openModal('toevoegen-modal')">Gebruiker toevoegen</button>
    </div>

    @if(session('error'))
        <div class="alert alert-danger" style="color:red; padding: 0.5rem 1rem;">
            {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success" style="color:green; padding: 0.5rem 1rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Voornaam</th>
                    <th>Achternaam</th>
                    <th>E-mail</th>
                    <th>Rol</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->first_name }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role }}</td>
                    <td>
                        <div class="table-actions">
                            <button type="button" class="secondary-button compact-button"
                                onclick="openModal('bewerken-modal-{{ $user->id }}')">
                                Bewerken
                            </button>
                            <button type="button" class="danger-button compact-button"
                                onclick="openModal('verwijder-modal-{{ $user->id }}')">
                                Verwijderen
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <span class="muted">Nog geen gebruikers gevonden.</span>
                            <button type="button" class="secondary-button compact-button" onclick="openModal('toevoegen-modal')">Voeg eerste gebruiker toe</button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>

{{-- Toevoegen modal --}}
<div id="toevoegen-modal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:520px;">
        <h3>Nieuwe user aanmaken</h3>
        <form method="POST" action="{{ route('super-users.toevoegen') }}">
            @csrf
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
                <div>
                    <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Voornaam *</label>
                    <input type="text" name="first_name" required
                        style="width:100%; padding:0.5rem; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Achternaam *</label>
                    <input type="text" name="name" required
                        style="width:100%; padding:0.5rem; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Tussenvoegsel</label>
                    <input type="text" name="prefix"
                        style="width:100%; padding:0.5rem; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Medewerkerscode *</label>
                    <input type="text" name="employee_code" required
                        style="width:100%; padding:0.5rem; box-sizing:border-box;">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">E-mail *</label>
                    <input type="email" name="email" required
                        style="width:100%; padding:0.5rem; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Wachtwoord *</label>
                    <input type="password" name="password" required
                        style="width:100%; padding:0.5rem; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Rol *</label>
                    <select name="user_role" required
                        style="width:100%; padding:0.5rem; box-sizing:border-box;">
                        <option value="">-- Kies een rol --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->role }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                <button type="button" class="secondary-button" onclick="closeModal('toevoegen-modal')">Annuleren</button>
                <button type="submit" class="primary-button">Aanmaken</button>
            </div>
        </form>
    </div>
</div>

{{-- Modals per user --}}
@foreach ($users as $user)

{{-- Verwijder modal --}}
<div id="verwijder-modal-{{ $user->id }}" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Gebruiker verwijderen</h3>
        <p>Voer uw wachtwoord in om <strong>{{ $user->first_name }} {{ $user->name }}</strong> te verwijderen.</p>
        <form method="POST" action="{{ route('super-users.verwijder', ['id' => $user->id]) }}">
            @csrf
            <input type="password" name="password" placeholder="Wachtwoord" required
                style="width:100%; padding:0.5rem; margin: 0.75rem 0; box-sizing:border-box;">
            <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                <button type="button" class="secondary-button"
                    onclick="closeModal('verwijder-modal-{{ $user->id }}')">Annuleren</button>
                <button type="submit" class="primary-button">Bevestigen</button>
            </div>
        </form>
    </div>
</div>

{{-- Bewerken modal --}}
<div id="bewerken-modal-{{ $user->id }}" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Gebruiker bewerken</h3>
        <p>Bewerk <strong>{{ $user->first_name }} {{ $user->name }}</strong> en bevestig met uw wachtwoord.</p>
        <div style="margin-bottom:0.75rem;">
            <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Rol</label>
            <select id="bewerken-role-{{ $user->id }}"
                style="width:100%; padding:0.5rem; box-sizing:border-box;">
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ $user->role === $role->role ? 'selected' : '' }}>{{ $role->role }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:0.75rem;">
            <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Wachtwoord ter bevestiging</label>
            <input type="password" id="bewerken-password-{{ $user->id }}" placeholder="Wachtwoord" required
                style="width:100%; padding:0.5rem; box-sizing:border-box;">
        </div>
        <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
            <button type="button" class="secondary-button"
                onclick="closeModal('bewerken-modal-{{ $user->id }}')">Annuleren</button>
            <button type="button" class="primary-button"
                onclick="bevestigBewerken({{ $user->id }})">Bevestigen</button>
        </div>
    </div>
</div>

@endforeach

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.modal-box {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    min-width: 320px;
    max-width: 440px;
    width: 100%;
    box-shadow: 0 4px 24px rgba(0,0,0,0.3);
    position: relative;
    z-index: 10000;
}
.modal-box h3 {
    margin-top: 0;
}
</style>

<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
function bevestigBewerken(userId) {
    const password = document.getElementById('bewerken-password-' + userId).value;
    const role = document.getElementById('bewerken-role-' + userId).value;
    if (!password) {
        alert('Voer uw wachtwoord in.');
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/super-users/' + userId + '/bewerken';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    const pw = document.createElement('input');
    pw.type = 'hidden';
    pw.name = 'password';
    pw.value = password;
    const roleInput = document.createElement('input');
    roleInput.type = 'hidden';
    roleInput.name = 'user_role';
    roleInput.value = role;
    form.appendChild(csrf);
    form.appendChild(pw);
    form.appendChild(roleInput);
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
