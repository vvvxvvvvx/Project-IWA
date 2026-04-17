<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OriginalMeasurement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'corrected_measurement',
        'missing_field',
        'inavlid_temperature',
    ];

    public function measurement()
    {
        return $this->belongsTo(Measurement::class, 'corrected_measurement', 'id');
    }
}