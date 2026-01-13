<?php

namespace App\Mcp\Tools;

use App\Models\Vehicle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para eliminar un vehículo
 */
class EliminarVehiculo extends Tool
{
    protected string $description = 'Elimina un vehículo del sistema. Requiere permisos de eliminación de vehículos. Esta acción no se puede deshacer.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('eliminar vehiculos')) {
            return Response::error(
                'No tienes permisos para eliminar vehículos.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'eliminar vehiculos']
            );
        }

        $vehiculoId = $request->get('vehiculo_id');
        $vehiculo = Vehicle::find($vehiculoId);

        if (! $vehiculo) {
            return Response::error(
                'Vehículo no encontrado.',
                ['code' => 'NOT_FOUND', 'vehiculo_id' => $vehiculoId]
            );
        }

        $vehiculoData = [
            'id' => $vehiculo->id,
            'placa' => $vehiculo->placa,
            'marca' => $vehiculo->marca,
            'modelo' => $vehiculo->modelo,
        ];

        $vehiculo->delete();

        return Response::structured([
            'success' => true,
            'message' => 'Vehículo eliminado exitosamente',
            'vehiculo_eliminado' => $vehiculoData,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'vehiculo_id' => $schema->integer()->description('ID del vehículo a eliminar'),
        ];
    }

    public function name(): string
    {
        return 'eliminar_vehiculo';
    }
}
