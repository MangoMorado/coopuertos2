<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreConductorRequest;
use App\Http\Requests\Api\UpdateConductorRequest;
use App\Http\Resources\Api\ConductorResource;
use App\Models\Conductor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Conductores', description: 'Gestión de conductores')]
class ConductorController extends Controller
{
    #[OA\Get(
        path: '/api/v1/conductores',
        summary: 'Listar conductores',
        description: 'Retorna una lista paginada de conductores con filtros opcionales',
        tags: ['Conductores'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Término de búsqueda', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items por página', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de conductores'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Conductor::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cedula', 'like', '%'.$search.'%')
                    ->orWhere('nombres', 'like', '%'.$search.'%')
                    ->orWhere('apellidos', 'like', '%'.$search.'%')
                    ->orWhere('numero_interno', 'like', '%'.$search.'%')
                    ->orWhere('celular', 'like', '%'.$search.'%')
                    ->orWhere('correo', 'like', '%'.$search.'%');
            });
        }

        $perPage = $request->get('per_page', 15);
        $conductores = $query->with(['asignacionActiva.vehicle'])->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ConductorResource::collection($conductores),
            'meta' => [
                'current_page' => $conductores->currentPage(),
                'per_page' => $conductores->perPage(),
                'total' => $conductores->total(),
                'last_page' => $conductores->lastPage(),
            ],
            'links' => [
                'first' => $conductores->url(1),
                'last' => $conductores->url($conductores->lastPage()),
                'prev' => $conductores->previousPageUrl(),
                'next' => $conductores->nextPageUrl(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/conductores',
        summary: 'Crear conductor',
        description: 'Crea un nuevo conductor',
        tags: ['Conductores'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Conductor creado exitosamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function store(StoreConductorRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['correo'])) {
            $data['correo'] = 'No tiene';
        }

        $conductor = Conductor::create($data);

        return response()->json([
            'success' => true,
            'data' => new ConductorResource($conductor->load('asignacionActiva.vehicle')),
            'message' => 'Conductor creado exitosamente',
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/conductores/{id}',
        summary: 'Mostrar conductor',
        description: 'Retorna la información de un conductor específico',
        tags: ['Conductores'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del conductor', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Información del conductor'),
            new OA\Response(response: 404, description: 'Conductor no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function show(Conductor $conductor): JsonResponse
    {
        $conductor->load('asignacionActiva.vehicle');

        return response()->json([
            'success' => true,
            'data' => new ConductorResource($conductor),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/conductores/{id}',
        summary: 'Actualizar conductor',
        description: 'Actualiza la información de un conductor',
        tags: ['Conductores'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del conductor', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Conductor actualizado exitosamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 404, description: 'Conductor no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function update(UpdateConductorRequest $request, Conductor $conductor): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['correo'])) {
            $data['correo'] = 'No tiene';
        }

        $conductor->update($data);
        $conductor->load('asignacionActiva.vehicle');

        return response()->json([
            'success' => true,
            'data' => new ConductorResource($conductor),
            'message' => 'Conductor actualizado exitosamente',
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/conductores/{id}',
        summary: 'Eliminar conductor',
        description: 'Elimina un conductor del sistema',
        tags: ['Conductores'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del conductor', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Conductor eliminado exitosamente'),
            new OA\Response(response: 404, description: 'Conductor no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function destroy(Conductor $conductor): JsonResponse
    {
        $conductor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conductor eliminado exitosamente',
        ]);
    }

    #[OA\Get(
        path: '/api/v1/conductores/search',
        summary: 'Buscar conductores',
        description: 'Busca conductores por cédula, nombres, apellidos o número interno',
        tags: ['Conductores'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, description: 'Término de búsqueda', schema: new OA\Schema(type: 'string', minLength: 2)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Resultados de búsqueda'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $conductores = Conductor::where(function ($q) use ($query) {
            $q->where('cedula', 'like', "%{$query}%")
                ->orWhere('nombres', 'like', "%{$query}%")
                ->orWhere('apellidos', 'like', "%{$query}%")
                ->orWhere('numero_interno', 'like', "%{$query}%");
        })->limit(10)->get();

        return response()->json([
            'success' => true,
            'data' => ConductorResource::collection($conductores),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/conductores/{uuid}/public',
        summary: 'Mostrar conductor público',
        description: 'Retorna la información pública de un conductor por UUID (sin autenticación)',
        tags: ['Conductores'],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true, description: 'UUID del conductor', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Información del conductor'),
            new OA\Response(response: 404, description: 'Conductor no encontrado'),
        ]
    )]
    public function publicShow(string $uuid): JsonResponse
    {
        $conductor = Conductor::where('uuid', $uuid)
            ->with(['asignacionActiva.vehicle'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new ConductorResource($conductor),
        ]);
    }
}
