<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo ImportLog
 *
 * Registra el progreso y estado de las importaciones masivas de conductores.
 * Permite hacer seguimiento en tiempo real del proceso de importación.
 *
 * @property int $id ID único del log
 * @property string $session_id Identificador único de la sesión de importación
 * @property int|null $user_id ID del usuario que inició la importación
 * @property string|null $file_path Ruta completa del archivo importado
 * @property string|null $file_name Nombre del archivo importado
 * @property string|null $extension Extensión del archivo (xlsx, csv, etc.)
 * @property string $estado Estado actual (procesando, completado, error)
 * @property float $progreso Porcentaje de progreso (0-100)
 * @property int|null $total Total de registros a importar
 * @property int $procesados Cantidad de registros procesados
 * @property int $importados Cantidad de registros importados exitosamente
 * @property int $duplicados Cantidad de registros duplicados encontrados
 * @property int $errores_count Cantidad de errores encontrados
 * @property string|null $mensaje Mensaje de estado o error
 * @property array<int, array{row: int, mensaje: string, data: array}>|null $errores Array de errores detallados
 * @property array<int, array{timestamp: string, tipo: string, mensaje: string, data: array}>|null $logs Array de logs detallados
 * @property \Illuminate\Support\Carbon|null $started_at Fecha y hora de inicio
 * @property \Illuminate\Support\Carbon|null $completed_at Fecha y hora de finalización
 * @property-read int $tiempo_transcurrido Tiempo transcurrido en segundos (accessor)
 * @property-read int $tiempo_estimado_restante Tiempo estimado restante en segundos (accessor)
 */
class ImportLog extends Model
{
    use HasFactory;

    /**
     * Campos permitidos para asignación masiva
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'file_path',
        'file_name',
        'extension',
        'estado',
        'progreso',
        'total',
        'procesados',
        'importados',
        'duplicados',
        'errores_count',
        'mensaje',
        'errores',
        'logs',
        'started_at',
        'completed_at',
    ];

    /**
     * Casts para convertir automáticamente tipos de datos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'errores' => 'array',
        'logs' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que inició la importación
     *
     * @return BelongsTo Relación muchos a uno con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el tiempo transcurrido en segundos
     *
     * Calcula la diferencia entre la fecha de inicio y la fecha actual
     * (o fecha de finalización si ya está completado).
     *
     * @return int Tiempo transcurrido en segundos (0 si no ha iniciado)
     */
    public function getTiempoTranscurridoAttribute(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        $endTime = $this->completed_at ?? now();

        return max(0, $endTime->diffInSeconds($this->started_at));
    }

    /**
     * Calcular tiempo estimado restante
     *
     * Estima el tiempo restante basándose en el tiempo promedio
     * por registro procesado hasta el momento.
     *
     * @return int Tiempo estimado restante en segundos (0 si no hay datos suficientes)
     */
    public function getTiempoEstimadoRestanteAttribute(): int
    {
        if ($this->progreso <= 0 || $this->procesados <= 0) {
            return 0;
        }

        $tiempoTranscurrido = $this->tiempo_transcurrido;
        $tiempoPorRegistro = $tiempoTranscurrido / $this->procesados;
        $registrosRestantes = ($this->total ?? $this->procesados) - $this->procesados;

        return (int) ($tiempoPorRegistro * $registrosRestantes);
    }

    /**
     * Formatear tiempo en formato legible
     *
     * Convierte segundos a un formato legible (ej: "2h 30m", "45m 15s", "30s").
     *
     * @param  int  $segundos  Cantidad de segundos a formatear
     * @return string Tiempo formateado en formato legible
     */
    public function formatearTiempo(int $segundos): string
    {
        if ($segundos < 60) {
            return "{$segundos}s";
        } elseif ($segundos < 3600) {
            $minutos = floor($segundos / 60);
            $seg = $segundos % 60;

            return "{$minutos}m {$seg}s";
        } else {
            $horas = floor($segundos / 3600);
            $minutos = floor(($segundos % 3600) / 60);

            return "{$horas}h {$minutos}m";
        }
    }
}
