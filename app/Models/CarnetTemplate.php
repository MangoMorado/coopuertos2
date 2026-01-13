<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo CarnetTemplate
 *
 * Representa una plantilla de carnet para la generación de carnets de conductores.
 * Almacena la configuración de variables y la imagen base de la plantilla.
 *
 * @property int $id ID único de la plantilla
 * @property string $nombre Nombre descriptivo de la plantilla
 * @property string|null $imagen_plantilla Ruta o contenido de la imagen base de la plantilla
 * @property array<string, mixed> $variables_config Configuración de variables disponibles en la plantilla
 * @property bool $activo Indica si la plantilla está activa y disponible para uso
 */
class CarnetTemplate extends Model
{
    /**
     * Campos permitidos para asignación masiva
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'imagen_plantilla',
        'variables_config',
        'activo',
    ];

    /**
     * Casts para convertir automáticamente tipos de datos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'variables_config' => 'array',
        'activo' => 'boolean',
    ];
}
