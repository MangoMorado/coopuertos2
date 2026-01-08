<?php

namespace App\Jobs;

use App\Models\CarnetGenerationLog;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Services\CarnetGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GenerarCarnetJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected int $conductorId;

    protected ?string $sessionId;

    protected int $templateId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $conductorId, int $templateId, ?string $sessionId = null)
    {
        $this->conductorId = $conductorId;
        $this->templateId = $templateId;
        $this->sessionId = $sessionId;
        $this->timeout = 300; // 5 minutos por carnet
    }

    /**
     * Execute the job.
     */
    public function handle(CarnetGeneratorService $generator): void
    {
        try {
            $conductor = Conductor::find($this->conductorId);
            $template = CarnetTemplate::find($this->templateId);

            if (! $conductor || ! $template) {
                throw new \Exception('Conductor o plantilla no encontrados');
            }

            // Eliminar carnet anterior si existe
            if ($conductor->ruta_carnet) {
                $rutaAnterior = storage_path('app/'.$conductor->ruta_carnet);
                if (File::exists($rutaAnterior)) {
                    try {
                        File::delete($rutaAnterior);
                        Log::info("Carnet anterior eliminado para conductor ID: {$conductor->id}");
                    } catch (\Exception $e) {
                        Log::warning("No se pudo eliminar carnet anterior para conductor ID: {$conductor->id}. Error: {$e->getMessage()}");
                    }
                }
            }

            // Directorio donde se guardarán los carnets permanentemente
            $carnetsDir = storage_path('app/carnets');
            if (! File::exists($carnetsDir)) {
                File::makeDirectory($carnetsDir, 0755, true);
            }

            // Directorio temporal para procesamiento
            $tempDir = storage_path('app/temp/carnet_'.$conductor->id.'_'.time());
            File::makeDirectory($tempDir, 0755, true);

            try {
                // Generar carnet PDF
                $pdfPath = $generator->generarCarnetPDF($conductor, $template, $tempDir);

                // Verificar que el PDF se generó correctamente
                if (! File::exists($pdfPath)) {
                    throw new \Exception('El archivo PDF no se generó correctamente');
                }

                // Mover PDF a ubicación permanente
                $nombreArchivo = 'carnet_'.$conductor->cedula.'_'.time().'.pdf';
                $rutaPermanente = $carnetsDir.'/'.$nombreArchivo;
                File::move($pdfPath, $rutaPermanente);

                // Verificar que el archivo se movió correctamente
                if (! File::exists($rutaPermanente)) {
                    throw new \Exception('Error al mover el archivo PDF a la ubicación permanente');
                }

                // Actualizar conductor con ruta del carnet solo si el archivo existe
                $rutaRelativa = 'carnets/'.$nombreArchivo;
                $conductor->update(['ruta_carnet' => $rutaRelativa]);

                // Actualizar log si existe sessionId
                if ($this->sessionId) {
                    $log = CarnetGenerationLog::where('session_id', $this->sessionId)->first();
                    if ($log) {
                        $log->increment('procesados');
                        $log->increment('exitosos');
                        $log->agregarLog(
                            "✅ Carnet generado para {$conductor->nombres} {$conductor->apellidos} (Cédula: {$conductor->cedula})",
                            'success',
                            [
                                'conductor_id' => $conductor->id,
                                'cedula' => $conductor->cedula,
                                'archivo' => $nombreArchivo,
                            ]
                        );

                        // Verificar si todos los trabajos terminaron
                        $this->verificarFinalizacion($log);
                    }
                }

                // Limpiar directorio temporal
                if (File::exists($tempDir)) {
                    File::deleteDirectory($tempDir);
                }
            } catch (\Exception $e) {
                // Limpiar directorio temporal en caso de error
                if (File::exists($tempDir)) {
                    File::deleteDirectory($tempDir);
                }
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error generando carnet en Job: '.$e->getMessage(), [
                'conductor_id' => $this->conductorId,
                'template_id' => $this->templateId,
                'session_id' => $this->sessionId,
                'trace' => $e->getTraceAsString(),
            ]);

            // Actualizar log si existe sessionId
            if ($this->sessionId) {
                $log = CarnetGenerationLog::where('session_id', $this->sessionId)->first();
                if ($log) {
                    $log->increment('procesados');
                    $log->increment('errores');
                    $conductor = Conductor::find($this->conductorId);
                    $log->agregarLog(
                        "❌ Error generando carnet para conductor ID {$this->conductorId}: {$e->getMessage()}",
                        'error',
                        [
                            'conductor_id' => $this->conductorId,
                            'cedula' => $conductor?->cedula ?? 'N/A',
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }

            throw $e;
        }
    }

    /**
     * Verificar si todos los trabajos terminaron y encolar finalización
     */
    protected function verificarFinalizacion(CarnetGenerationLog $log): void
    {
        // Usar bloqueo para evitar condiciones de carrera
        DB::transaction(function () use ($log) {
            $log->refresh();

            // Verificar si todos los trabajos terminaron
            if ($log->procesados >= $log->total && $log->estado === 'procesando') {
                // Verificar si ya hay un job de finalización encolado
                $finalizacionEncolada = DB::table('jobs')
                    ->where('queue', 'carnets')
                    ->where('payload', 'like', '%FinalizarGeneracionCarnets%')
                    ->where('payload', 'like', '%"session_id":"'.$log->session_id.'"%')
                    ->exists();

                if (! $finalizacionEncolada) {
                    // Todos los trabajos terminaron, encolar finalización
                    FinalizarGeneracionCarnets::dispatch($log->session_id)
                        ->onQueue('carnets');

                    $log->agregarLog('📦 Todos los carnets generados. Creando archivo ZIP...', 'info');
                }
            }
        });
    }
}
