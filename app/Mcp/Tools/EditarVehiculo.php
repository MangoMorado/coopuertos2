<?php

namespace App\Mcp\Tools;

use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\Vehicle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para editar un vehículo existente
 */
class EditarVehiculo extends Tool
{
    protected string $description = 'Actualiza la información de un vehículo existente. Requiere permisos de edición de vehículos.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('editar vehiculos')) {
            return Response::error(
                'No tienes permisos para editar vehículos.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'editar vehiculos']
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

        $currentYear = now()->year;
        $minYear = 1990; // Año mínimo configurable

        $validated = $request->validate([
            'tipo' => ['sometimes', 'required', 'in:Bus,Camioneta,Taxi'],
            'marca' => ['sometimes', 'required', 'string', 'max:255'],
            'modelo' => ['sometimes', 'required', 'string', 'max:255'],
            'anio_fabricacion' => [
                'sometimes',
                'required',
                'integer',
                'min:'.$minYear,
                'max:'.$currentYear,
            ],
            'placa' => ['sometimes', 'required', 'string', 'max:20', 'unique:vehicles,placa,'.$vehiculo->id.',id'],
            'chasis_vin' => ['nullable', 'string', 'max:255'],
            'capacidad_pasajeros' => [
                'nullable',
                'integer',
                'min:0',
                'max:80',
            ],
            'capacidad_carga_kg' => ['nullable', 'integer', 'min:0'],
            'combustible' => ['sometimes', 'required', 'in:gasolina,diesel,hibrido,electrico'],
            'ultima_revision_tecnica' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'estado' => ['sometimes', 'required', 'in:Activo,En Mantenimiento,Fuera de Servicio'],
            'propietario_nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'conductor_id' => ['nullable', 'exists:conductors,id'],
            'foto_base64' => ['nullable', 'string'],
        ], [
            'anio_fabricacion.min' => 'El año de fabricación no puede ser menor a '.$minYear.'.',
            'anio_fabricacion.max' => 'El año de fabricación no puede ser mayor al año actual ('.$currentYear.').',
            'capacidad_pasajeros.max' => 'La capacidad de pasajeros no puede ser mayor a 80.',
            'ultima_revision_tecnica.before_or_equal' => 'La fecha de revisión técnica no puede ser una fecha futura.',
        ]);

        if (isset($validated['placa'])) {
            $validated['placa'] = Str::upper($validated['placa']);
        }

        // Manejo de foto en base64
        if (isset($validated['foto_base64']) && ! empty($validated['foto_base64'])) {
            $validated['foto'] = $validated['foto_base64'];
            unset($validated['foto_base64']);
        }

        // Gestionar asignación de conductor si se proporciona
        $nuevoConductorId = $validated['conductor_id'] ?? null;
        $conductorIdAnterior = $vehiculo->conductor_id;
        unset($validated['conductor_id']);

        $vehiculo->update($validated);

        // Gestionar la asignación en conductor_vehicle
        if ($nuevoConductorId != $conductorIdAnterior) {
            // Si había un conductor anterior, desasignarlo
            if ($conductorIdAnterior) {
                ConductorVehicle::where('conductor_id', $conductorIdAnterior)
                    ->where('vehicle_id', $vehiculo->id)
                    ->where('estado', 'activo')
                    ->update([
                        'estado' => 'inactivo',
                        'fecha_desasignacion' => now(),
                    ]);
            }

            // Si se asignó un nuevo conductor, crear el registro
            if ($nuevoConductorId) {
                $nuevoConductor = Conductor::find($nuevoConductorId);
                if ($nuevoConductor) {
                    // Desactivar cualquier otro vehículo activo del conductor
                    ConductorVehicle::where('conductor_id', $nuevoConductorId)
                        ->where('estado', 'activo')
                        ->where('vehicle_id', '!=', $vehiculo->id)
                        ->update([
                            'estado' => 'inactivo',
                            'fecha_desasignacion' => now(),
                        ]);

                    // Crear o activar la asignación
                    ConductorVehicle::updateOrCreate(
                        [
                            'conductor_id' => $nuevoConductorId,
                            'vehicle_id' => $vehiculo->id,
                        ],
                        [
                            'estado' => 'activo',
                            'fecha_asignacion' => now(),
                            'fecha_desasignacion' => null,
                        ]
                    );
                }
                $vehiculo->update(['conductor_id' => $nuevoConductorId]);
            } else {
                $vehiculo->update(['conductor_id' => null]);
            }
        }

        $vehiculo->refresh();

        return Response::structured([
            'success' => true,
            'message' => 'Vehículo actualizado exitosamente',
            'vehiculo' => [
                'id' => $vehiculo->id,
                'placa' => $vehiculo->placa,
                'marca' => $vehiculo->marca,
                'modelo' => $vehiculo->modelo,
                'tipo' => $vehiculo->tipo,
                'estado' => $vehiculo->estado,
                'conductor_id' => $vehiculo->conductor_id,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'vehiculo_id' => $schema->integer()->description('ID del vehículo a editar'),
            'tipo' => $schema->string()->enum(['Bus', 'Camioneta', 'Taxi'])->nullable()->description('Tipo de vehículo'),
            'marca' => $schema->string()->nullable()->description('Marca del vehículo'),
            'modelo' => $schema->string()->nullable()->description('Modelo del vehículo'),
            'anio_fabricacion' => $schema->integer()->nullable()->description('Año de fabricación'),
            'placa' => $schema->string()->nullable()->description('Número de placa'),
            'chasis_vin' => $schema->string()->nullable()->description('Número de chasis o VIN'),
            'capacidad_pasajeros' => $schema->integer()->nullable()->description('Capacidad de pasajeros'),
            'capacidad_carga_kg' => $schema->integer()->nullable()->description('Capacidad de carga en kilogramos'),
            'combustible' => $schema->string()->enum(['gasolina', 'diesel', 'hibrido', 'electrico'])->nullable()->description('Tipo de combustible'),
            'ultima_revision_tecnica' => $schema->string()->format('date')->nullable()->description('Fecha de última revisión técnica (formato: YYYY-MM-DD)'),
            'estado' => $schema->string()->enum(['Activo', 'En Mantenimiento', 'Fuera de Servicio'])->nullable()->description('Estado del vehículo'),
            'propietario_nombre' => $schema->string()->nullable()->description('Nombre del propietario del vehículo'),
            'conductor_id' => $schema->integer()->nullable()->description('ID del conductor a asignar (null para desasignar)'),
            'foto_base64' => $schema->string()->nullable()->description('Foto del vehículo en formato base64 (data URI)'),
        ];
    }

    public function name(): string
    {
        return 'editar_vehiculo';
    }
}
