<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory;

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

    protected $casts = [
        'errores' => 'array',
        'logs' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que inició la importación
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el tiempo transcurrido en segundos
     */
    public function getTiempoTranscurridoAttribute(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        $endTime = $this->completed_at ?? now();

        return $endTime->diffInSeconds($this->started_at);
    }

    /**
     * Calcular tiempo estimado restante
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
