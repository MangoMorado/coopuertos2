<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'marca',
        'modelo',
        'anio_fabricacion',
        'placa',
        'chasis_vin',
        'capacidad_pasajeros',
        'capacidad_carga_kg',
        'combustible',
        'ultima_revision_tecnica',
        'estado',
        'propietario_nombre',
        'conductor_id',
        'foto',
    ];

    // Relación antigua (mantener por compatibilidad, pero usar asignaciones)
    public function conductor()
    {
        return $this->belongsTo(Conductor::class);
    }

    // Relación muchos a muchos con conductores a través de conductor_vehicle
    public function conductores()
    {
        return $this->belongsToMany(Conductor::class, 'conductor_vehicle')
                    ->withPivot('estado', 'fecha_asignacion', 'fecha_desasignacion', 'observaciones')
                    ->withTimestamps();
    }

    // Obtener todos los conductores activos del vehículo
    public function conductoresActivos()
    {
        return $this->conductores()
                    ->wherePivot('estado', 'activo')
                    ->get();
    }

    // Obtener todas las asignaciones del vehículo
    public function asignaciones()
    {
        return $this->hasMany(ConductorVehicle::class);
    }
}
