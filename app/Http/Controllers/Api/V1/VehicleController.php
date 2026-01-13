<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreVehicleRequest;
use App\Http\Requests\Api\UpdateVehicleRequest;
use App\Http\Resources\Api\VehicleResource;
use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestión de vehículos
 *
 * Proporciona endpoints RESTful para CRUD de vehículos y búsqueda.
 * Maneja la asignación/desasignación de conductores a vehículos.
 * Todos los endpoints requieren autenticación mediante Laravel Sanctum.
 */
#[OA\Tag(name: 'Vehículos', description: 'Gestión de vehículos')]
class VehicleController extends Controller
{
    /**
     * Lista vehículos con paginación y filtros opcionales
     *
     * Retorna una lista paginada de vehículos con búsqueda opcional por
     * placa, marca, modelo o nombre del propietario.
     *
     * @param  Request  $request  Request HTTP con parámetros de búsqueda y paginación
     * @return JsonResponse Respuesta JSON con lista paginada de vehículos
     */
    #[OA\Get(
        path: '/api/v1/vehiculos',
        summary: 'Listar vehículos',
        description: 'Retorna una lista paginada de vehículos con filtros opcionales',
        tags: ['Vehículos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Término de búsqueda', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items por página', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de vehículos'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('placa', 'like', '%'.$search.'%')
                    ->orWhere('marca', 'like', '%'.$search.'%')
                    ->orWhere('modelo', 'like', '%'.$search.'%')
                    ->orWhere('propietario_nombre', 'like', '%'.$search.'%');
            });
        }

        $perPage = $request->get('per_page', 15);
        $vehiculos = $query->with(['conductor'])->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => VehicleResource::collection($vehiculos),
            'meta' => [
                'current_page' => $vehiculos->currentPage(),
                'per_page' => $vehiculos->perPage(),
                'total' => $vehiculos->total(),
                'last_page' => $vehiculos->lastPage(),
            ],
            'links' => [
                'first' => $vehiculos->url(1),
                'last' => $vehiculos->url($vehiculos->lastPage()),
                'prev' => $vehiculos->previousPageUrl(),
                'next' => $vehiculos->nextPageUrl(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/vehiculos',
        summary: 'Crear vehículo',
        description: 'Crea un nuevo vehículo',
        tags: ['Vehículos'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Vehículo creado exitosamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    /**
     * Crea un nuevo vehículo
     *
     * Crea un vehículo con los datos validados. La placa se convierte a mayúsculas.
     * Si se proporciona conductor_id, se asigna automáticamente al vehículo.
     *
     * @param  StoreVehicleRequest  $request  Request con datos validados del vehículo
     * @return JsonResponse Respuesta JSON con el vehículo creado (HTTP 201)
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['placa'] = Str::upper($data['placa']);

        $conductorId = $data['conductor_id'] ?? null;
        unset($data['conductor_id']);

        $vehiculo = Vehicle::create($data);

        if ($conductorId) {
            $conductor = Conductor::find($conductorId);
            if ($conductor) {
                $conductor->asignarVehiculo($vehiculo->id);
            }
            $vehiculo->update(['conductor_id' => $conductorId]);
        }

        return response()->json([
            'success' => true,
            'data' => new VehicleResource($vehiculo->load('conductor')),
            'message' => 'Vehículo creado exitosamente',
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/vehiculos/{id}',
        summary: 'Mostrar vehículo',
        description: 'Retorna la información de un vehículo específico',
        tags: ['Vehículos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del vehículo', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Información del vehículo'),
            new OA\Response(response: 404, description: 'Vehículo no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    /**
     * Muestra la información de un vehículo específico
     *
     * Retorna los datos completos de un vehículo incluyendo su conductor asignado.
     *
     * @param  Vehicle  $vehicle  Vehículo obtenido mediante route model binding
     * @return JsonResponse Respuesta JSON con los datos del vehículo
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        $vehicle->load('conductor');

        return response()->json([
            'success' => true,
            'data' => new VehicleResource($vehicle),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/vehiculos/{id}',
        summary: 'Actualizar vehículo',
        description: 'Actualiza la información de un vehículo',
        tags: ['Vehículos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del vehículo', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Vehículo actualizado exitosamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 404, description: 'Vehículo no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    /**
     * Actualiza la información de un vehículo
     *
     * Actualiza los datos del vehículo. Si se cambia el conductor_id,
     * desactiva la asignación anterior y crea/actualiza la nueva asignación
     * en ConductorVehicle, asegurando que un conductor solo tenga un vehículo activo.
     * La placa se convierte a mayúsculas.
     *
     * @param  UpdateVehicleRequest  $request  Request con datos validados del vehículo
     * @param  Vehicle  $vehicle  Vehículo a actualizar obtenido mediante route model binding
     * @return JsonResponse Respuesta JSON con el vehículo actualizado
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $data = $request->validated();
        $data['placa'] = Str::upper($data['placa']);

        $nuevoConductorId = $data['conductor_id'] ?? null;
        $conductorIdAnterior = $vehicle->conductor_id;
        unset($data['conductor_id']);

        $vehicle->update($data);

        if ($nuevoConductorId != $conductorIdAnterior) {
            if ($conductorIdAnterior) {
                ConductorVehicle::where('conductor_id', $conductorIdAnterior)
                    ->where('vehicle_id', $vehicle->id)
                    ->where('estado', 'activo')
                    ->update([
                        'estado' => 'inactivo',
                        'fecha_desasignacion' => now(),
                    ]);
            }

            if ($nuevoConductorId) {
                $nuevoConductor = Conductor::find($nuevoConductorId);
                if ($nuevoConductor) {
                    ConductorVehicle::where('conductor_id', $nuevoConductorId)
                        ->where('estado', 'activo')
                        ->where('vehicle_id', '!=', $vehicle->id)
                        ->update([
                            'estado' => 'inactivo',
                            'fecha_desasignacion' => now(),
                        ]);

                    ConductorVehicle::updateOrCreate(
                        [
                            'conductor_id' => $nuevoConductorId,
                            'vehicle_id' => $vehicle->id,
                        ],
                        [
                            'estado' => 'activo',
                            'fecha_asignacion' => now(),
                            'fecha_desasignacion' => null,
                        ]
                    );
                }
            }

            $vehicle->update(['conductor_id' => $nuevoConductorId]);
        }

        return response()->json([
            'success' => true,
            'data' => new VehicleResource($vehicle->load('conductor')),
            'message' => 'Vehículo actualizado exitosamente',
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/vehiculos/{id}',
        summary: 'Eliminar vehículo',
        description: 'Elimina un vehículo del sistema',
        tags: ['Vehículos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del vehículo', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Vehículo eliminado exitosamente'),
            new OA\Response(response: 404, description: 'Vehículo no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    /**
     * Elimina un vehículo del sistema
     *
     * Elimina permanentemente el vehículo y todas sus relaciones asociadas.
     *
     * @param  Vehicle  $vehicle  Vehículo a eliminar obtenido mediante route model binding
     * @return JsonResponse Respuesta JSON confirmando la eliminación
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehículo eliminado exitosamente',
        ]);
    }

    #[OA\Get(
        path: '/api/v1/vehiculos/search',
        summary: 'Buscar vehículos',
        description: 'Busca vehículos por placa, marca o modelo',
        tags: ['Vehículos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, description: 'Término de búsqueda', schema: new OA\Schema(type: 'string', minLength: 2)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Resultados de búsqueda'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    /**
     * Busca vehículos por término de búsqueda
     *
     * Realiza una búsqueda rápida de vehículos por placa, marca o modelo.
     * Retorna máximo 10 resultados. Requiere mínimo 2 caracteres en el término de búsqueda.
     *
     * @param  Request  $request  Request HTTP con parámetro 'q' (término de búsqueda)
     * @return JsonResponse Respuesta JSON con lista de vehículos encontrados (máximo 10)
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $vehiculos = Vehicle::with(['conductor'])
            ->where(function ($q) use ($query) {
                $q->where('placa', 'like', "%{$query}%")
                    ->orWhere('marca', 'like', "%{$query}%")
                    ->orWhere('modelo', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => VehicleResource::collection($vehiculos),
        ]);
    }
}
