<?php

namespace App\Mcp\Tools;

use App\Models\CarnetGenerationLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para consultar logs de generación de carnets
 */
class ObtenerLogsGeneracionCarnets extends Tool
{
    protected string $description = 'Consulta los logs de generación de carnets (individuales o masivos). Permite filtrar por session_id, usuario, estado o tipo.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        if (! Auth::user()->hasPermissionTo('ver carnets')) {
            return Response::error(
                'No tienes permisos para ver logs de generación de carnets.',
                [
                    'code' => 'PERMISSION_DENIED',
                    'hint' => 'Se requiere el permiso "ver carnets" para acceder a esta información.',
                ]
            );
        }

        $validated = $request->validate([
            'session_id' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer'],
            'estado' => ['nullable', 'string', 'in:pendiente,procesando,completado,error'],
            'tipo' => ['nullable', 'string', 'in:masivo,individual,plantilla'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'include_logs' => ['nullable', 'boolean'],
        ], [
            'estado.in' => 'El estado debe ser uno de: pendiente, procesando, completado, error',
            'tipo.in' => 'El tipo debe ser uno de: masivo, individual, plantilla',
            'limit.max' => 'El límite máximo es 100 registros',
        ]);

        $query = CarnetGenerationLog::query()->with('user:id,name,email');

        // Filtros
        if (isset($validated['session_id'])) {
            $query->where('session_id', $validated['session_id']);
        }

        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (isset($validated['estado'])) {
            $query->where('estado', $validated['estado']);
        }

        if (isset($validated['tipo'])) {
            $query->where('tipo', $validated['tipo']);
        }

        // Ordenar por más recientes
        $query->orderBy('created_at', 'desc');

        // Límite
        $limit = $validated['limit'] ?? 20;
        $logs = $query->limit($limit)->get();

        // Formatear respuesta
        $includeLogs = $validated['include_logs'] ?? false;
        $formattedLogs = $logs->map(function ($log) use ($includeLogs) {
            $progreso = $log->total > 0 ? round(($log->procesados / $log->total) * 100, 2) : 0;

            $data = [
                'id' => $log->id,
                'session_id' => $log->session_id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
                'tipo' => $log->tipo,
                'estado' => $log->estado,
                'total' => $log->total,
                'procesados' => $log->procesados,
                'exitosos' => $log->exitosos,
                'errores' => $log->errores,
                'progreso' => $progreso,
                'mensaje' => $log->mensaje,
                'error' => $log->error,
                'archivo_zip' => $log->archivo_zip,
                'tiempo_transcurrido' => $log->tiempo_transcurrido,
                'tiempo_estimado_restante' => $log->tiempo_estimado_restante,
                'started_at' => $log->started_at?->toDateTimeString(),
                'completed_at' => $log->completed_at?->toDateTimeString(),
                'created_at' => $log->created_at->toDateTimeString(),
            ];

            if ($includeLogs) {
                $data['logs'] = $log->logs ?? [];
            }

            return $data;
        });

        return Response::structured([
            'total' => $logs->count(),
            'logs' => $formattedLogs,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'session_id' => $schema->string()
                ->description('Filtrar por session_id específico'),
            'user_id' => $schema->integer()
                ->description('Filtrar por ID de usuario'),
            'estado' => $schema->string()
                ->enum(['pendiente', 'procesando', 'completado', 'error'])
                ->description('Filtrar por estado de generación'),
            'tipo' => $schema->string()
                ->enum(['masivo', 'individual', 'plantilla'])
                ->description('Filtrar por tipo de generación'),
            'limit' => $schema->integer()
                ->minimum(1)
                ->maximum(100)
                ->default(20)
                ->description('Número máximo de registros a retornar (máximo 100)'),
            'include_logs' => $schema->boolean()
                ->default(false)
                ->description('Incluir logs detallados en la respuesta'),
        ];
    }

    public function name(): string
    {
        return 'obtener_logs_generacion_carnets';
    }
}
