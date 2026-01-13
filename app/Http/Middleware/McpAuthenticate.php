<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware personalizado para autenticación MCP
 *
 * Permite la herramienta `iniciar_sesion` sin autenticación.
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
        if (! $request->bearerToken() && ! Auth::guard('sanctum')->check()) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Autenticación requerida. Por favor, usa la herramienta iniciar_sesion primero para obtener un token.',
                    'hint' => 'Usa la herramienta iniciar_sesion con email y password para obtener un token de acceso.',
                ],
            ], 401);
        }

        // Intentar autenticar con Sanctum
        if (! Auth::guard('sanctum')->check()) {
            // Si hay un token Bearer, intentar autenticar
            if ($token = $request->bearerToken()) {
                // Sanctum manejará la autenticación automáticamente
                // Si falla, retornar error
                if (! Auth::guard('sanctum')->check()) {
                    return response()->json([
                        'error' => [
                            'code' => 'INVALID_TOKEN',
                            'message' => 'El token proporcionado no es válido o ha expirado.',
                            'hint' => 'Usa la herramienta iniciar_sesion para obtener un nuevo token.',
                        ],
                    ], 401);
                }
            }
        }

        return $next($request);
    }
}
