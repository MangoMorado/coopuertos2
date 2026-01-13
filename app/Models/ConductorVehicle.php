<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo ConductorVehicle
 *
 * Tabla pivot que gestiona la relación muchos a muchos entre conductores y vehículos.
 * Permite mantener un historial completo de asignaciones con fechas y estados.
 *
 * @property int $id ID único de la asignación
 * @property int $conductor_id ID del conductor asignado
 * @property int $vehicle_id ID del vehículo asignado
 * @property string $estado Estado de la asignación (activo, inactivo)
 * @property \Illuminate\Support\Carbon|null $fecha_asignacion Fecha en que se realizó la asignación
 * @property \Illuminate\Support\Carbon|null $fecha_desasignacion Fecha en que se desasignó el vehículo
 * @property string|null $observaciones Observaciones sobre la asignación
 * @property-read Conductor $conductor Relación con el conductor
 * @property-read Vehicle $vehicle Relación con el vehículo
 */
class ConductorVehicle extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos
     *
     * @var string
     */
    protected $table = 'conductor_vehicle';

    /**
     * Campos permitidos para asignación masiva
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conductor_id',
        'vehicle_id',
        'estado',
        'fecha_asignacion',
        'fecha_desasignacion',
        'observaciones',
    ];

    /**
     * Casts para convertir automáticamente tipos de datos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_asignacion' => 'date',
        'fecha_desasignacion' => 'date',
    ];

    /**
     * Relación con el conductor
     *
     * @return BelongsTo Relación muchos a uno con Conductor
     */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    /**
     * Relación con el vehículo
     *
     * @return BelongsTo Relación muchos a uno con Vehicle
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
