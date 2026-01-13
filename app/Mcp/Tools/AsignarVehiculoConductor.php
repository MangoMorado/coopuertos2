<?php

namespace App\Mcp\Tools;

use App\Models\Conductor;
use App\Models\Vehicle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para asignar o desasignar un vehículo a un conductor
 */
class AsignarVehiculoConductor extends Tool
{
    protected string $description = 'Asigna o desasigna un vehículo a un conductor. Si se proporciona vehiculo_id, asigna el vehículo. Si vehiculo_id es null, desasigna el vehículo actual del conductor. Requiere permisos de edición de conductores o vehículos.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos (editar conductores o editar vehículos)
        $user = $request->user();
        if (! $user || (! $user->can('editar conductores') && ! $user->can('editar vehiculos'))) {
            return Response::error(
                'No tienes permisos para asignar vehículos a conductores.',
                [
                    'code' => 'PERMISSION_DENIED',
                    'required_permissions' => ['editar conductores', 'editar vehiculos'],
                ]
            );
        }

        $conductorId = $request->get('conductor_id');
        $vehiculoId = $request->get('vehiculo_id'); // null para desasignar

        $conductor = Conductor::find($conductorId);
        if (! $conductor) {
            return Response::error(
                'Conductor no encontrado.',
                ['code' => 'NOT_FOUND', 'conductor_id' => $conductorId]
            );
        }

        if ($vehiculoId !== null) {
            $vehiculo = Vehicle::find($vehiculoId);
            if (! $vehiculo) {
                return Response::error(
                    'Vehículo no encontrado.',
                    ['code' => 'NOT_FOUND', 'vehiculo_id' => $vehiculoId]
                );
            }

            // Asignar vehículo
            $asignacion = $conductor->asignarVehiculo($vehiculoId, $request->get('observaciones'));

            // Actualizar conductor_id en vehicles para compatibilidad
            $vehiculo->update(['conductor_id' => $conductorId]);

            return Response::structured([
                'success' => true,
                'message' => 'Vehículo asignado exitosamente al conductor',
                'asignacion' => [
                    'conductor_id' => $conductor->id,
                    'conductor_nombre' => $conductor->nombres.' '.$conductor->apellidos,
                    'vehiculo_id' => $vehiculo->id,
                    'vehiculo_placa' => $vehiculo->placa,
                    'fecha_asignacion' => $asignacion->fecha_asignacion,
                    'estado' => $asignacion->estado,
                ],
            ]);
        } else {
            // Desasignar vehículo
            $desasignado = $conductor->desasignarVehiculo($request->get('observaciones'));

            if (! $desasignado) {
                return Response::error(
                    'El conductor no tiene un vehículo asignado.',
                    ['code' => 'NO_ASSIGNMENT', 'conductor_id' => $conductorId]
                );
            }

            // Actualizar conductor_id en vehicles
            Vehicle::where('conductor_id', $conductorId)->update(['conductor_id' => null]);

            return Response::structured([
                'success' => true,
                'message' => 'Vehículo desasignado exitosamente del conductor',
                'conductor' => [
                    'id' => $conductor->id,
                    'nombre' => $conductor->nombres.' '.$conductor->apellidos,
                ],
            ]);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'conductor_id' => $schema->integer()->description('ID del conductor'),
            'vehiculo_id' => $schema->integer()->nullable()->description('ID del vehículo a asignar. Si es null, se desasigna el vehículo actual del conductor'),
            'observaciones' => $schema->string()->nullable()->description('Observaciones sobre la asignación o desasignación'),
        ];
    }

    public function name(): string
    {
        return 'asignar_vehiculo_conductor';
    }
}
