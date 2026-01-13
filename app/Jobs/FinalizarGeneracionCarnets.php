<?php

namespace App\Jobs;

use App\Models\CarnetGenerationLog;
use App\Models\Conductor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class FinalizarGeneracionCarnets implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected string $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $sessionId)
    {
        $this->sessionId = $sessionId;
        $this->timeout = 600; // 10 minutos
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Aumentar límites de memoria y tiempo para procesar muchos archivos
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $log = CarnetGenerationLog::where('session_id', $this->sessionId)->first();

        if (! $log) {
            Log::error("CarnetGenerationLog no encontrado para finalizar: {$this->sessionId}");

            return;
        }

        try {
            $log->agregarLog('📦 Iniciando creación de archivo ZIP...', 'info');

            // Obtener todos los conductores con carnets generados
            $conductores = Conductor::whereNotNull('ruta_carnet')->get();

            if ($conductores->isEmpty()) {
                throw new \Exception('No hay carnets generados para comprimir');
            }

            // Crear directorio para ZIP si no existe
            $zipDir = public_path('storage/carnets');
            if (! File::exists($zipDir)) {
                File::makeDirectory($zipDir, 0755, true);
            }

            $zipPath = $zipDir.'/carnets_'.$this->sessionId.'.zip';
            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('No se pudo crear el archivo ZIP');
            }

            $archivosAgregados = 0;
            foreach ($conductores as $conductor) {
                if ($conductor->ruta_carnet) {
                    $rutaCompleta = storage_path('app/'.$conductor->ruta_carnet);
                    if (File::exists($rutaCompleta)) {
                        $nombreArchivo = 'carnet_'.$conductor->cedula.'.pdf';
                        if ($zip->addFile($rutaCompleta, $nombreArchivo)) {
                            $archivosAgregados++;
                        }
                    }
                }
            }

            $zip->close();

            if ($archivosAgregados === 0) {
                throw new \Exception('No se encontraron archivos PDF para comprimir');
            }

            $log->agregarLog("✅ Archivo ZIP creado con {$archivosAgregados} carnets", 'success', [
                'archivos_agregados' => $archivosAgregados,
                'ruta_zip' => 'carnets/carnets_'.$this->sessionId.'.zip',
            ]);

            // Limpiar ZIPs viejos antes de actualizar el log
            $this->limpiarZipsViejos();

            // Actualizar log como completado
            $log->update([
                'estado' => 'completado',
                'completed_at' => now(),
                'archivo_zip' => 'carnets/carnets_'.$this->sessionId.'.zip',
                'mensaje' => "Generación completada: {$log->exitosos} exitosos, {$log->errores} errores",
            ]);

            Log::info("ZIP creado con {$archivosAgregados} archivos para sesión: {$this->sessionId}");

        } catch (\Exception $e) {
            Log::error('Error finalizando generación de carnets: '.$e->getMessage(), [
                'session_id' => $this->sessionId,
                'trace' => $e->getTraceAsString(),
            ]);

            $log->update([
                'estado' => 'error',
                'error' => $e->getMessage(),
                'completed_at' => now(),
                'mensaje' => 'Error al finalizar: '.$e->getMessage(),
            ]);

            $log->agregarLog('❌ Error al crear ZIP: '.$e->getMessage(), 'error');

            throw $e;
        }
    }

    /**
     * Limpiar ZIPs viejos, manteniendo solo los 2 más recientes
     */
    protected function limpiarZipsViejos(): void
    {
        $zipDir = public_path('storage/carnets');

        if (! File::exists($zipDir)) {
            return;
        }

        // Obtener todos los archivos ZIP ordenados por fecha de modificación
        $zips = collect(File::files($zipDir))
            ->filter(function ($file) {
                return strtolower($file->getExtension()) === 'zip';
            })
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            })
            ->values();

        // Si hay más de 2, eliminar los más viejos
        if ($zips->count() > 2) {
            $zipsParaEliminar = $zips->slice(2);

            foreach ($zipsParaEliminar as $zip) {
                try {
                    File::delete($zip->getPathname());
                    Log::info('ZIP antiguo eliminado: '.$zip->getFilename());
                } catch (\Exception $e) {
                    Log::warning('Error eliminando ZIP antiguo: '.$e->getMessage());
                }
            }
        }
    }
}
