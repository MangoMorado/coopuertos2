<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePropietarioRequest;
use App\Http\Requests\Api\UpdatePropietarioRequest;
use App\Http\Resources\Api\PropietarioResource;
use App\Models\Propietario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Propietarios', description: 'Gestión de propietarios')]
class PropietarioController extends Controller
{
    #[OA\Get(
        path: '/api/v1/propietarios',
        summary: 'Listar propietarios',
        description: 'Retorna una lista paginada de propietarios con filtros opcionales',
        tags: ['Propietarios'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Término de búsqueda', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items por página', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de propietarios'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Propietario::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre_completo', 'like', '%'.$search.'%')
                    ->orWhere('numero_identificacion', 'like', '%'.$search.'%')
                    ->orWhere('telefono_contacto', 'like', '%'.$search.'%')
                    ->orWhere('correo_electronico', 'like', '%'.$search.'%');
            });
        }

        $perPage = $request->get('per_page', 15);
        $propietarios = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PropietarioResource::collection($propietarios),
            'meta' => [
                'current_page' => $propietarios->currentPage(),
                'per_page' => $propietarios->perPage(),
                'total' => $propietarios->total(),
                'last_page' => $propietarios->lastPage(),
            ],
            'links' => [
                'first' => $propietarios->url(1),
                'last' => $propietarios->url($propietarios->lastPage()),
                'prev' => $propietarios->previousPageUrl(),
                'next' => $propietarios->nextPageUrl(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/propietarios',
        summary: 'Crear propietario',
        description: 'Crea un nuevo propietario',
        tags: ['Propietarios'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Propietario creado exitosamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function store(StorePropietarioRequest $request): JsonResponse
    {
        $propietario = Propietario::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => new PropietarioResource($propietario),
            'message' => 'Propietario creado exitosamente',
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/propietarios/{id}',
        summary: 'Mostrar propietario',
        description: 'Retorna la información de un propietario específico',
        tags: ['Propietarios'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del propietario', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Información del propietario'),
            new OA\Response(response: 404, description: 'Propietario no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function show(Propietario $propietario): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new PropietarioResource($propietario),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/propietarios/{id}',
        summary: 'Actualizar propietario',
        description: 'Actualiza la información de un propietario',
        tags: ['Propietarios'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del propietario', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Propietario actualizado exitosamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 404, description: 'Propietario no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function update(UpdatePropietarioRequest $request, Propietario $propietario): JsonResponse
    {
        $propietario->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new PropietarioResource($propietario),
            'message' => 'Propietario actualizado exitosamente',
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/propietarios/{id}',
        summary: 'Eliminar propietario',
        description: 'Elimina un propietario del sistema',
        tags: ['Propietarios'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID del propietario', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Propietario eliminado exitosamente'),
            new OA\Response(response: 404, description: 'Propietario no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function destroy(Propietario $propietario): JsonResponse
    {
        $propietario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Propietario eliminado exitosamente',
        ]);
    }

    #[OA\Get(
        path: '/api/v1/propietarios/search',
        summary: 'Buscar propietarios',
        description: 'Busca propietarios por nombre completo o número de identificación',
        tags: ['Propietarios'],
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

        $propietarios = Propietario::where(function ($q) use ($query) {
            $q->where('nombre_completo', 'like', "%{$query}%")
                ->orWhere('numero_identificacion', 'like', "%{$query}%");
        })->limit(10)->get();

        return response()->json([
            'success' => true,
            'data' => PropietarioResource::collection($propietarios),
        ]);
    }
}
