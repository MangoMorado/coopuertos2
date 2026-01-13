<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\CoopuertosServer;
use App\Mcp\Tools\IniciarSesion;
use App\Mcp\Tools\ObtenerEstadisticas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoopuertosServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_can_call_tool(): void
    {
        $response = CoopuertosServer::tool(ObtenerEstadisticas::class);

        $response->assertOk();
    }

    public function test_server_tool_has_correct_name(): void
    {
        $response = CoopuertosServer::tool(IniciarSesion::class, [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertName('iniciar_sesion');
    }

    public function test_server_tool_has_description(): void
    {
        $response = CoopuertosServer::tool(IniciarSesion::class, [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertDescription('Inicia sesión en el sistema Coopuertos proporcionando email y contraseña. Retorna un token de acceso que debe ser guardado y usado en el header Authorization: Bearer <token> para todas las consultas posteriores.');
    }
}
