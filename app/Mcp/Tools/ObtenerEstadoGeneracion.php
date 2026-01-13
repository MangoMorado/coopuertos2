<?php

namespace App\Mcp\Tools;

use App\Models\CarnetGenerationLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para consultar el estado de una generación masiva de carnets
 */
class ObtenerEstadoGeneracion extends Tool
{
    protected string $description = 'Consulta el progreso y estado de una generación masiva de carnets mediante su session_id. Retorna información detallada del progreso, tiempo transcurrido y estimado.';

    public function handle(Request $request): Response|ResponseFactory
    {
        $sessionId = $request->get('session_id');
        $log = CarnetGenerationLog::where('session_id', $sessionId)->first();

        if (! $log) {
            return Response::error(
                'Sesión de generación no encontrada.',
                ['code' => 'NOT_FOUND', 'session_id' => $sessionId]
            );
        }

        $archivoUrl = null;
        if ($log->archivo_zip) {
            $archivoUrl = asset('storage/'.$log->archivo_zip);
        }

        $progreso = $log->total > 0 ? round(($log->procesados / $log->total) * 100, 2) : 0;

        return Response::structured([
            'success' => true,
            'session_id' => $log->session_id,
            'estado' => $log->estado,
            'progreso' => [
                'total' => $log->total,
                'procesados' => $log->procesados,
                'exitosos' => $log->exitosos,
                'errores' => $log->errores,
                'porcentaje' => $progreso,
            ],
            'tiempo' => [
                'transcurrido_segundos' => $log->tiempo_transcurrido,
                'transcurrido_formato' => $log->formatearTiempo($log->tiempo_transcurrido),
                'estimado_restante_segundos' => $log->tiempo_estimado_restante,
                'estimado_restante_formato' => $log->formatearTiempo($log->tiempo_estimado_restante),
            ],
            'mensaje' => $log->mensaje,
            'error' => $log->error,
            'archivo' => [
                'url' => $archivoUrl,
                'ruta' => $log->archivo_zip,
                'disponible' => $archivoUrl !== null,
            ],
            'fechas' => [
                'inicio' => $log->started_at?->toDateTimeString(),
                'finalizacion' => $log->completed_at?->toDateTimeString(),
            ],
            'logs' => $log->logs ?? [],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_id' => $schema->string()->description('ID de sesión de la generación masiva (obtenido de generar_carnets_masivos)'),
        ];
    }

    public function name(): string
    {
        return 'obtener_estado_generacion';
    }
}
