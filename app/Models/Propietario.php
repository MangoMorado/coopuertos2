<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Propietario
 *
 * Representa un propietario de vehículos de la cooperativa de transporte.
 * Gestiona la información de contacto y datos de identificación.
 *
 * @property int $id ID único del propietario
 * @property string|null $tipo_identificacion Tipo de identificación (CC, NIT, etc.)
 * @property string|null $numero_identificacion Número de identificación
 * @property string|null $nombre_completo Nombre completo del propietario
 * @property string|null $tipo_propietario Tipo de propietario (natural, jurídica, etc.)
 * @property string|null $direccion_contacto Dirección de contacto
 * @property string|null $telefono_contacto Teléfono de contacto
 * @property string|null $correo_electronico Correo electrónico de contacto
 * @property string|null $estado Estado del propietario (activo, inactivo, etc.)
 */
class Propietario extends Model
{
    use HasFactory;

    /**
     * Campos permitidos para asignación masiva
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_identificacion',
        'numero_identificacion',
        'nombre_completo',
        'tipo_propietario',
        'direccion_contacto',
        'telefono_contacto',
        'correo_electronico',
        'estado',
    ];
}
