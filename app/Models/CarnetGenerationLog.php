<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo CarnetGenerationLog
 *
 * Registra el progreso y estado de las generaciones masivas de carnets.
 * Permite hacer seguimiento en tiempo real del proceso de generación.
 *
 * @property int $id ID único del log
 * @property string $session_id Identificador único de la sesión de generación
 * @property int|null $user_id ID del usuario que inició la generación
 * @property string $tipo Tipo de generación (masiva, individual, etc.)
 * @property string $estado Estado actual (procesando, completado, error)
 * @property int $total Total de carnets a generar
 * @property int $procesados Cantidad de carnets procesados
 * @property int $exitosos Cantidad de carnets generados exitosamente
 * @property int $errores Cantidad de errores encontrados
 * @property string|null $mensaje Mensaje de estado o error
 * @property array<int, array{timestamp: string, tipo: string, mensaje: string, data: array}>|null $logs Array de logs detallados
 * @property string|null $archivo_zip Ruta del archivo ZIP generado
 * @property string|null $error Mensaje de error si la generación falló
 * @property \Illuminate\Support\Carbon|null $started_at Fecha y hora de inicio
 * @property \Illuminate\Support\Carbon|null $completed_at Fecha y hora de finalización
 * @property-read int $tiempo_transcurrido Tiempo transcurrido en segundos (accessor)
 * @property-read int $tiempo_estimado_restante Tiempo estimado restante en segundos (accessor)
 */
class CarnetGenerationLog extends Model
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
        'tipo',
        'estado',
        'total',
        'procesados',
        'exitosos',
        'errores',
        'mensaje',
        'logs',
        'archivo_zip',
        'error',
        'started_at',
        'completed_at',
    ];

    /**
     * Casts para convertir automáticamente tipos de datos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total' => 'integer',
        'procesados' => 'integer',
        'exitosos' => 'integer',
        'errores' => 'integer',
        'logs' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que inició la generación
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
        if ($this->procesados <= 0 || $this->total <= 0) {
            return 0;
        }

        $tiempoTranscurrido = $this->tiempo_transcurrido;
        $tiempoPorRegistro = $tiempoTranscurrido / $this->procesados;
        $registrosRestantes = $this->total - $this->procesados;

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

    /**
     * Agregar un log al registro
     *
     * Agrega una entrada de log al array de logs, manteniendo un máximo
     * de 500 entradas (elimina las más antiguas si se excede).
     *
     * @param  string  $mensaje  Mensaje del log
     * @param  string  $tipo  Tipo de log (info, warning, error, etc.)
     * @param  array<string, mixed>  $data  Datos adicionales del log
     */
    public function agregarLog(string $mensaje, string $tipo = 'info', array $data = []): void
    {
        $logs = $this->logs ?? [];
        $logs[] = [
            'timestamp' => now()->toDateTimeString(),
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'data' => $data,
        ];

        // Mantener máximo 500 logs
        if (count($logs) > 500) {
            $logs = array_slice($logs, -500);
        }

        $this->logs = $logs;
        $this->save();
    }
}
