<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\CoopuertosServer;
use App\Mcp\Tools\CrearPropietario;
use App\Mcp\Tools\CrearVehiculo;
use App\Mcp\Tools\EditarConductor;
use App\Mcp\Tools\EliminarConductor;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HerramientasCRUDTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        Permission::firstOrCreate(['name' => 'crear vehiculos', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crear propietarios', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'editar conductores', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'eliminar conductores', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(['crear vehiculos', 'crear propietarios', 'editar conductores', 'eliminar conductores']);
    }

    public function test_crear_vehiculo_requires_permission(): void
    {
        $user = User::factory()->create();
        // No asignar permiso

        $response = CoopuertosServer::actingAs($user)->tool(CrearVehiculo::class, [
            'placa' => 'ABC123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'tipo' => 'Sedán',
            'estado' => 'activo',
        ]);

        $response->assertHasErrors();
    }

    public function test_crear_propietario_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = CoopuertosServer::actingAs($user)->tool(CrearPropietario::class, [
            'nombre_completo' => 'María González',
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('propietarios', [
            'numero_identificacion' => '1234567890',
        ]);
    }

    public function test_editar_conductor_requires_permission(): void
    {
        $user = User::factory()->create();
        // No asignar permiso

        $conductor = Conductor::factory()->create();

        $response = CoopuertosServer::actingAs($user)->tool(EditarConductor::class, [
            'id' => $conductor->id,
            'celular' => '3001234567',
        ]);

        $response->assertHasErrors();
    }

    public function test_eliminar_conductor_requires_permission(): void
    {
        $user = User::factory()->create();
        // No asignar permiso

        $conductor = Conductor::factory()->create();

        $response = CoopuertosServer::actingAs($user)->tool(EliminarConductor::class, [
            'id' => $conductor->id,
        ]);

        $response->assertHasErrors();
    }
}
