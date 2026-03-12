<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'country_code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'country_code',
        'country',
    ];

    public function companies()
    {
        return $this->hasMany(Company::class, 'country', 'country_code');
    }

    public function geolocations()
    {
        return $this->hasMany(Geolocation::class, 'country_code', 'country_code');
    }

    public function nearestlocations()
    {
        return $this->hasMany(Nearestlocation::class, 'country_code', 'country_code');
    }
}