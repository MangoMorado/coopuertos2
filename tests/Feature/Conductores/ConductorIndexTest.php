<?php

namespace Tests\Feature\Conductores;

use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConductorIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear permisos y roles necesarios
        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'ver conductores', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crear conductores', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'editar conductores', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'eliminar conductores', 'guard_name' => 'web']);

        // Crear roles si no existen
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // Asignar permisos a Admin
        $adminRole->givePermissionTo(['ver conductores', 'crear conductores', 'editar conductores', 'eliminar conductores']);

        // Asignar solo permiso de ver a User
        $userRole->givePermissionTo('ver conductores');
    }

    public function test_user_with_permission_can_view_conductores_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/conductores');

        $response->assertStatus(200);
        $response->assertViewIs('conductores.index');
    }

    public function test_user_without_permission_cannot_view_conductores_index(): void
    {
        $user = User::factory()->create();
        // No asignar ningún rol o permiso

        $response = $this->actingAs($user)->get('/conductores');

        $response->assertStatus(403);
    }

    public function test_conductores_index_displays_paginated_results(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear 15 conductores para probar la paginación
        Conductor::factory()->count(15)->create();

        $response = $this->actingAs($user)->get('/conductores');

        $response->assertStatus(200);
        $response->assertViewHas('conductores');
        $this->assertCount(10, $response->viewData('conductores'));
    }

    public function test_conductores_index_can_search_by_cedula(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->create(['cedula' => '1234567890']);
        Conductor::factory()->create(['cedula' => '0987654321']);

        $response = $this->actingAs($user)->get('/conductores?search=1234567890');

        $response->assertStatus(200);
        $conductores = $response->viewData('conductores');
        $this->assertCount(1, $conductores);
        $this->assertEquals('1234567890', $conductores->first()->cedula);
    }

    public function test_conductores_index_can_search_by_nombres(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->create(['nombres' => 'Juan', 'apellidos' => 'Pérez']);
        Conductor::factory()->create(['nombres' => 'Pedro', 'apellidos' => 'García']);

        $response = $this->actingAs($user)->get('/conductores?search=Juan');

        $response->assertStatus(200);
        $conductores = $response->viewData('conductores');
        $this->assertCount(1, $conductores);
        $this->assertEquals('Juan', $conductores->first()->nombres);
    }

    public function test_conductores_index_can_search_by_apellidos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->create(['nombres' => 'Juan', 'apellidos' => 'Pérez']);
        Conductor::factory()->create(['nombres' => 'Pedro', 'apellidos' => 'García']);

        $response = $this->actingAs($user)->get('/conductores?search=Pérez');

        $response->assertStatus(200);
        $conductores = $response->viewData('conductores');
        $this->assertCount(1, $conductores);
        $this->assertEquals('Pérez', $conductores->first()->apellidos);
    }

    public function test_conductores_index_can_search_by_numero_interno(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->create(['numero_interno' => '123']);
        Conductor::factory()->create(['numero_interno' => '456']);

        $response = $this->actingAs($user)->get('/conductores?search=123');

        $response->assertStatus(200);
        $conductores = $response->viewData('conductores');
        $this->assertCount(1, $conductores);
        $this->assertEquals('123', $conductores->first()->numero_interno);
    }

    public function test_conductores_index_can_search_by_celular(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->create(['celular' => '3001234567']);
        Conductor::factory()->create(['celular' => '3007654321']);

        $response = $this->actingAs($user)->get('/conductores?search=3001234567');

        $response->assertStatus(200);
        $conductores = $response->viewData('conductores');
        $this->assertCount(1, $conductores);
        $this->assertEquals('3001234567', $conductores->first()->celular);
    }

    public function test_conductores_index_can_search_by_correo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->create(['correo' => 'juan@example.com']);
        Conductor::factory()->create(['correo' => 'pedro@example.com']);

        $response = $this->actingAs($user)->get('/conductores?search=juan@example.com');

        $response->assertStatus(200);
        $conductores = $response->viewData('conductores');
        $this->assertCount(1, $conductores);
        $this->assertEquals('juan@example.com', $conductores->first()->correo);
    }

    public function test_conductores_index_includes_eager_loaded_vehiculo_activo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create();

        $response = $this->actingAs($user)->get('/conductores');

        $response->assertStatus(200);
        $conductores = $response->viewData('conductores');

        // Verificar que la relación está cargada (no debería generar N+1 queries)
        $this->assertTrue($conductores->first()->relationLoaded('asignacionActiva'));
    }

    public function test_conductores_index_ajax_returns_json_response(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Conductor::factory()->count(5)->create();

        $response = $this->actingAs($user)
            ->get('/conductores?ajax=1', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'html',
            'pagination',
        ]);
    }
}
