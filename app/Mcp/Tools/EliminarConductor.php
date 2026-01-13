<?php

namespace App\Mcp\Tools;

use App\Models\Conductor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para eliminar un conductor
 */
class EliminarConductor extends Tool
{
    protected string $description = 'Elimina un conductor del sistema. Requiere permisos de eliminación de conductores. Esta acción no se puede deshacer.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('eliminar conductores')) {
            return Response::error(
                'No tienes permisos para eliminar conductores.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'eliminar conductores']
            );
        }

        $conductorId = $request->get('conductor_id');
        $conductor = Conductor::find($conductorId);

        if (! $conductor) {
            return Response::error(
                'Conductor no encontrado.',
                ['code' => 'NOT_FOUND', 'conductor_id' => $conductorId]
            );
        }

        $conductorData = [
            'id' => $conductor->id,
            'uuid' => $conductor->uuid,
            'nombres' => $conductor->nombres,
            'apellidos' => $conductor->apellidos,
            'cedula' => $conductor->cedula,
        ];

        $conductor->delete();

        return Response::structured([
            'success' => true,
            'message' => 'Conductor eliminado exitosamente',
            'conductor_eliminado' => $conductorData,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'conductor_id' => $schema->integer()->description('ID del conductor a eliminar'),
        ];
    }

    public function name(): string
    {
        return 'eliminar_conductor';
    }
}
