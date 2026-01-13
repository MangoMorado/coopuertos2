<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Controlador API para estado de salud del sistema
 *
 * Proporciona endpoints públicos para verificar el estado de salud del sistema
 * incluyendo base de datos, colas, almacenamiento, extensiones PHP y versiones.
 * Este endpoint es público y no requiere autenticación.
 */
#[OA\Tag(name: 'Health', description: 'Estado de salud del sistema')]
class HealthController extends Controller
{
    /**
     * Obtiene el estado de salud completo del sistema
     *
     * Retorna información detallada sobre el estado de salud del sistema:
     * - Base de datos: estado de conexión
     * - Colas: trabajos pendientes y fallidos
     * - Almacenamiento: espacio total, usado, libre y porcentaje
     * - Extensiones PHP: estado de cada extensión requerida
     * - Versiones: PHP y Laravel
     *
     * @param  HealthCheckService  $healthCheckService  Servicio de verificación de salud
     * @return JsonResponse Respuesta JSON con el estado de salud completo
     */
    #[OA\Get(
        path: '/api/v1/health',
        summary: 'Obtener estado de salud del sistema',
        description: 'Retorna información detallada sobre el estado de salud del sistema: base de datos, colas, almacenamiento, extensiones PHP y versiones. Este endpoint es público y no requiere autenticación.',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estado de salud obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'database',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'status', type: 'string', example: 'healthy'),
                                        new OA\Property(property: 'message', type: 'string', example: 'Conexión establecida'),
                                        new OA\Property(property: 'connection', type: 'string', example: 'mysql'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'queue',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'status', type: 'string', example: 'healthy'),
                                        new OA\Property(property: 'pending', type: 'integer', example: 0),
                                        new OA\Property(property: 'failed', type: 'integer', example: 0),
                                        new OA\Property(property: 'connection', type: 'string', example: 'database'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'storage',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'status', type: 'string', example: 'healthy'),
                                        new OA\Property(property: 'total', type: 'string', example: '500.00 GB'),
                                        new OA\Property(property: 'used', type: 'string', example: '250.50 GB'),
                                        new OA\Property(property: 'free', type: 'string', example: '249.50 GB'),
                                        new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 50.1),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'versions',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'php', type: 'string', example: '8.2.12'),
                                        new OA\Property(property: 'laravel', type: 'string', example: '12.0.0'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'php_extensions',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'status', type: 'string', example: 'healthy'),
                                        new OA\Property(
                                            property: 'extensions',
                                            type: 'object',
                                            description: 'Estado de cada extensión PHP requerida'
                                        ),
                                    ]
                                ),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'Estado de salud obtenido exitosamente'),
                    ]
                )
            ),
        ]
    )]
    public function index(HealthCheckService $healthCheckService): JsonResponse
    {
        $healthStatus = $healthCheckService->getHealthStatus();

        return response()->json([
            'success' => true,
            'data' => $healthStatus,
            'message' => 'Estado de salud obtenido exitosamente',
        ]);
    }
}
