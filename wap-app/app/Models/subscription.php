<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public $timestamps = false;
    protected $table = 'subscriptions';

    protected $fillable = [
        'company',
        'type',
        'start_date',
        'end_date',
        'price',
        'notes',
        'identifier',
        'token',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company', 'id');
    }

    public function subscriptionType()
    {
        return $this->belongsTo(SubscriptionType::class, 'type', 'id');
    }

    public function stations()
    {
        return $this->belongsToMany(Station::class, 'subscription_station', 'subscription', 'station');
    }

    public function endpointActivities()
    {
        return $this->hasMany(EndpointActivity::class, 'identifier', 'identifier');
    }
}