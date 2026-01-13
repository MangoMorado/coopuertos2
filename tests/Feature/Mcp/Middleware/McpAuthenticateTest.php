<?php

namespace Tests\Feature\Mcp\Middleware;

use App\Http\Middleware\McpAuthenticate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class McpAuthenticateTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_allows_iniciar_sesion_tool_without_auth(): void
    {
        $middleware = new McpAuthenticate;
        $request = Request::create('/mcp/coopuertos', 'POST', [], [], [], [], json_encode([
            'method' => 'tools/call',
            'params' => [
                'name' => 'iniciar_sesion',
                'arguments' => [],
            ],
        ]));

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['allowed' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['allowed'] ?? false);
    }

    public function test_middleware_blocks_other_tools_without_auth(): void
    {
        $middleware = new McpAuthenticate;
        $request = Request::create('/mcp/coopuertos', 'POST', [], [], [], [], json_encode([
            'method' => 'tools/call',
            'params' => [
                'name' => 'buscar_conductor',
                'arguments' => [],
            ],
        ]));

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['allowed' => true]);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_middleware_allows_authenticated_requests(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $middleware = new McpAuthenticate;
        $request = Request::create('/mcp/coopuertos', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$user->createToken('test')->plainTextToken,
        ], json_encode([
            'method' => 'tools/call',
            'params' => [
                'name' => 'buscar_conductor',
                'arguments' => [],
            ],
        ]));

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['allowed' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
