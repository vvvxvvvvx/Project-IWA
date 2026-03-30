<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    public $timestamps = false;
    protected $table = 'userroles';

    protected $fillable = [
        'role',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'user_role', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(RoleTask::class, 'role_id');
    }
}