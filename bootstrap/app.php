<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Excluir ruta MCP del CSRF para permitir peticiones desde el controlador de prueba
        $middleware->validateCsrfTokens(except: [
            'mcp/coopuertos',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Las traducciones de Spatie Permission se manejan automáticamente
        // a través del archivo lang/es/permission.php
    })->create();
