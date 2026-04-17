<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EndpointActivity extends Model
{
    public $timestamps = false;
    protected $table = 'endpoint_activity';

    protected $fillable = [
        'identifier',
        'endpoint_used',
        'files_downloaded',
        'activity_date',
        'activity_time',
        'authorized',
        'data_transferred',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'identifier', 'identifier');
    }
}