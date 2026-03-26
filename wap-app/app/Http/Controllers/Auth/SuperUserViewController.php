<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
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


    // Haal alle gebruikers op voor het super-user overzicht.
    public function allUsers(): array
    {
        $users = DB::table('users')->select('users.id', 'users.first_name', 'users.name', 'userroles.role')
        ->join('userroles', 'users.user_role', '=', 'userroles.id')
        ->get();

        return ['users' => $users];
    }

    // Verwijder een gebruiker en laad daarna het overzicht opnieuw.
    public function verwijder($id): array
    {
        DB::table('users')->where('id', $id)->delete();
        return $this->allUsers();
    }

    // Voeg een gebruiker toe en laad daarna het overzicht opnieuw.
    public function toevoegen($first_name, $name, $email, $password, $user_role): array
    {
        DB::table('users')->insert([
            'first_name' => $first_name,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'user_role' => $user_role
        ]);
        return $this->allUsers();
    }

    /**
     * Toon alle users in view
     */
    public function index(): View
    {
        $data = $this->allUsers();
        return view('admin.super-user-list', $data);
    }

    
    
}
