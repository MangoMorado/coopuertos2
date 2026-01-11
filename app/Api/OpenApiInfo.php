<?php

namespace App\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API Coopuertos',
    description: 'API REST para el sistema Coopuertos - Gestión de conductores, vehículos y propietarios'
)]
#[OA\Server(
    url: '/api/v1',
    description: 'Servidor API v1'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token de autenticación de Laravel Sanctum'
)]
class OpenApiInfo {}
