<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modelo Conductor
 *
 * Representa un conductor de la cooperativa de transporte.
 * Gestiona la información personal, profesional y las asignaciones de vehículos.
 *
 * @property string $uuid Identificador único universal del conductor
 * @property string $nombres Nombres del conductor
 * @property string $apellidos Apellidos del conductor
 * @property string $cedula Número de cédula de identidad
 * @property string|null $conductor_tipo Tipo de conductor (propietario, relevo, etc.)
 * @property string|null $rh Grupo sanguíneo y factor RH
 * @property string|null $numero_interno Número interno asignado al conductor
 * @property string|null $vehiculo Placa del vehículo asignado (legacy, usar relaciones)
 * @property string|null $celular Número de teléfono celular
 * @property string|null $correo Dirección de correo electrónico
 * @property \Illuminate\Support\Carbon|null $fecha_nacimiento Fecha de nacimiento
 * @property string|null $otra_profesion Otra profesión del conductor
 * @property string|null $nivel_estudios Nivel de estudios alcanzado
 * @property bool $relevo Indica si el conductor es relevo
 * @property string|null $foto Foto del conductor en formato base64
 * @property string|null $ruta_carnet Ruta del archivo de carnet generado
 * @property string|null $estado Estado del conductor (activo, inactivo, etc.)
 */
class Conductor extends Model
{
    use HasFactory;

    /**
     * Campos permitidos para asignación masiva
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'nombres',
        'apellidos',
        'cedula',
        'conductor_tipo',
        'rh',
        'numero_interno',
        'vehiculo',
        'celular',
        'correo',
        'fecha_nacimiento',
        'otra_profesion',
        'nivel_estudios',
        'relevo',
        'foto',
        'ruta_carnet',
        'estado',
    ];

    /**
     * Casts para convertir automáticamente tipos de datos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'relevo' => 'boolean',
    ];

    /**
     * Generar UUID automáticamente al crear un nuevo registro
     *
     * Se ejecuta en el evento `creating` de Eloquent para asignar
     * un UUID único a cada conductor antes de guardarlo en la base de datos.
     */
    protected static function booted(): void
    {
        static::creating(function ($conductor) {
            $conductor->uuid = (string) Str::uuid();
        });
    }

    /**
     * Relación muchos a muchos con vehículos
     *
     * Un conductor puede estar asignado a múltiples vehículos a lo largo del tiempo,
     * y un vehículo puede tener múltiples conductores. La relación se gestiona
     * a través de la tabla pivot `conductor_vehicle`.
     *
     * @return BelongsToMany Relación muchos a muchos con Vehicle
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'conductor_vehicle')
            ->withPivot('estado', 'fecha_asignacion', 'fecha_desasignacion', 'observaciones')
            ->withTimestamps();
    }

    /**
     * Obtener el vehículo activo del conductor
     *
     * Retorna el vehículo que actualmente está asignado al conductor
     * con estado 'activo' en la tabla pivot.
     *
     * @return Vehicle|null El vehículo activo o null si no hay ninguno
     */
    public function vehiculoActivo(): ?Vehicle
    {
        return $this->vehicles()
            ->wherePivot('estado', 'activo')
            ->first();
    }

    /**
     * Obtener todas las asignaciones de vehículos (activas e inactivas)
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

    /**
     * Obtener la asignación activa de vehículo
     *
     * Retorna la relación ConductorVehicle que está actualmente activa.
     *
     * @return HasOne Relación uno a uno con ConductorVehicle activa
     */
    public function asignacionActiva(): HasOne
    {
        return $this->hasOne(ConductorVehicle::class)
            ->where('estado', 'activo');
    }

    /**
     * Asignar un vehículo a este conductor
     *
     * Si el conductor ya tiene un vehículo activo, lo desactiva automáticamente
     * antes de crear la nueva asignación. Esto garantiza que un conductor
     * solo tenga un vehículo activo a la vez.
     *
     * @param  int  $vehicleId  ID del vehículo a asignar
     * @param  string|null  $observaciones  Observaciones opcionales sobre la asignación
     * @return ConductorVehicle La nueva asignación creada
     */
    public function asignarVehiculo(int $vehicleId, ?string $observaciones = null): ConductorVehicle
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
     *
     * Marca la asignación activa como inactiva, registrando la fecha
     * de desasignación y opcionalmente observaciones.
     *
     * @param  string|null  $observaciones  Observaciones opcionales sobre la desasignación
     * @return bool true si se desasignó correctamente, false si no había asignación activa
     */
    public function desasignarVehiculo(?string $observaciones = null): bool
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
