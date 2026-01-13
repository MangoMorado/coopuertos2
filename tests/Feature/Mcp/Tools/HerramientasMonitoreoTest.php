<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\CoopuertosServer;
use App\Mcp\Tools\ObtenerEstadisticas;
use App\Mcp\Tools\ObtenerMetricasColas;
use App\Mcp\Tools\ObtenerSaludSistema;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HerramientasMonitoreoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        Permission::firstOrCreate(['name' => 'ver configuracion', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo('ver configuracion');
    }

    public function test_obtener_estadisticas_returns_system_statistics(): void
    {
        $user = User::factory()->create();

        Conductor::factory()->count(5)->create();

        $response = CoopuertosServer::actingAs($user)->tool(ObtenerEstadisticas::class);

        $response->assertOk();
        $response->assertSee('conductores');
        $response->assertSee('vehiculos');
    }

    public function test_obtener_salud_sistema_requires_permission(): void
    {
        $user = User::factory()->create();
        // No asignar permiso

        $response = CoopuertosServer::actingAs($user)->tool(ObtenerSaludSistema::class);

        $response->assertHasErrors();
    }

    public function test_obtener_salud_sistema_returns_health_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = CoopuertosServer::actingAs($user)->tool(ObtenerSaludSistema::class);

        $response->assertOk();
        $response->assertSee('database');
        $response->assertSee('queue');
    }

    public function test_obtener_metricas_colas_requires_permission(): void
    {
        $user = User::factory()->create();
        // No asignar permiso

        $response = CoopuertosServer::actingAs($user)->tool(ObtenerMetricasColas::class);

        $response->assertHasErrors();
    }

    public function test_obtener_metricas_colas_returns_queue_metrics(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear algunos jobs fallidos para probar
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test',
            'failed_at' => now(),
        ]);

        $response = CoopuertosServer::actingAs($user)->tool(ObtenerMetricasColas::class);

        $response->assertOk();
        $response->assertSee('pending');
        $response->assertSee('failed');
    }
}
