<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware personalizado para autenticación MCP
 *
 * Permite métodos de descubrimiento inicial (initialize, tools/list, prompts/list, resources/list)
 * y la herramienta `iniciar_sesion` sin autenticación.
 * Para todas las demás herramientas, requiere autenticación con Sanctum.
 */
class McpAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si es una request MCP (POST a /mcp/coopuertos)
        if ($request->isMethod('POST') && $request->is('mcp/coopuertos')) {
            // Intentar decodificar el body JSON-RPC
            $body = $request->getContent();
            $data = json_decode($body, true);

            // Métodos MCP permitidos sin autenticación (descubrimiento inicial)
            $allowedMethods = [
                'initialize',
                'tools/list',
                'prompts/list',
                'resources/list',
            ];

            // Verificar si es un método de descubrimiento permitido
            if (isset($data['method']) && in_array($data['method'], $allowedMethods, true)) {
                // Permitir acceso sin autenticación para métodos de descubrimiento
                return $next($request);
            }

            // Verificar si es una invocación de herramienta para `iniciar_sesion`
            if (
                isset($data['method']) &&
                $data['method'] === 'tools/call' &&
                isset($data['params']['name']) &&
                $data['params']['name'] === 'iniciar_sesion'
            ) {
                // Permitir acceso sin autenticación para la herramienta de login
                return $next($request);
            }
        }

        // Para todas las demás requests MCP, requerir autenticación con Sanctum
        // Intentar autenticar con Sanctum usando el token Bearer
        if ($token = $request->bearerToken()) {
            // Autenticar usando Sanctum
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;

            if ($user) {
                Auth::guard('sanctum')->setUser($user);
            } else {
                return response()->json([
                    'error' => [
                        'code' => 'INVALID_TOKEN',
                        'message' => 'El token proporcionado no es válido o ha expirado.',
                        'hint' => 'Usa la herramienta iniciar_sesion para obtener un nuevo token.',
                    ],
                ], 401);
            }
        } else {
            // No hay token Bearer
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Autenticación requerida. Por favor, usa la herramienta iniciar_sesion primero para obtener un token.',
                    'hint' => 'Usa la herramienta iniciar_sesion con email y password para obtener un token de acceso.',
                ],
            ], 401);
        }

        return $next($request);
    }
}
