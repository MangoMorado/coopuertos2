<?php

namespace Tests\Feature\Mcp\Resources;

use App\Mcp\Resources\DocumentacionProyecto;
use App\Mcp\Resources\EjemplosUsoHerramientasMcp;
use App\Mcp\Resources\GuiaIntegracionMcp;
use App\Mcp\Resources\RoadmapProyecto;
use App\Mcp\Servers\CoopuertosServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodosLosRecursosTest extends TestCase
{
    use RefreshDatabase;

    public function test_documentacion_proyecto_returns_readme(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->resource(DocumentacionProyecto::class);

        $response->assertOk();
        $response->assertSee('Coopuertos');
    }

    public function test_roadmap_proyecto_returns_roadmap(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->resource(RoadmapProyecto::class);

        $response->assertOk();
        $response->assertSee('Roadmap');
    }

    public function test_guia_integracion_returns_integration_guide(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->resource(GuiaIntegracionMcp::class);

        $response->assertOk();
        $response->assertSee('Guía');
        $response->assertSee('cliente');
    }

    public function test_ejemplos_uso_returns_examples(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->resource(EjemplosUsoHerramientasMcp::class);

        $response->assertOk();
        $response->assertSee('ejemplos');
    }
}
