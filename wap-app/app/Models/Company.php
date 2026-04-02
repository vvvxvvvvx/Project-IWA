<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public $timestamps = false;
    protected $table = 'companies';

    protected $fillable = [
        'name',
        'city',
        'street',
        'number',
        'number_additional',
        'zip_code',
        'country',
        'email',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country', 'country_code');
    }

    public function relations()
    {
        return $this->hasMany(Relation::class, 'company', 'id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'company', 'id');
    }
}