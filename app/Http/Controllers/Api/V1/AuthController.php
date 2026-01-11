<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Autenticación', description: 'Endpoints de autenticación con Laravel Sanctum')]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Autenticar usuario',
        description: 'Autentica un usuario con email y password, retorna un token de acceso',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'usuario@ejemplo.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Autenticación exitosa',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Autenticación exitosa'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas no son correctas.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token', ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'message' => 'Autenticación exitosa',
        ]);
    }

    #[OA\Get(
        path: '/api/v1/auth/user',
        summary: 'Obtener usuario autenticado',
        description: 'Retorna la información del usuario autenticado',
        tags: ['Autenticación'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Usuario obtenido exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()),
            'message' => 'Usuario obtenido exitosamente',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Cerrar sesión',
        description: 'Revoca el token de acceso del usuario autenticado',
        tags: ['Autenticación'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }
}
