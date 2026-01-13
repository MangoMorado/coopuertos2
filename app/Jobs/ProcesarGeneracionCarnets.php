<?php

namespace App\Jobs;

use App\Models\CarnetGenerationLog;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job supervisor para procesar generación masiva de carnets
 *
 * Coordina la generación masiva de carnets encolando trabajos individuales
 * (GenerarCarnetJob) para cada conductor. No genera los carnets directamente,
 * solo encola los trabajos. El seguimiento se hace mediante CarnetGenerationLog.
 */
class ProcesarGeneracionCarnets implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Identificador único de la sesión de generación
     */
    protected string $sessionId;

    /**
     * ID del usuario que inició la generación (opcional)
     */
    protected ?int $userId;

    /**
     * Tipo de generación ('masivo' por defecto)
     */
    protected string $tipo;

    /**
     * ID de la plantilla a usar (opcional, usa activa si no se especifica)
     */
    protected ?int $templateId;

    /**
     * IDs de conductores específicos a procesar (opcional, todos si es null)
     *
     * @var array<int>|null
     */
    protected ?array $conductorIds;

    /**
     * Crea una nueva instancia del job supervisor
     *
     * @param  string  $sessionId  Identificador único de la sesión de generación
     * @param  string  $tipo  Tipo de generación (default: 'masivo')
     * @param  int|null  $userId  ID del usuario que inició la generación
     * @param  int|null  $templateId  ID de la plantilla a usar (opcional)
     * @param  array<int>|null  $conductorIds  IDs de conductores específicos (opcional)
     */
    public function __construct(
        string $sessionId,
        string $tipo = 'masivo',
        ?int $userId = null,
        ?int $templateId = null,
        ?array $conductorIds = null
    ) {
        $this->sessionId = $sessionId;
        $this->tipo = $tipo;
        $this->userId = $userId;
        $this->templateId = $templateId;
        $this->conductorIds = $conductorIds;
        $this->timeout = 600; // 10 minutos
    }

    /**
     * Ejecuta el job supervisor de generación de carnets
     *
     * Busca el CarnetGenerationLog, obtiene la plantilla activa (o la especificada),
     * obtiene los conductores a procesar (específicos o todos), y encola trabajos
     * individuales GenerarCarnetJob para cada conductor. No marca como completado
     * aquí, eso lo hace FinalizarGeneracionCarnets cuando todos los jobs terminan.
     *
     * @throws \Exception Si no se encuentra el log, no hay plantilla configurada o no hay conductores
     */
    public function handle(): void
    {
        $log = CarnetGenerationLog::where('session_id', $this->sessionId)->first();

        if (! $log) {
            Log::error("CarnetGenerationLog no encontrado para procesar: {$this->sessionId}");

            return;
        }

        try {
            // Actualizar estado a procesando
            $log->update([
                'estado' => 'procesando',
                'started_at' => now(),
                'mensaje' => 'Iniciando encolado de trabajos de generación...',
            ]);

            $log->agregarLog('🔄 Iniciando proceso de generación de carnets...', 'info');

            // Obtener plantilla activa
            $template = $this->templateId
                ? CarnetTemplate::find($this->templateId)
                : CarnetTemplate::where('activo', true)->first();

            if (! $template) {
                throw new \Exception('No hay plantilla configurada para generar los carnets');
            }

            $log->agregarLog("📋 Usando plantilla: {$template->nombre}", 'info');

            // Obtener conductores a procesar
            if ($this->conductorIds) {
                $conductores = Conductor::whereIn('id', $this->conductorIds)->get();
            } else {
                $conductores = Conductor::all();
            }

            if ($conductores->isEmpty()) {
                throw new \Exception('No hay conductores para generar carnets');
            }

            $total = $conductores->count();
            $log->update([
                'total' => $total,
                'mensaje' => "Encolando {$total} trabajos de generación...",
            ]);

            $log->agregarLog("📊 Total de conductores a procesar: {$total}", 'info');

            // Encolar trabajos de generación uno por uno
            $encolados = 0;
            foreach ($conductores as $conductor) {
                GenerarCarnetJob::dispatch($conductor->id, $template->id, $this->sessionId)
                    ->onQueue('carnets'); // Cola específica para carnets

                $encolados++;

                // Actualizar progreso cada 10 trabajos
                if ($encolados % 10 === 0) {
                    $log->update([
                        'mensaje' => "Encolados {$encolados} de {$total} trabajos...",
                    ]);
                }
            }

            $log->agregarLog("✅ Todos los trabajos han sido encolados ({$encolados} trabajos)", 'success');
            $log->update([
                'mensaje' => "Procesando {$total} carnets en segundo plano...",
            ]);

            // El log se actualizará por cada GenerarCarnetJob que se ejecute
            // No marcamos como completado aquí, se hará cuando todos los jobs terminen

        } catch (\Exception $e) {
            Log::error('Error en Job supervisor de generación de carnets: '.$e->getMessage(), [
                'session_id' => $this->sessionId,
                'trace' => $e->getTraceAsString(),
            ]);

            $log->update([
                'estado' => 'error',
                'error' => $e->getMessage(),
                'completed_at' => now(),
                'mensaje' => 'Error: '.$e->getMessage(),
            ]);

            $log->agregarLog('❌ Error fatal: '.$e->getMessage(), 'error');

            throw $e;
        }
    }
}
