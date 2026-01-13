<?php

namespace App\Mcp\Tools;

use App\Models\Propietario;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para eliminar un propietario
 */
class EliminarPropietario extends Tool
{
    protected string $description = 'Elimina un propietario del sistema. Requiere permisos de eliminación de propietarios. Esta acción no se puede deshacer.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('eliminar propietarios')) {
            return Response::error(
                'No tienes permisos para eliminar propietarios.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'eliminar propietarios']
            );
        }

        $propietarioId = $request->get('propietario_id');
        $propietario = Propietario::find($propietarioId);

        if (! $propietario) {
            return Response::error(
                'Propietario no encontrado.',
                ['code' => 'NOT_FOUND', 'propietario_id' => $propietarioId]
            );
        }

        $propietarioData = [
            'id' => $propietario->id,
            'nombre_completo' => $propietario->nombre_completo,
            'numero_identificacion' => $propietario->numero_identificacion,
        ];

        $propietario->delete();

        return Response::structured([
            'success' => true,
            'message' => 'Propietario eliminado exitosamente',
            'propietario_eliminado' => $propietarioData,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'propietario_id' => $schema->integer()->description('ID del propietario a eliminar'),
        ];
    }

    public function name(): string
    {
        return 'eliminar_propietario';
    }
}
