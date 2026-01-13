<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\CoopuertosServer;
use App\Mcp\Tools\GenerarCarnet;
use App\Mcp\Tools\ObtenerPlantillaActiva;
use App\Mcp\Tools\PersonalizarPlantilla;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HerramientasCarnetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        Permission::firstOrCreate(['name' => 'crear carnets', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'editar carnets', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(['crear carnets', 'editar carnets']);
    }

    public function test_obtener_plantilla_activa_returns_template_when_exists(): void
    {
        $user = User::factory()->create();

        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla Test',
            'activo' => true,
            'variables_config' => [],
        ]);

        $response = CoopuertosServer::actingAs($user)->tool(ObtenerPlantillaActiva::class);

        $response->assertOk();
        $response->assertSee('Plantilla Test');
    }

    public function test_obtener_plantilla_activa_returns_error_when_no_template(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->tool(ObtenerPlantillaActiva::class);

        $response->assertHasErrors();
    }

    public function test_personalizar_plantilla_requires_permission(): void
    {
        $user = User::factory()->create();
        // No asignar permiso

        $response = CoopuertosServer::actingAs($user)->tool(PersonalizarPlantilla::class, [
            'nombre_sistema' => 'Test',
        ]);

        $response->assertHasErrors();
    }

    public function test_personalizar_plantilla_can_update_template(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        CarnetTemplate::create([
            'nombre' => 'Plantilla Original',
            'activo' => true,
            'variables_config' => [],
        ]);

        $variablesConfig = json_encode([
            'nombre_sistema' => 'Nuevo Nombre',
            'color_principal' => '#FF0000',
        ]);

        $response = CoopuertosServer::actingAs($user)->tool(PersonalizarPlantilla::class, [
            'variables_config' => $variablesConfig,
        ]);

        $response->assertOk();

        // Buscar la nueva plantilla activa
        $nuevaTemplate = CarnetTemplate::where('activo', true)->first();
        $this->assertNotNull($nuevaTemplate);
        $this->assertEquals('Nuevo Nombre', $nuevaTemplate->variables_config['nombre_sistema'] ?? null);
    }

    public function test_generar_carnet_requires_permission(): void
    {
        $user = User::factory()->create();
        // No asignar permiso

        $conductor = Conductor::factory()->create();

        $response = CoopuertosServer::actingAs($user)->tool(GenerarCarnet::class, [
            'conductor_id' => $conductor->id,
        ]);

        $response->assertHasErrors();
    }

    public function test_generar_carnet_requires_active_template(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create();

        $response = CoopuertosServer::actingAs($user)->tool(GenerarCarnet::class, [
            'conductor_id' => $conductor->id,
        ]);

        $response->assertHasErrors();
    }
}
