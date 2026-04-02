<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relation extends Model
{
    public $timestamps = false;
    protected $table = 'relations';

    protected $fillable = [
        'name',
        'first_name',
        'initials',
        'prefix',
        'company',
        'function',
        'title',
        'email',
        'phone',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company', 'id');
    }
}