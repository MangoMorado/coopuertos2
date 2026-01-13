<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\CoopuertosServer;
use App\Mcp\Tools\BuscarPropietario;
use App\Mcp\Tools\BuscarVehiculo;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HerramientasBusquedaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        Permission::firstOrCreate(['name' => 'ver vehiculos', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ver propietarios', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(['ver vehiculos', 'ver propietarios']);
    }

    public function test_buscar_vehiculo_by_placa(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Vehicle::factory()->create(['placa' => 'ABC123']);

        $response = CoopuertosServer::actingAs($user)->tool(BuscarVehiculo::class, [
            'query' => 'ABC123',
        ]);

        $response->assertOk();
        $response->assertSee('ABC123');
    }

    public function test_buscar_propietario_by_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Propietario::factory()->create(['nombre_completo' => 'Juan Pérez']);

        $response = CoopuertosServer::actingAs($user)->tool(BuscarPropietario::class, [
            'query' => 'Juan',
        ]);

        $response->assertOk();
        $response->assertSee('Juan');
    }
}
