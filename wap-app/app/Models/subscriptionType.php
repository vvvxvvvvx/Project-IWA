<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionType extends Model
{
    public $timestamps = false;
    protected $table = 'subscription_types';

    protected $fillable = [
        'name',
        'description',
        'nr_stations',
        'frequency_in_hours',
        'frequency_in_days',
        'continuous',
        'price_per_station',
        'valid_through',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'type', 'id');
    }
}