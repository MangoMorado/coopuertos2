<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Vehicle
 *
 * Representa un vehículo de la cooperativa de transporte.
 * Gestiona la información técnica del vehículo y sus asignaciones con conductores.
 *
 * @property int $id ID único del vehículo
 * @property string|null $tipo Tipo de vehículo (bus, microbús, etc.)
 * @property string|null $marca Marca del vehículo
 * @property string|null $modelo Modelo del vehículo
 * @property int|null $anio_fabricacion Año de fabricación
 * @property string|null $placa Número de placa del vehículo
 * @property string|null $chasis_vin Número de chasis o VIN
 * @property int|null $capacidad_pasajeros Capacidad de pasajeros
 * @property float|null $capacidad_carga_kg Capacidad de carga en kilogramos
 * @property string|null $combustible Tipo de combustible
 * @property \Illuminate\Support\Carbon|null $ultima_revision_tecnica Fecha de última revisión técnica
 * @property string|null $estado Estado del vehículo (activo, inactivo, etc.)
 * @property string|null $propietario_nombre Nombre del propietario del vehículo
 * @property int|null $conductor_id ID del conductor asignado (legacy, usar relaciones)
 * @property string|null $foto Foto del vehículo en formato base64
 */
class Vehicle extends Model
{
    use HasFactory;

    /**
     * Campos permitidos para asignación masiva
     *
     * @var array<int, string>
     */
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

    /**
     * Casts para convertir automáticamente tipos de datos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ultima_revision_tecnica' => 'date',
    ];

    /**
     * Relación con conductor (legacy)
     *
     * Relación antigua mantenida por compatibilidad. Se recomienda
     * usar las relaciones `conductores()` y `asignaciones()` para
     * gestionar múltiples conductores a lo largo del tiempo.
     *
     * @return BelongsTo Relación muchos a uno con Conductor
     */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    /**
     * Relación muchos a muchos con conductores
     *
     * Un vehículo puede tener múltiples conductores asignados a lo largo del tiempo,
     * y un conductor puede estar asignado a múltiples vehículos. La relación se gestiona
     * a través de la tabla pivot `conductor_vehicle`.
     *
     * @return BelongsToMany Relación muchos a muchos con Conductor
     */
    public function conductores(): BelongsToMany
    {
        return $this->belongsToMany(Conductor::class, 'conductor_vehicle')
            ->withPivot('estado', 'fecha_asignacion', 'fecha_desasignacion', 'observaciones')
            ->withTimestamps();
    }

    /**
     * Obtener todos los conductores activos del vehículo
     *
     * Retorna una colección de conductores que actualmente están asignados
     * al vehículo con estado 'activo' en la tabla pivot.
     *
     * @return Collection<int, Conductor> Colección de conductores activos
     */
    public function conductoresActivos(): Collection
    {
        return $this->conductores()
            ->wherePivot('estado', 'activo')
            ->get();
    }

    /**
     * Obtener todas las asignaciones de conductores (activas e inactivas)
     *
     * Retorna todas las relaciones conductor-vehículo, incluyendo
     * el historial completo de asignaciones.
     *
     * @return HasMany Relación uno a muchos con ConductorVehicle
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(ConductorVehicle::class);
    }
}
