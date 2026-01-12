<?php

namespace Tests\Feature\Conductores;

use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConductorShowTest extends TestCase
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

    public function test_user_can_view_conductor_info(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create();

        $response = $this->actingAs($user)->get("/conductores/{$conductor->id}/info");

        $response->assertStatus(200);
        $response->assertViewIs('conductores.info');
        $response->assertViewHas('conductor');
        $this->assertEquals($conductor->id, $response->viewData('conductor')->id);
    }

    public function test_user_can_view_public_conductor_by_uuid(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
        ]);

        // La vista pública actualmente requiere autenticación debido al layout usado
        $response = $this->actingAs($user)->get("/conductor/{$conductor->uuid}");

        $response->assertStatus(200);
        $response->assertViewIs('conductores.show');
        $response->assertViewHas('conductor');
        $this->assertEquals($conductor->uuid, $response->viewData('conductor')->uuid);
        $this->assertEquals('Juan', $response->viewData('conductor')->nombres);
        $this->assertEquals('Pérez', $response->viewData('conductor')->apellidos);
    }

    public function test_public_conductor_view_includes_all_necessary_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'celular' => '3001234567',
            'correo' => 'juan@example.com',
            'estado' => 'activo',
        ]);

        // La vista pública actualmente requiere autenticación debido al layout usado
        $response = $this->actingAs($user)->get("/conductor/{$conductor->uuid}");

        $response->assertStatus(200);
        $conductorData = $response->viewData('conductor');

        // Verificar que todos los datos necesarios están presentes
        $this->assertEquals('Juan', $conductorData->nombres);
        $this->assertEquals('Pérez', $conductorData->apellidos);
        $this->assertEquals('1234567890', $conductorData->cedula);
        $this->assertEquals('A', $conductorData->conductor_tipo);
        $this->assertEquals('O+', $conductorData->rh);
        $this->assertEquals('3001234567', $conductorData->celular);
        $this->assertEquals('juan@example.com', $conductorData->correo);
        $this->assertEquals('activo', $conductorData->estado);
        $this->assertNotNull($conductorData->uuid);

        // Verificar que la relación asignacionActiva está cargada
        $this->assertTrue($conductorData->relationLoaded('asignacionActiva'));
    }

    public function test_invalid_uuid_returns_404_for_public_view(): void
    {
        $invalidUuid = '00000000-0000-0000-0000-000000000000';

        $response = $this->get("/conductor/{$invalidUuid}");

        $response->assertStatus(404);
    }

    public function test_user_without_permission_cannot_view_conductor_info(): void
    {
        $user = User::factory()->create();
        // No asignar ningún rol o permiso

        $conductor = Conductor::factory()->create();

        $response = $this->actingAs($user)->get("/conductores/{$conductor->id}/info");

        $response->assertStatus(403);
    }
}
