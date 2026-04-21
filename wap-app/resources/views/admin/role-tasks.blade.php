{{-- Beheerpagina voor roltaken: CRUD op taken per rol. --}}
@extends('layouts.iwa')

@section('title', 'Roltaken')
@section('eyebrow', 'Rollenbeheer')
@section('page-title', 'Roltaken Overzicht')


@section('content')

@foreach ($roles as $role)
<article class="panel" style="margin-bottom: 1.5rem;">
    <div class="panel-header panel-header-stack">
        <div>
            <h2>{{ $role->role }}</h2>
            <p class="muted">Taken voor de rol "{{ $role->role }}".</p>
        </div>
        <button type="button" class="secondary-button compact-button"
            onclick="toggleInlineForm('toevoegen-form-{{ $role->id }}')">Taak toevoegen</button>
    </div>

    {{-- Inline toevoegen-formulier, verschijnt direct onder de knop --}}
    <div id="toevoegen-form-{{ $role->id }}" class="inline-form" style="display:none;">
        <h3>Nieuwe taak voor "{{ $role->role }}"</h3>
        <form method="POST" action="{{ route('role-tasks.store') }}">
            @csrf
            <input type="hidden" name="role_id" value="{{ $role->id }}">
            <div style="margin-bottom:0.75rem;">
                <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Taaknaam *</label>
                <input type="text" name="name" required maxlength="100"
                    style="width:100%; padding:0.5rem; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:0.75rem;">
                <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Beschrijving</label>
                <input type="text" name="description" maxlength="256"
                    style="width:100%; padding:0.5rem; box-sizing:border-box;">
            </div>
            <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                <button type="button" class="secondary-button"
                    onclick="toggleInlineForm('toevoegen-form-{{ $role->id }}')">Annuleren</button>
                <button type="submit" class="primary-button">Aanmaken</button>
            </div>
        </form>
    </div>

    @php
        $roleTasks = $tasks->where('role_id', $role->id);
    @endphp

    @if ($roleTasks->isEmpty())
        <p style="padding: 0.75rem 1rem; color: #6b7280;">Geen taken voor deze rol.</p>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Taaknaam</th>
                        <th>Beschrijving</th>
                        <th style="width:1%;white-space:nowrap;">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roleTasks as $task)
                    <tr>
                        <td>{{ $task->id }}</td>
                        <td>{{ $task->name }}</td>
                        <td>{{ $task->description ?? '—' }}</td>
                        <td style="white-space:nowrap;">
                            <div class="table-actions" style="flex-wrap:nowrap;">
                                <button type="button" class="secondary-button compact-button"
                                    onclick="toggleInlineForm('bewerken-form-{{ $task->id }}')">Bewerken</button>
                                <button type="button" class="danger-button compact-button"
                                    onclick="toggleInlineForm('verwijder-form-{{ $task->id }}')">Verwijderen</button>
                            </div>
                        </td>
                    </tr>

                    {{-- Inline bewerken-formulier, verschijnt direct onder deze rij --}}
                    <tr id="bewerken-form-{{ $task->id }}" class="inline-form-row" style="display:none;">
                        <td colspan="4">
                            <div class="inline-form">
                                <h3>Taak bewerken</h3>
                                <form method="POST" action="{{ route('role-tasks.update', ['id' => $task->id]) }}">
                                    @csrf
                                    @method('PUT')
                                    <div style="margin-bottom:0.75rem;">
                                        <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Taaknaam *</label>
                                        <input type="text" name="name" value="{{ $task->name }}" required maxlength="100"
                                            style="width:100%; padding:0.5rem; box-sizing:border-box;">
                                    </div>
                                    <div style="margin-bottom:0.75rem;">
                                        <label style="display:block; margin-bottom:0.25rem; font-size:0.875rem;">Beschrijving</label>
                                        <input type="text" name="description" value="{{ $task->description }}" maxlength="256"
                                            style="width:100%; padding:0.5rem; box-sizing:border-box;">
                                    </div>
                                    <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                                        <button type="button" class="secondary-button"
                                            onclick="toggleInlineForm('bewerken-form-{{ $task->id }}')">Annuleren</button>
                                        <button type="submit" class="primary-button">Opslaan</button>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Inline verwijder-formulier, verschijnt direct onder deze rij --}}
                    <tr id="verwijder-form-{{ $task->id }}" class="inline-form-row" style="display:none;">
                        <td colspan="4">
                            <div class="inline-form">
                                <h3>Taak verwijderen</h3>
                                <p>Voer uw wachtwoord in om "<strong>{{ $task->name }}</strong>" te verwijderen.</p>
                                <form method="POST" action="{{ route('role-tasks.destroy', ['id' => $task->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="password" name="password" placeholder="Wachtwoord" required
                                        style="width:100%; padding:0.5rem; margin:0.75rem 0; box-sizing:border-box;">
                                    <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                                        <button type="button" class="secondary-button compact-button"
                                            onclick="toggleInlineForm('verwijder-form-{{ $task->id }}')">Annuleren</button>
                                        <button type="submit" class="danger-button compact-button">Bevestigen</button>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>

                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>
@endforeach

<style>
.inline-form {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem 1.25rem;
    margin: 0.75rem 1rem;
}
.inline-form h3 {
    margin-top: 0;
}
.inline-form-row td {
    padding: 0 !important;
    border: none !important;
}
</style>

<script>
function toggleInlineForm(id) {
    var el = document.getElementById(id);
    if (el.style.display === 'none') {
        el.style.display = el.tagName === 'TR' ? 'table-row' : 'block';
    } else {
        el.style.display = 'none';
    }
}
</script>
@endsection
