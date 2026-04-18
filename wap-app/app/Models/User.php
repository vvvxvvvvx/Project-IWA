<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

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

    public function hasTask(string $taskName): bool
    {
        return $this->userrole
            && $this->userrole->tasks()->where('name', $taskName)->exists();
    }
}