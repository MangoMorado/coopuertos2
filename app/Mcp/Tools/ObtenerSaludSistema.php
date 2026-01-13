<?php

namespace App\Mcp\Tools;

use App\Services\HealthCheckService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para obtener el estado de salud del sistema
 */
class ObtenerSaludSistema extends Tool
{
    protected string $description = 'Obtiene el estado completo de salud del sistema incluyendo base de datos, colas, almacenamiento, versiones de PHP y Laravel, y extensiones PHP requeridas.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos - solo usuarios con acceso a configuración
        if (! Auth::user()->hasPermissionTo('ver configuracion')) {
            return Response::error(
                'No tienes permisos para ver el estado de salud del sistema.',
                [
                    'code' => 'PERMISSION_DENIED',
                    'hint' => 'Se requiere el permiso "ver configuracion" para acceder a esta información.',
                ]
            );
        }

        $healthCheckService = new HealthCheckService;
        $healthStatus = $healthCheckService->getHealthStatus();

        return Response::structured([
            'database' => $healthStatus['database'] ?? null,
            'queue' => $healthStatus['queue'] ?? null,
            'storage' => $healthStatus['storage'] ?? null,
            'versions' => $healthStatus['versions'] ?? null,
            'php_extensions' => $healthStatus['php_extensions'] ?? null,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function name(): string
    {
        return 'obtener_salud_sistema';
    }
}
