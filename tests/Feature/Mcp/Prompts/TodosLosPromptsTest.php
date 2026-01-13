<?php

namespace Tests\Feature\Mcp\Prompts;

use App\Mcp\Prompts\PromptConfigurarPermisos;
use App\Mcp\Prompts\PromptImportarConductores;
use App\Mcp\Prompts\PromptTroubleshooting;
use App\Mcp\Prompts\TutorialInteractivoApp;
use App\Mcp\Servers\CoopuertosServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodosLosPromptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prompt_importar_conductores_returns_guide(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->prompt(PromptImportarConductores::class);

        $response->assertOk();
        $response->assertSee('importación');
    }

    public function test_prompt_configurar_permisos_returns_guide(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->prompt(PromptConfigurarPermisos::class);

        $response->assertOk();
        $response->assertSee('permisos');
    }

    public function test_prompt_troubleshooting_returns_guide(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->prompt(PromptTroubleshooting::class);

        $response->assertOk();
        $response->assertSee('problemas');
    }

    public function test_prompt_tutorial_interactivo_returns_guide(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->prompt(TutorialInteractivoApp::class);

        $response->assertOk();
        $response->assertSee('tutorial');
    }
}
