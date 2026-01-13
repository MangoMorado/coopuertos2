<?php

namespace App\Services\ConductorImport;

use App\Models\ImportLog;
use Illuminate\Support\Facades\Log;

/**
 * Seguimiento de progreso para importación de conductores
 *
 * Maneja la actualización de progreso usando sesión PHP (para controladores web)
 * o ImportLog en base de datos (para jobs en cola). Mantiene logs limitados para
 * evitar consumo excesivo de memoria/almacenamiento.
 */
class ConductorImportProgressTracker
{
    /**
     * Actualiza el progreso usando sesión PHP (para controladores web)
     *
     * Actualiza el progreso de la importación en la sesión PHP. Mantiene un máximo
     * de 50 logs para evitar consumo excesivo de memoria en sesiones.
     *
     * @param  string  $sessionId  Identificador único de la sesión de importación
     * @param  array<string, mixed>  $datos  Datos del progreso a actualizar (estado, progreso, mensaje, log, etc.)
     */
    public function updateSessionProgress(string $sessionId, array $datos): void
    {
        try {
            $progreso = session()->get("import_progress_{$sessionId}", []);

            // Obtener logs existentes
            $logsExistentes = $progreso['log'] ?? [];

            // Agregar nuevos logs
            if (isset($datos['log']) && is_array($datos['log'])) {
                $logsExistentes = array_merge($logsExistentes, $datos['log']);
                // Mantener solo los últimos 50 logs
                if (count($logsExistentes) > 50) {
                    $logsExistentes = array_slice($logsExistentes, -50);
                }
                $datos['log'] = $logsExistentes;
            }

            // Actualizar progreso
            $progreso = array_merge($progreso, $datos);
            session()->put("import_progress_{$sessionId}", $progreso);

            // Forzar guardado de sesión para que el frontend pueda leer los cambios inmediatamente
            session()->save();

        } catch (\Exception $e) {
            Log::error('Error actualizando progreso: '.$e->getMessage());
        }
    }

    /**
     * Actualiza el progreso usando ImportLog en base de datos (para jobs en cola)
     *
     * Actualiza el progreso de la importación en el modelo ImportLog. Mantiene un máximo
     * de 200 logs para permitir más información de debug en procesos asíncronos.
     *
     * @param  ImportLog  $importLog  Modelo ImportLog a actualizar
     * @param  array<string, mixed>  $datos  Datos del progreso a actualizar (estado, progreso, mensaje, logs, etc.)
     */
    public function updateImportLog(ImportLog $importLog, array $datos): void
    {
        $logsExistentes = $importLog->logs ?? [];

        if (isset($datos['logs']) && is_array($datos['logs'])) {
            $logsExistentes = array_merge($logsExistentes, $datos['logs']);
            // Mantener más logs para debug (200 en lugar de 100)
            if (count($logsExistentes) > 200) {
                $logsExistentes = array_slice($logsExistentes, -200);
            }
            $datos['logs'] = $logsExistentes;
        }

        $importLog->update($datos);
    }

    /**
     * Agrega un log individual al ImportLog
     *
     * Añade un nuevo log al registro de importación con timestamp. Mantiene un máximo
     * de 200 logs eliminando los más antiguos si se excede el límite.
     *
     * @param  ImportLog  $importLog  Modelo ImportLog al que agregar el log
     * @param  string  $mensaje  Mensaje del log
     * @param  string  $tipo  Tipo de log: 'info', 'success', 'warning', 'error' (default: 'info')
     */
    public function addLog(ImportLog $importLog, string $mensaje, string $tipo = 'info'): void
    {
        $logsExistentes = $importLog->logs ?? [];
        $logsExistentes[] = [
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Mantener máximo 200 logs
        if (count($logsExistentes) > 200) {
            $logsExistentes = array_slice($logsExistentes, -200);
        }

        $importLog->update(['logs' => $logsExistentes]);
    }
}
