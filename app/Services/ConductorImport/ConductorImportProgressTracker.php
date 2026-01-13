<?php

namespace App\Services\ConductorImport;

use App\Models\ImportLog;
use Illuminate\Support\Facades\Log;

class ConductorImportProgressTracker
{
    /**
     * Actualizar progreso usando sesión (para controlador)
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
     * Actualizar progreso usando ImportLog (para Job)
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
     * Agregar log al ImportLog
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
