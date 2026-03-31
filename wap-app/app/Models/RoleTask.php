<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleTask extends Model
{
    public $timestamps = false;
    protected $table = 'role_tasks';

    protected $fillable = [
        'role_id',
        'name',
        'description',
    ];

    public function role()
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }
}