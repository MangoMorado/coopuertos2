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

class ProcesarGeneracionCarnets implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected string $sessionId;

    protected ?int $userId;

    protected string $tipo;

    protected ?int $templateId;

    protected ?array $conductorIds;

    /**
     * Create a new job instance.
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
     * Execute the job.
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
