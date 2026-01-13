<?php

namespace App\Jobs;

use App\Models\ImportLog;
use App\Services\ConductorImport\ConductorImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para procesar importación de conductores en segundo plano
 *
 * Procesa archivos CSV/Excel de importación de conductores de manera asíncrona.
 * Utiliza ImportLog para el seguimiento de progreso en lugar de sesión PHP.
 * Aumenta límites de tiempo y memoria para manejar archivos grandes.
 */
class ProcesarImportacionConductores implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Identificador único de la sesión de importación
     *
     * @var string
     */
    protected $sessionId;

    /**
     * Ruta relativa al archivo de importación en storage/app
     *
     * @var string
     */
    protected $filePath;

    /**
     * Extensión del archivo ('csv' o 'xlsx')
     *
     * @var string
     */
    protected $extension;

    /**
     * ID del usuario que inició la importación
     *
     * @var int
     */
    protected $userId;

    /**
     * Crea una nueva instancia del job
     *
     * @param  string  $sessionId  Identificador único de la sesión de importación
     * @param  string  $filePath  Ruta relativa al archivo en storage/app
     * @param  string  $extension  Extensión del archivo: 'csv' o 'xlsx'
     * @param  int  $userId  ID del usuario que inició la importación
     */
    public function __construct(string $sessionId, string $filePath, string $extension, int $userId)
    {
        $this->sessionId = $sessionId;
        $this->filePath = $filePath;
        $this->extension = $extension;
        $this->userId = $userId;

        // Configurar timeout para archivos grandes
        $this->timeout = 600; // 10 minutos
    }

    /**
     * Ejecuta el job de importación
     *
     * Busca el ImportLog asociado, procesa el archivo usando ConductorImportService
     * con useSession=false (para usar ImportLog), actualiza el estado del log
     * y elimina el archivo temporal después de procesarlo.
     *
     * @param  ConductorImportService  $importService  Servicio de importación de conductores
     *
     * @throws \Exception Si no se encuentra el ImportLog, el archivo no existe o hay errores en el procesamiento
     */
    public function handle(ConductorImportService $importService): void
    {
        // Aumentar límites para archivos grandes
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $importLog = ImportLog::where('session_id', $this->sessionId)
            ->where('user_id', $this->userId)
            ->first();

        if (! $importLog) {
            Log::error("ImportLog no encontrado para procesar: {$this->sessionId}");

            return;
        }

        try {
            // Actualizar estado a procesando
            $importLog->update([
                'estado' => 'procesando',
                'progreso' => 5,
                'mensaje' => 'Preparando lectura del archivo...',
                'started_at' => now(),
                'logs' => [['mensaje' => 'Iniciando procesamiento en segundo plano...', 'tipo' => 'info']],
            ]);

            // Determinar ruta completa del archivo
            $fullPath = storage_path('app/'.$this->filePath);

            if (! file_exists($fullPath)) {
                throw new \Exception("El archivo no existe: {$fullPath}");
            }

            // Procesar archivo usando el servicio (useSession = false para usar ImportLog)
            $result = $importService->processFile(
                $this->sessionId,
                $this->filePath,
                $this->extension,
                false // No usar sesión, usar ImportLog
            );

            // Marcar como completado
            $importLog->update([
                'estado' => 'completado',
                'progreso' => 100,
                'completed_at' => now(),
                'mensaje' => "Importación completada: {$result['importados']} importados, {$result['duplicados']} duplicados, ".count($result['errores']).' errores',
                'logs' => array_merge($importLog->logs ?? [], [['mensaje' => '✅ Importación completada exitosamente', 'tipo' => 'success']]),
            ]);

            // Limpiar archivo temporal
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }

        } catch (\Exception $e) {
            Log::error('Error en Job de importación: '.$e->getMessage(), [
                'session_id' => $this->sessionId,
                'trace' => $e->getTraceAsString(),
            ]);

            $importLog->update([
                'estado' => 'error',
                'mensaje' => 'Error: '.$e->getMessage(),
                'completed_at' => now(),
                'logs' => array_merge($importLog->logs ?? [], [['mensaje' => '✗ Error: '.$e->getMessage(), 'tipo' => 'error']]),
            ]);

            throw $e;
        }
    }
}
