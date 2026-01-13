<?php

namespace Tests\Feature\Mcp\Prompts;

use App\Mcp\Prompts\PromptGenerarReporte;
use App\Mcp\Servers\CoopuertosServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptGenerarReporteTest extends TestCase
{
    use RefreshDatabase;

    public function test_prompt_returns_system_and_user_messages(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->prompt(PromptGenerarReporte::class, [
            'tipo' => 'conductores',
        ]);

        $response->assertOk();
        $response->assertSee('reporte');
    }

    public function test_prompt_accepts_tipo_parameter(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->prompt(PromptGenerarReporte::class, [
            'tipo' => 'vehiculos',
        ]);

        $response->assertOk();
    }

    public function test_prompt_accepts_filtros_parameter(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->prompt(PromptGenerarReporte::class, [
            'tipo' => 'conductores',
            'filtros' => ['estado' => 'activo'],
        ]);

        $response->assertOk();
    }
}
