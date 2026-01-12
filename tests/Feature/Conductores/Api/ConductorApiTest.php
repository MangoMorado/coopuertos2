<?php

namespace Tests\Feature\Conductores\Api;

use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConductorApiTest extends TestCase
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

    public function test_authenticated_user_can_list_conductores_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        Conductor::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/conductores');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'nombres',
                        'apellidos',
                        'cedula',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_unauthenticated_user_cannot_list_conductores_via_api(): void
    {
        $response = $this->getJson('/api/v1/conductores');

        $response->assertStatus(401);
    }

    public function test_user_with_permission_can_create_conductor_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $conductorData = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ];

        $response = $this->postJson('/api/v1/conductores', $conductorData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'uuid',
                    'nombres',
                    'apellidos',
                    'cedula',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Conductor creado exitosamente',
            ]);

        $this->assertDatabaseHas('conductors', [
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);
    }

    public function test_user_without_permission_cannot_create_conductor_via_api(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de crear
        Sanctum::actingAs($user);

        $conductorData = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ];

        $response = $this->postJson('/api/v1/conductores', $conductorData);

        $response->assertStatus(403);
    }

    public function test_user_can_get_conductor_by_id_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        $response = $this->getJson("/api/v1/conductores/{$conductor->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'uuid',
                    'nombres',
                    'apellidos',
                    'cedula',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $conductor->id,
                    'cedula' => '1234567890',
                    'nombres' => 'Juan',
                    'apellidos' => 'Pérez',
                ],
            ]);
    }

    public function test_user_can_get_public_conductor_by_uuid_via_api(): void
    {
        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        $response = $this->getJson("/api/v1/conductores/{$conductor->uuid}/public");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'uuid',
                    'nombres',
                    'apellidos',
                    'cedula',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $conductor->uuid,
                    'cedula' => '1234567890',
                ],
            ]);
    }

    public function test_public_conductor_endpoint_does_not_require_authentication(): void
    {
        $conductor = Conductor::factory()->create();

        $response = $this->getJson("/api/v1/conductores/{$conductor->uuid}/public");

        $response->assertStatus(200);
    }

    public function test_user_can_search_conductores_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        Conductor::factory()->create([
            'cedula' => '0987654321',
            'nombres' => 'María',
            'apellidos' => 'González',
        ]);

        $response = $this->getJson('/api/v1/conductores/search?q=Juan');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'nombres',
                        'apellidos',
                        'cedula',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
        $this->assertTrue(
            collect($data)->contains('nombres', 'Juan'),
            'Los resultados deben incluir conductores con el nombre "Juan"'
        );
    }

    public function test_user_with_permission_can_update_conductor_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        $updateData = [
            'nombres' => 'Carlos',
            'apellidos' => 'Rodríguez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'B',
            'rh' => 'A+',
            'estado' => 'inactivo',
        ];

        $response = $this->putJson("/api/v1/conductores/{$conductor->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'nombres',
                    'apellidos',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Conductor actualizado exitosamente',
                'data' => [
                    'nombres' => 'Carlos',
                    'apellidos' => 'Rodríguez',
                ],
            ]);

        $this->assertDatabaseHas('conductors', [
            'id' => $conductor->id,
            'nombres' => 'Carlos',
            'apellidos' => 'Rodríguez',
        ]);
    }

    public function test_user_without_permission_cannot_update_conductor_via_api(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de editar
        Sanctum::actingAs($user);

        $conductor = Conductor::factory()->create();

        $updateData = [
            'nombres' => 'Carlos',
            'apellidos' => 'Rodríguez',
            'cedula' => $conductor->cedula,
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ];

        $response = $this->putJson("/api/v1/conductores/{$conductor->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_delete_conductor_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
        ]);

        $response = $this->deleteJson("/api/v1/conductores/{$conductor->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Conductor eliminado exitosamente',
            ]);

        $this->assertDatabaseMissing('conductors', [
            'id' => $conductor->id,
        ]);
    }

    public function test_user_without_permission_cannot_delete_conductor_via_api(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de eliminar
        Sanctum::actingAs($user);

        $conductor = Conductor::factory()->create();

        $response = $this->deleteJson("/api/v1/conductores/{$conductor->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('conductors', [
            'id' => $conductor->id,
        ]);
    }

    public function test_api_returns_paginated_conductores(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        Conductor::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/conductores?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
            ])
            ->assertJson([
                'meta' => [
                    'per_page' => 10,
                    'total' => 25,
                ],
            ]);
    }

    public function test_api_returns_conductor_resource_with_correct_structure(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ]);

        $response = $this->getJson("/api/v1/conductores/{$conductor->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'uuid',
                    'nombres',
                    'apellidos',
                    'cedula',
                    'conductor_tipo',
                    'rh',
                    'numero_interno',
                    'celular',
                    'correo',
                    'fecha_nacimiento',
                    'otra_profesion',
                    'nivel_estudios',
                    'relevo',
                    'estado',
                    'foto',
                    'ruta_carnet',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_api_validates_conductor_data_before_creating(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Intentar crear sin campos requeridos
        $response = $this->postJson('/api/v1/conductores', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombres', 'apellidos', 'cedula', 'conductor_tipo', 'rh', 'estado']);

        // Intentar crear con cédula duplicada
        Conductor::factory()->create(['cedula' => '1234567890']);

        $response = $this->postJson('/api/v1/conductores', [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cedula']);
    }

    public function test_api_validates_conductor_data_before_updating(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
        ]);

        // Intentar actualizar sin campos requeridos
        $response = $this->putJson("/api/v1/conductores/{$conductor->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombres', 'apellidos', 'cedula', 'conductor_tipo', 'rh', 'estado']);

        // Intentar actualizar con cédula duplicada
        $otroConductor = Conductor::factory()->create(['cedula' => '0987654321']);

        $response = $this->putJson("/api/v1/conductores/{$conductor->id}", [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '0987654321', // Cédula de otro conductor
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cedula']);
    }
}
