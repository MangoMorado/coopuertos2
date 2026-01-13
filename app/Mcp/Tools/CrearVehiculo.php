<?php

namespace App\Mcp\Tools;

use App\Models\Conductor;
use App\Models\Vehicle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para crear un nuevo vehículo
 */
class CrearVehiculo extends Tool
{
    protected string $description = 'Crea un nuevo vehículo en el sistema. Requiere permisos de creación de vehículos.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('crear vehiculos')) {
            return Response::error(
                'No tienes permisos para crear vehículos.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'crear vehiculos']
            );
        }

        $validated = $request->validate([
            'tipo' => ['required', 'in:Bus,Camioneta,Taxi'],
            'marca' => ['required', 'string', 'max:255'],
            'modelo' => ['required', 'string', 'max:255'],
            'anio_fabricacion' => ['required', 'integer', 'min:1900', 'max:'.now()->year],
            'placa' => ['required', 'string', 'max:20', 'unique:vehicles,placa'],
            'chasis_vin' => ['nullable', 'string', 'max:255'],
            'capacidad_pasajeros' => ['nullable', 'integer', 'min:0'],
            'capacidad_carga_kg' => ['nullable', 'integer', 'min:0'],
            'combustible' => ['required', 'in:gasolina,diesel,hibrido,electrico'],
            'ultima_revision_tecnica' => ['nullable', 'date'],
            'estado' => ['required', 'in:Activo,En Mantenimiento,Fuera de Servicio'],
            'propietario_nombre' => ['required', 'string', 'max:255'],
            'conductor_id' => ['nullable', 'exists:conductors,id'],
            'foto_base64' => ['nullable', 'string'],
        ], [
            'tipo.required' => 'El tipo de vehículo es obligatorio.',
            'marca.required' => 'La marca es obligatoria.',
            'modelo.required' => 'El modelo es obligatorio.',
            'anio_fabricacion.required' => 'El año de fabricación es obligatorio.',
            'placa.required' => 'La placa es obligatoria.',
            'placa.unique' => 'Ya existe un vehículo con esta placa.',
            'combustible.required' => 'El tipo de combustible es obligatorio.',
            'estado.required' => 'El estado es obligatorio.',
            'propietario_nombre.required' => 'El nombre del propietario es obligatorio.',
        ]);

        $validated['placa'] = Str::upper($validated['placa']);

        // Manejo de foto en base64
        if (isset($validated['foto_base64']) && ! empty($validated['foto_base64'])) {
            $validated['foto'] = $validated['foto_base64'];
            unset($validated['foto_base64']);
        }

        // Guardar conductor_id temporalmente si existe
        $conductorId = $validated['conductor_id'] ?? null;
        unset($validated['conductor_id']);

        $vehiculo = Vehicle::create($validated);

        // Si se asignó un conductor, crear el registro en conductor_vehicle
        if ($conductorId) {
            $conductor = Conductor::find($conductorId);
            if ($conductor) {
                $conductor->asignarVehiculo($vehiculo->id);
            }
            $vehiculo->update(['conductor_id' => $conductorId]);
        }

        return Response::structured([
            'success' => true,
            'message' => 'Vehículo creado exitosamente',
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
            'tipo' => $schema->string()->enum(['Bus', 'Camioneta', 'Taxi'])->description('Tipo de vehículo'),
            'marca' => $schema->string()->description('Marca del vehículo'),
            'modelo' => $schema->string()->description('Modelo del vehículo'),
            'anio_fabricacion' => $schema->integer()->description('Año de fabricación'),
            'placa' => $schema->string()->description('Número de placa (debe ser único)'),
            'chasis_vin' => $schema->string()->nullable()->description('Número de chasis o VIN'),
            'capacidad_pasajeros' => $schema->integer()->nullable()->description('Capacidad de pasajeros'),
            'capacidad_carga_kg' => $schema->integer()->nullable()->description('Capacidad de carga en kilogramos'),
            'combustible' => $schema->string()->enum(['gasolina', 'diesel', 'hibrido', 'electrico'])->description('Tipo de combustible'),
            'ultima_revision_tecnica' => $schema->string()->format('date')->nullable()->description('Fecha de última revisión técnica (formato: YYYY-MM-DD)'),
            'estado' => $schema->string()->enum(['Activo', 'En Mantenimiento', 'Fuera de Servicio'])->description('Estado del vehículo'),
            'propietario_nombre' => $schema->string()->description('Nombre del propietario del vehículo'),
            'conductor_id' => $schema->integer()->nullable()->description('ID del conductor a asignar (opcional)'),
            'foto_base64' => $schema->string()->nullable()->description('Foto del vehículo en formato base64 (data URI)'),
        ];
    }

    public function name(): string
    {
        return 'crear_vehiculo';
    }
}
