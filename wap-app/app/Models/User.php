<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'first_name',
        'initials',
        'prefix',
        'email',
        'employee_code',
        'user_role',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function userrole()
    {
        return $this->belongsTo(Userrole::class, 'user_role', 'id');
    }
}