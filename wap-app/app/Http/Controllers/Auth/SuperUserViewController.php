<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SuperUserViewController extends Controller
{
    /**
     * Toon het super-user overzicht.
     *
     * Er bestaat geen aparte auth.superuser-view meer; gebruik daarom dezelfde
     * expliciet benoemde beheerpagina als index().
     */
    public function create(): View
    {
        return view('admin.super-user-list', $this->allUsers());
    }


    public function allusers(): array
    // Haal alle gebruikers op voor het super-user overzicht.
    {
        $users = DB::table('users')->select('users.id', 'users.first_name', 'users.name', 'users.email', 'userroles.role')
            ->join('userroles', 'users.user_role', '=', 'userroles.id')
            ->orderBy('userroles.role')
            ->get();

        $roles = DB::table('userroles')->select('id', 'role')->get();

        return ['users' => $users, 'roles' => $roles];
    }

    public function verwijder(Request $request, $id)
    {
        $currentUser = Auth::user();

        if (!Hash::check($request->input('password'), $currentUser->password)) {
            return redirect()->route('super-users.index')
                ->with('error', 'Onjuist wachtwoord. Gebruiker is niet verwijderd.');
        }

        DB::table('users')->where('id', $id)->delete();
        return redirect()->route('super-users.index');
    }

    public function toevoegen(Request $request)
    {
        DB::table('users')->insert([
            'first_name' => $request->input('first_name'),
            'name'       => $request->input('name'),
            'prefix'     => $request->input('prefix', ''),
            'email'      => $request->input('email'),
            'password'   => bcrypt($request->input('password')),
            'employee_code' => $request->input('employee_code'),
            'user_role'  => $request->input('user_role'),
        ]);
        return redirect()->route('super-users.index');
    }

    public function bewerkenVerify(Request $request, $id)
    {
        $currentUser = Auth::user();

        if (!Hash::check($request->input('password'), $currentUser->password)) {
            return redirect()->route('super-users.index')
                ->with('error', 'Onjuist wachtwoord. Bewerken geannuleerd.');
        }

        DB::table('users')->where('id', $id)->update([
            'user_role' => $request->input('user_role'),
        ]);

        return redirect()->route('super-users.index')
            ->with('success', 'Gebruiker succesvol bijgewerkt.');
    }

    
    public function index(): View
    {
        $data = $this->allUsers();
        return view('admin.super-user-list', $data);
    }
}
