<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para eliminar jobs fallidos (Super Poder)
 *
 * Permite eliminar jobs fallidos de la tabla failed_jobs.
 * Requiere permisos especiales (solo rol Mango o permiso específico).
 */
class EliminarJobsFallidos extends Tool
{
    protected string $description = 'Elimina jobs fallidos de la tabla failed_jobs. Permite eliminar por ID, UUID, o todos los jobs fallidos. Requiere permisos especiales (solo rol Mango).';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos - solo rol Mango puede usar esta herramienta
        $user = Auth::user();
        if (! $user->hasRole('Mango')) {
            return Response::error(
                'No tienes permisos para eliminar jobs fallidos. Esta funcionalidad solo está disponible para el rol Mango.',
                [
                    'code' => 'PERMISSION_DENIED',
                    'hint' => 'Se requiere el rol "Mango" para acceder a esta funcionalidad de super poderes.',
                ]
            );
        }

        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:failed_jobs,id'],
            'uuid' => ['nullable', 'string', 'exists:failed_jobs,uuid'],
            'eliminar_todos' => ['nullable', 'boolean'],
            'confirmar' => ['required', 'boolean', 'accepted'],
        ], [
            'id.exists' => 'El job fallido con el ID especificado no existe.',
            'uuid.exists' => 'El job fallido con el UUID especificado no existe.',
            'confirmar.accepted' => 'Debes confirmar la eliminación estableciendo confirmar en true.',
            'eliminar_todos.required_without' => 'Debes especificar id, uuid o eliminar_todos.',
        ]);

        // Validar que se proporcione al menos un criterio
        if (! isset($validated['id']) && ! isset($validated['uuid']) && ! ($validated['eliminar_todos'] ?? false)) {
            return Response::error(
                'Debes especificar un ID, UUID o establecer eliminar_todos en true.',
                [
                    'code' => 'VALIDATION_ERROR',
                    'hint' => 'Proporciona id, uuid o eliminar_todos=true para eliminar jobs fallidos.',
                ]
            );
        }

        // Validar que no se proporcionen múltiples criterios
        $criterios = 0;
        if (isset($validated['id'])) {
            $criterios++;
        }
        if (isset($validated['uuid'])) {
            $criterios++;
        }
        if ($validated['eliminar_todos'] ?? false) {
            $criterios++;
        }

        if ($criterios > 1) {
            return Response::error(
                'Solo puedes especificar un criterio a la vez: id, uuid o eliminar_todos.',
                [
                    'code' => 'VALIDATION_ERROR',
                    'hint' => 'Proporciona solo uno de: id, uuid o eliminar_todos.',
                ]
            );
        }

        try {
            // Eliminar todos
            if ($validated['eliminar_todos'] ?? false) {
                $totalAntes = DB::table('failed_jobs')->count();
                $deleted = DB::table('failed_jobs')->delete();

                return Response::structured([
                    'success' => true,
                    'message' => "Se eliminaron {$deleted} jobs fallidos exitosamente.",
                    'eliminados' => $deleted,
                    'total_antes' => $totalAntes,
                    'criterio' => 'todos',
                ]);
            }

            // Eliminar por ID o UUID
            $query = DB::table('failed_jobs');

            if (isset($validated['id'])) {
                $query->where('id', $validated['id']);
                $criterio = 'id';
                $valor = $validated['id'];
            } else {
                // uuid
                $query->where('uuid', $validated['uuid']);
                $criterio = 'uuid';
                $valor = $validated['uuid'];
            }

            // Obtener información del job antes de eliminarlo
            $job = $query->first();

            // Verificar que el job existe
            if (! $job) {
                return Response::error(
                    'El job fallido especificado no existe.',
                    [
                        'code' => 'NOT_FOUND',
                    ]
                );
            }

            // Eliminar el job específico (recrear la query)
            $deleteQuery = DB::table('failed_jobs');
            if ($criterio === 'id') {
                $deleteQuery->where('id', $validated['id']);
            } else {
                $deleteQuery->where('uuid', $validated['uuid']);
            }

            $deleted = $deleteQuery->delete();

            if ($deleted === 0) {
                return Response::error(
                    'No se pudo eliminar el job fallido.',
                    [
                        'code' => 'DELETE_FAILED',
                    ]
                );
            }

            return Response::structured([
                'success' => true,
                'message' => 'Job fallido eliminado exitosamente.',
                'eliminado' => [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                ],
                'criterio' => $criterio,
            ]);
        } catch (\Exception $e) {
            return Response::error(
                'Error al eliminar jobs fallidos: '.$e->getMessage(),
                [
                    'code' => 'DELETE_ERROR',
                ]
            );
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('ID del job fallido a eliminar'),
            'uuid' => $schema->string()
                ->description('UUID del job fallido a eliminar'),
            'eliminar_todos' => $schema->boolean()
                ->default(false)
                ->description('Si es true, elimina todos los jobs fallidos (requiere confirmar=true)'),
            'confirmar' => $schema->boolean()
                ->description('Debe ser true para confirmar la eliminación (requerido)'),
        ];
    }

    public function name(): string
    {
        return 'eliminar_jobs_fallidos';
    }
}
