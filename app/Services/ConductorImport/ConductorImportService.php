<?php

namespace App\Services\ConductorImport;

use App\Models\ImportLog;
use Illuminate\Support\Facades\Log;

/**
 * Servicio principal para procesar importación de conductores desde archivos CSV o Excel
 *
 * Coordina el flujo de importación, delegando el procesamiento específico de archivos
 * a ConductorImportFileProcessor y el seguimiento de progreso a ConductorImportProgressTracker.
 */
class ConductorImportService
{
    /**
     * @param  ConductorImportFileProcessor  $fileProcessor  Procesador de archivos CSV/Excel
     * @param  ConductorImportProgressTracker  $progressTracker  Seguimiento de progreso
     */
    public function __construct(
        private ConductorImportFileProcessor $fileProcessor,
        private ConductorImportProgressTracker $progressTracker
    ) {}

    /**
     * Procesa un archivo de importación de conductores (CSV o Excel)
     *
     * Este método es el punto de entrada principal para la importación. Maneja la validación
     * del archivo, determina la estrategia de procesamiento según el tipo y tamaño del archivo,
     * y coordina el flujo de importación. Aumenta automáticamente los límites de tiempo y memoria
     * para manejar archivos grandes.
     *
     * @param  string  $sessionId  Identificador único de la sesión de importación
     * @param  string  $filePath  Ruta relativa o absoluta al archivo a procesar
     * @param  string  $extension  Extensión del archivo: 'csv' o 'xlsx'
     * @param  bool  $useSession  Si es true, usa sesión PHP para el progreso; si es false, usa ImportLog en BD
     * @return array{
     *     importados: int,
     *     duplicados: int,
     *     errores: array<int, string>,
     *     total: int
     * }
     *
     * @throws \Exception Si el archivo no existe o no se puede procesar
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
     * Procesa un archivo CSV de conductores
     *
     * Delega el procesamiento al ConductorImportFileProcessor usando el método optimizado
     * que procesa línea por línea para manejar archivos grandes eficientemente.
     *
     * @param  string  $sessionId  Identificador único de la sesión de importación
     * @param  string  $fullPath  Ruta completa al archivo CSV
     * @param  bool  $useSession  Si es true, usa sesión PHP; si es false, usa ImportLog
     * @return array{
     *     importados: int,
     *     duplicados: int,
     *     errores: array<int, string>,
     *     total: int
     * }
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
     * Procesa un archivo Excel de conductores
     *
     * Delega el procesamiento al ConductorImportFileProcessor. El parámetro $optimized
     * se usa solo para mostrar advertencias al usuario, pero el procesamiento real
     * siempre usa el método estándar de Excel (carga todo en memoria).
     *
     * @param  string  $sessionId  Identificador único de la sesión de importación
     * @param  string  $fullPath  Ruta completa al archivo Excel
     * @param  bool  $useSession  Si es true, usa sesión PHP; si es false, usa ImportLog
     * @param  bool  $optimized  Indica si el archivo es grande (>10MB), solo para mostrar advertencias
     * @return array{
     *     importados: int,
     *     duplicados: int,
     *     errores: array<int, string>,
     *     total: int
     * }
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
     * Resuelve la ruta completa del archivo
     *
     * Convierte rutas relativas a rutas absolutas dentro del directorio storage/app.
     * Si la ruta ya es absoluta o ya incluye storage/app, la retorna sin modificar.
     *
     * @param  string  $filePath  Ruta relativa o absoluta del archivo
     * @return string Ruta absoluta completa del archivo
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
     * Actualiza el progreso de la importación
     *
     * Actualiza el progreso usando sesión PHP o ImportLog en base de datos según
     * el parámetro $useSession. Convierte automáticamente la clave 'log' a 'logs'
     * cuando se usa ImportLog para mantener compatibilidad con el modelo.
     *
     * @param  string  $sessionId  Identificador único de la sesión de importación
     * @param  bool  $useSession  Si es true, usa sesión PHP; si es false, usa ImportLog
     * @param  array<string, mixed>  $data  Datos del progreso a actualizar (estado, progreso, mensaje, log, etc.)
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
