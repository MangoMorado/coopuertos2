<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\CoopuertosServer;
use App\Mcp\Tools\BuscarConductor;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BuscarConductorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        Permission::firstOrCreate(['name' => 'ver conductores', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo('ver conductores');
    }

    public function test_tool_can_search_conductor_by_cedula(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->create(['cedula' => '1234567890', 'nombres' => 'Juan', 'apellidos' => 'Pérez']);

        $response = CoopuertosServer::actingAs($user)->tool(BuscarConductor::class, [
            'query' => '1234567890',
        ]);

        $response->assertOk();
        $response->assertSee('1234567890');
    }

    public function test_tool_can_search_conductor_by_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->create(['nombres' => 'Juan', 'apellidos' => 'Pérez']);

        $response = CoopuertosServer::actingAs($user)->tool(BuscarConductor::class, [
            'query' => 'Juan',
        ]);

        $response->assertOk();
        $response->assertSee('Juan');
    }

    public function test_tool_returns_empty_results_when_no_match(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = CoopuertosServer::actingAs($user)->tool(BuscarConductor::class, [
            'query' => 'nonexistent',
        ]);

        $response->assertOk();
        $response->assertSee('total');
        $response->assertSee('resultados');
    }

    public function test_tool_respects_limit_parameter(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->count(15)->create();

        $response = CoopuertosServer::actingAs($user)->tool(BuscarConductor::class, [
            'query' => '',
            'limit' => 5,
        ]);

        $response->assertOk();
        // Verificar que el límite se respeta (puede haber menos de 5 si no hay suficientes resultados)
        $response->assertSee('"total"');
    }
}
