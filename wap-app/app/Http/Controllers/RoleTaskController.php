<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RoleTaskController extends Controller
{
    /**
     * Toon alle rollen met hun taken.
     */
    public function index(): View
    {
        $roles = DB::table('userroles')->get();

        $tasks = DB::table('role_tasks')
            ->join('userroles', 'role_tasks.role_id', '=', 'userroles.id')
            ->select('role_tasks.*', 'userroles.role as role_name')
            ->orderBy('role_tasks.role_id')
            ->get();

        return view('admin.role-tasks', compact('roles', 'tasks'));
    }

    /**
     * Sla een nieuwe taak op bij een rol.
     */
    public function store(Request $request)
    {

        $request->validate([
            'role_id'     => 'required|exists:userroles,id',
            'name'        => 'required|max:100',
            'description' => 'nullable|max:256',
        ]);

        DB::table('role_tasks')->insert([
            'role_id'     => $request->input('role_id'),
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('role-tasks.index')
            ->with('success', 'Taak succesvol aangemaakt.');
    }

    /**
     * Wijzig een bestaande taak.
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'name'        => 'required|max:100',
            'description' => 'nullable|max:256',
        ]);

        DB::table('role_tasks')->where('id', $id)->update([
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('role-tasks.index')
            ->with('success', 'Taak succesvol bijgewerkt.');
    }

    /**
     * Verwijder een taak na wachtwoordbevestiging.
     */
    public function destroy(Request $request, $id)
    {

        if (!Hash::check($request->input('password'), Auth::user()->password)) {
            return redirect()->route('role-tasks.index')
                ->with('error', 'Onjuist wachtwoord. Taak is niet verwijderd.');
        }

        DB::table('role_tasks')->where('id', $id)->delete();

        return redirect()->route('role-tasks.index')
            ->with('success', 'Taak succesvol verwijderd.');
    }
}
