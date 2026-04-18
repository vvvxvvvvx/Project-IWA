<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationFault extends Model
{
    protected $table = 'station_fault';

    protected $fillable = ['station', 'type', 'status', 'description'];

    public function notes()
    {
        return $this->hasMany(StationFaultNote::class, 'station_fault_id')->orderBy('created_at', 'asc');
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'offline'             => 'Offline',
            'ontbrekende_data'    => 'Ontbrekende data',
            'temperatuurcorrectie'=> 'Temperatuurcorrectie',
            default               => 'Overig',
        };
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'open'          => 'Open',
            'in_behandeling'=> 'In behandeling',
            'opgelost'      => 'Opgelost',
            default         => $this->status,
        };
    }
}
