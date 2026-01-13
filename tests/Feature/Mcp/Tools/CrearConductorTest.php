<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\CoopuertosServer;
use App\Mcp\Tools\CrearConductor;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrearConductorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        Permission::firstOrCreate(['name' => 'crear conductores', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo('crear conductores');
    }

    public function test_tool_can_create_conductor_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = CoopuertosServer::actingAs($user)->tool(CrearConductor::class, [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('conductors', [
            'cedula' => '1234567890',
            'nombres' => 'Juan',
        ]);
    }

    public function test_tool_requires_permission_to_create(): void
    {
        $user = User::factory()->create();
        // No asignar permiso

        $response = CoopuertosServer::actingAs($user)->tool(CrearConductor::class, [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ]);

        $response->assertHasErrors();
    }

    public function test_tool_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = CoopuertosServer::actingAs($user)->tool(CrearConductor::class, []);

        $response->assertHasErrors();
    }

    public function test_tool_validates_unique_cedula(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->create(['cedula' => '1234567890']);

        $response = CoopuertosServer::actingAs($user)->tool(CrearConductor::class, [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890', // Duplicado
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ]);

        $response->assertHasErrors();
    }
}
