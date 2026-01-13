<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\CoopuertosServer;
use App\Mcp\Tools\IniciarSesion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IniciarSesionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tool_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = CoopuertosServer::tool(IniciarSesion::class, [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertSee('token');
        $response->assertSee('success');
    }

    public function test_tool_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = CoopuertosServer::tool(IniciarSesion::class, [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertHasErrors();
    }

    public function test_tool_validates_required_fields(): void
    {
        $response = CoopuertosServer::tool(IniciarSesion::class, []);

        $response->assertHasErrors();
    }

    public function test_tool_validates_email_format(): void
    {
        $response = CoopuertosServer::tool(IniciarSesion::class, [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertHasErrors();
    }

    public function test_tool_returns_token_on_successful_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = CoopuertosServer::tool(IniciarSesion::class, [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertSee('token');
        $response->assertSee('success');
    }
}
