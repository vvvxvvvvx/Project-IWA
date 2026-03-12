<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'name';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'longitude',
        'latitude',
        'elevation',
    ];

    public function measurements()
    {
        return $this->hasMany(Measurement::class, 'station', 'name');
    }

    public function geolocation()
    {
        return $this->hasOne(Geolocation::class, 'station_name', 'name');
    }

    public function nearestlocation()
    {
        return $this->hasOne(Nearestlocation::class, 'station_name', 'name');
    }

    public function subscriptions()
    {
        return $this->belongsToMany(Subscription::class, 'subscription_station', 'station', 'subscription');
    }
}