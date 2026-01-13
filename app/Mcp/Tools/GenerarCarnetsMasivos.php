<?php

namespace App\Mcp\Tools;

use App\Jobs\ProcesarGeneracionCarnets;
use App\Models\CarnetGenerationLog;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para iniciar generación masiva de carnets
 */
class GenerarCarnetsMasivos extends Tool
{
    protected string $description = 'Inicia la generación masiva de carnets para todos los conductores o para conductores específicos. El proceso se ejecuta en segundo plano mediante jobs en cola. Requiere permisos de creación de carnets.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('crear carnets')) {
            return Response::error(
                'No tienes permisos para generar carnets.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'crear carnets']
            );
        }

        // Verificar que hay una plantilla activa
        $template = CarnetTemplate::where('activo', true)->first();

        if (! $template) {
            return Response::error(
                'No hay plantilla activa para generar los carnets. Por favor, configure una plantilla primero.',
                ['code' => 'NO_TEMPLATE']
            );
        }

        // Obtener conductores a procesar (todos por defecto, o los seleccionados si se envía)
        $conductorIds = $request->get('conductor_ids');

        $query = Conductor::with(['asignacionActiva.vehicle']);
        if ($conductorIds && is_array($conductorIds) && count($conductorIds) > 0) {
            $conductores = $query->whereIn('id', $conductorIds)->get();
        } else {
            $conductores = $query->get();
        }

        if ($conductores->isEmpty()) {
            return Response::error(
                'No hay conductores para generar carnets.',
                ['code' => 'NO_CONDUCTORS']
            );
        }

        // Crear log de generación masiva
        $sessionId = Str::uuid()->toString();
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => $conductores->count(),
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
            'mensaje' => 'Iniciando generación de carnets...',
            'logs' => [
                [
                    'timestamp' => now()->toDateTimeString(),
                    'tipo' => 'info',
                    'mensaje' => '🔄 Iniciando generación masiva de carnets...',
                    'data' => [
                        'template_id' => $template->id,
                        'template_nombre' => $template->nombre,
                        'total_conductores' => $conductores->count(),
                    ],
                ],
            ],
        ]);

        // Encolar job supervisor
        ProcesarGeneracionCarnets::dispatch(
            $sessionId,
            'masivo',
            $user->id,
            $template->id,
            $conductores->pluck('id')->toArray()
        )->onQueue('carnets');

        Log::info("Generación masiva de carnets iniciada vía MCP - Session ID: {$sessionId}, Total: {$conductores->count()}");

        return Response::structured([
            'success' => true,
            'message' => 'Generación de carnets iniciada. El proceso se ejecutará en segundo plano.',
            'session_id' => $sessionId,
            'total_conductores' => $conductores->count(),
            'estado' => 'pendiente',
            'instrucciones' => [
                'Usa la herramienta obtener_estado_generacion con este session_id para consultar el progreso.',
                'Cuando el estado sea "completado", usa descargar_carnet para obtener el archivo ZIP.',
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'conductor_ids' => $schema->array()->items($schema->integer())->nullable()->description('Array de IDs de conductores específicos a procesar. Si es null o vacío, procesa todos los conductores'),
        ];
    }

    public function name(): string
    {
        return 'generar_carnets_masivos';
    }
}
