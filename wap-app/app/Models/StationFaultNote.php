<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationFaultNote extends Model
{
    protected $table = 'station_fault_note';

    public $timestamps = false;

    protected $fillable = ['station_fault_id', 'message'];

    protected $casts = ['created_at' => 'datetime'];

    public function fault()
    {
        return $this->belongsTo(StationFault::class, 'station_fault_id');
    }
}
