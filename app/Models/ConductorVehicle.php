<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConductorVehicle extends Model
{
    use HasFactory;

    protected $table = 'conductor_vehicle';

    protected $fillable = [
        'conductor_id',
        'vehicle_id',
        'estado',
        'fecha_asignacion',
        'fecha_desasignacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_asignacion' => 'date',
        'fecha_desasignacion' => 'date',
    ];

    public function conductor()
    {
        return $this->belongsTo(Conductor::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
