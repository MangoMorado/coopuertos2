<?php

namespace App\Services\ConductorImport;

use App\Models\ImportLog;
use Illuminate\Support\Facades\Log;

class ConductorImportService
{
    public function __construct(
        private ConductorImportFileProcessor $fileProcessor,
        private ConductorImportProgressTracker $progressTracker
    ) {}

    /**
     * Procesar archivo de importación
     */
    public function processFile(
        string $sessionId,
        string $filePath,
        string $extension,
        bool $useSession = true
    ): array {
        // Aumentar tiempo de ejecución y memoria
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        try {
            // Determinar ruta completa
            $fullPath = $this->resolveFullPath($filePath);

            // Verificar que el archivo existe
            if (! file_exists($fullPath)) {
                throw new \Exception("El archivo no existe: {$fullPath}. Ruta original: {$filePath}");
            }

            // Determinar tamaño del archivo
            $fileSize = filesize($fullPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            $this->updateProgress($sessionId, $useSession, [
                'estado' => 'procesando',
                'progreso' => 6,
                'mensaje' => "Analizando archivo ({$fileSizeMB} MB)...",
                'log' => [['mensaje' => "📊 Tamaño del archivo: {$fileSizeMB} MB", 'tipo' => 'info']],
            ]);

            // Procesar según extensión
            if ($extension === 'csv') {
                return $this->processCsvFile($sessionId, $fullPath, $useSession);
            } else {
                // Para Excel, decidir estrategia según tamaño
                if ($fileSize > 10 * 1024 * 1024) { // > 10MB
                    return $this->processExcelFile($sessionId, $fullPath, $useSession, true);
                } else {
                    return $this->processExcelFile($sessionId, $fullPath, $useSession, false);
                }
            }

        } catch (\Exception $e) {
            $this->updateProgress($sessionId, $useSession, [
                'estado' => 'error',
                'error' => 'Error al procesar archivo: '.$e->getMessage(),
                'log' => [['mensaje' => '✗ Error: '.$e->getMessage(), 'tipo' => 'error']],
            ]);

            throw $e;
        }
    }

    /**
     * Procesar archivo CSV
     */
    private function processCsvFile(string $sessionId, string $fullPath, bool $useSession): array
    {
        $this->updateProgress($sessionId, $useSession, [
            'progreso' => 5,
            'mensaje' => 'Preparando lectura del archivo...',
            'log' => [['mensaje' => 'Archivo cargado correctamente', 'tipo' => 'success']],
        ]);

        $progressCallback = function (array $data) use ($sessionId, $useSession) {
            $this->updateProgress($sessionId, $useSession, $data);
        };

        return $this->fileProcessor->processCsv($sessionId, $fullPath, $progressCallback);
    }

    /**
     * Procesar archivo Excel
     */
    private function processExcelFile(string $sessionId, string $fullPath, bool $useSession, bool $optimized): array
    {
        if ($optimized) {
            $this->updateProgress($sessionId, $useSession, [
                'progreso' => 8,
                'mensaje' => '⚠️ Archivo Excel grande detectado...',
                'log' => [['mensaje' => '⚠️ Archivo Excel grande. Usando procesamiento estándar (puede consumir mucha memoria)', 'tipo' => 'warning']],
            ]);
        }

        $this->updateProgress($sessionId, $useSession, [
            'progreso' => 5,
            'mensaje' => 'Preparando lectura del archivo...',
            'log' => [['mensaje' => 'Archivo cargado correctamente', 'tipo' => 'success']],
        ]);

        $progressCallback = function (array $data) use ($sessionId, $useSession) {
            $this->updateProgress($sessionId, $useSession, $data);
        };

        return $this->fileProcessor->processExcel($sessionId, $fullPath, $progressCallback);
    }

    /**
     * Resolver ruta completa del archivo
     */
    private function resolveFullPath(string $filePath): string
    {
        // Si la ruta ya es absoluta, usarla directamente
        if (strpos($filePath, '/') === 0 || strpos($filePath, ':\\') !== false || strpos($filePath, ':/') !== false) {
            return $filePath;
        }

        // Si ya incluye storage/app, usarla directamente
        if (strpos($filePath, storage_path('app')) === 0) {
            return $filePath;
        }

        // Es una ruta relativa en storage
        $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);

        return storage_path('app'.DIRECTORY_SEPARATOR.$normalizedPath);
    }

    /**
     * Actualizar progreso usando sesión o ImportLog
     */
    private function updateProgress(string $sessionId, bool $useSession, array $data): void
    {
        if ($useSession) {
            $this->progressTracker->updateSessionProgress($sessionId, $data);
        } else {
            $importLog = ImportLog::where('session_id', $sessionId)->first();
            if ($importLog) {
                // Convertir 'log' a 'logs' para ImportLog
                if (isset($data['log'])) {
                    $data['logs'] = $data['log'];
                    unset($data['log']);
                }
                $this->progressTracker->updateImportLog($importLog, $data);
            }
        }
    }
}
