<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SuperUserViewController extends Controller
{
    /**
     * Display the superuser creation view.
     */
    public function create(): View
    {
        return view('auth.superuser');
    }


    public function allusers (): array
    {
        $users = DB::table('users')->select('users.id', 'users.first_name', 'users.name', 'userroles.role')
        ->join('userroles', 'users.user_role', '=', 'userroles.id')
        ->get();

        return ['users' => $users];
    }

    public function verwijder($id): array
    {
        DB::table('users')->where('id', $id)->delete();
        return $this->allusers();
    }

    public function toevoegen($first_name, $name, $email, $password, $user_role): array
    {
        DB::table('users')->insert([
            'first_name' => $first_name,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'user_role' => $user_role
        ]);
        return $this->allusers();
    }

    /**
     * Toon alle users in view
     */
    public function index(): View
    {
        $data = $this->allusers();
        return view('super-users', $data);
    }

    
    
}
?>