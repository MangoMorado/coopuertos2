<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo CarnetDownload
 *
 * Registra el estado de las descargas de archivos ZIP con carnets generados.
 * Permite hacer seguimiento del proceso de empaquetado y descarga.
 *
 * @property int $id ID único del registro de descarga
 * @property string $session_id Identificador único de la sesión de descarga
 * @property int $total Total de carnets a incluir en el ZIP
 * @property int $procesados Cantidad de carnets procesados para el ZIP
 * @property string $estado Estado actual (procesando, completado, error)
 * @property string|null $archivo_zip Ruta del archivo ZIP generado
 * @property string|null $error Mensaje de error si la descarga falló
 * @property array<int, array{timestamp: string, tipo: string, mensaje: string, data: array}>|null $logs Array de logs detallados
 */
class CarnetDownload extends Model
{
    /**
     * Campos permitidos para asignación masiva
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'total',
        'procesados',
        'estado',
        'archivo_zip',
        'error',
        'logs',
    ];

    /**
     * Casts para convertir automáticamente tipos de datos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total' => 'integer',
        'procesados' => 'integer',
        'logs' => 'array',
    ];
}
