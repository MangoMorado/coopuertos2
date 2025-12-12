<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Conductor extends Model
{
    use HasFactory;

    // Campos permitidos para asignación masiva
    protected $fillable = [
        'uuid',
        'nombres',
        'apellidos',
        'cedula',
        'conductor_tipo',
        'rh',
        'numero_interno',
        'celular',
        'correo',
        'fecha_nacimiento',
        'otra_profesion',
        'nivel_estudios',
        'foto',
        'estado',
    ];

    // Generar UUID automáticamente al crear un registro
    protected static function booted()
    {
        static::creating(function ($conductor) {
            $conductor->uuid = (string) Str::uuid();
        });
    }

    // Relación muchos a muchos con vehículos a través de conductor_vehicle
    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'conductor_vehicle')
                    ->withPivot('estado', 'fecha_asignacion', 'fecha_desasignacion', 'observaciones')
                    ->withTimestamps();
    }

    // Obtener el vehículo activo del conductor
    public function vehiculoActivo()
    {
        return $this->vehicles()
                    ->wherePivot('estado', 'activo')
                    ->first();
    }

    // Obtener todas las asignaciones (activas e inactivas)
    public function asignaciones()
    {
        return $this->hasMany(ConductorVehicle::class);
    }

    // Obtener la asignación activa
    public function asignacionActiva()
    {
        return $this->hasOne(ConductorVehicle::class)
                    ->where('estado', 'activo');
    }

    /**
     * Asignar un vehículo a este conductor
     * Si ya tiene un vehículo activo, lo desactiva primero
     */
    public function asignarVehiculo($vehicleId, $observaciones = null)
    {
        // Desactivar cualquier asignación activa previa
        $this->asignaciones()
            ->where('estado', 'activo')
            ->update([
                'estado' => 'inactivo',
                'fecha_desasignacion' => now(),
            ]);

        // Crear nueva asignación activa
        return ConductorVehicle::create([
            'conductor_id' => $this->id,
            'vehicle_id' => $vehicleId,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
            'observaciones' => $observaciones,
        ]);
    }

    /**
     * Desasignar el vehículo activo del conductor
     */
    public function desasignarVehiculo($observaciones = null)
    {
        $asignacionActiva = $this->asignacionActiva()->first();
        
        if ($asignacionActiva) {
            $asignacionActiva->update([
                'estado' => 'inactivo',
                'fecha_desasignacion' => now(),
                'observaciones' => $observaciones,
            ]);
            return true;
        }
        
        return false;
    }
}
