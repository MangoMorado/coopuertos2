<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que permite la herramienta MCP de login sin autenticación
 *
 * Verifica si la request MCP es para la herramienta `iniciar_sesion` y
 * permite el acceso sin autenticación. Para todas las demás herramientas,
 * requiere autenticación con Sanctum.
 */
class AllowMcpLoginTool
{
    /**
     * Handle an incoming request.
     *
     * Este middleware se ejecuta ANTES de auth:sanctum.
     * Si detecta que es la herramienta de login, permite el acceso sin autenticación.
     * Para todas las demás herramientas, auth:sanctum se ejecutará después y requerirá autenticación.
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
                // Marcar la request para que auth:sanctum la ignore
                // Esto se hace agregando un atributo a la request
                $request->attributes->set('mcp_allow_public', true);

                // Continuar sin requerir autenticación
                return $next($request);
            }
        }

        // Para todas las demás requests, continuar normalmente
        // auth:sanctum se ejecutará después y requerirá autenticación
        return $next($request);
    }
}
