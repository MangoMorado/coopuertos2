<?php

namespace Tests\Feature\Api;

use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiResponseTest extends TestCase
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
        Permission::firstOrCreate(['name' => 'ver conductores', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crear conductores', 'guard_name' => 'web']);

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(['ver conductores', 'crear conductores']);
    }

    public function test_api_returns_json_responses(): void
    {
        // Test con endpoint público
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');

        // Test con endpoint autenticado
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/conductores');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_api_responses_have_correct_structure(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Test respuesta de listado
        $response = $this->getJson('/api/v1/conductores');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Test respuesta de creación
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
                'data',
                'message',
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Test respuesta de visualización
        $conductor = Conductor::where('cedula', '1234567890')->first();

        $response = $this->getJson("/api/v1/conductores/{$conductor->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_api_paginated_responses_have_meta_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Crear múltiples registros para forzar paginación
        Conductor::factory()->count(20)->create();

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
                'success' => true,
            ]);

        $meta = $response->json('meta');

        $this->assertIsInt($meta['current_page']);
        $this->assertIsInt($meta['per_page']);
        $this->assertIsInt($meta['total']);
        $this->assertIsInt($meta['last_page']);

        $this->assertEquals(10, $meta['per_page']);
        $this->assertGreaterThanOrEqual(20, $meta['total']);
        $this->assertGreaterThanOrEqual(1, $meta['current_page']);
        $this->assertGreaterThanOrEqual(1, $meta['last_page']);

        // Verificar links
        $links = $response->json('links');

        $this->assertIsString($links['first']);
        $this->assertIsString($links['last']);
        $this->assertNull($links['prev']); // Primera página, no hay prev
        $this->assertIsString($links['next']); // Hay siguiente página
    }

    public function test_api_error_responses_have_correct_format(): void
    {
        // Test error 401 (No autenticado)
        $response = $this->getJson('/api/v1/conductores');

        $response->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json');

        // Test error 404 (No encontrado)
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/conductores/99999');

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json');

        // Test error 403 (Sin permisos)
        $userWithoutPermission = User::factory()->create();
        Sanctum::actingAs($userWithoutPermission);

        $response = $this->postJson('/api/v1/conductores', [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ]);

        $response->assertStatus(403)
            ->assertHeader('Content-Type', 'application/json');

        // Test error 422 (Validación)
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/conductores', []);

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors(['nombres', 'apellidos', 'cedula', 'conductor_tipo', 'rh', 'estado']);

        // Verificar estructura de errores de validación
        $errors = $response->json('errors');

        $this->assertIsArray($errors);
        $this->assertArrayHasKey('nombres', $errors);
        $this->assertArrayHasKey('apellidos', $errors);
        $this->assertIsArray($errors['nombres']);
    }

    public function test_api_success_responses_include_message_when_provided(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Crear conductor (debería incluir mensaje)
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
                'data',
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Conductor creado exitosamente',
            ]);

        // Verificar que el mensaje es un string
        $this->assertIsString($response->json('message'));
    }

    public function test_api_paginated_responses_meta_is_correct(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Crear exactamente 25 conductores
        Conductor::factory()->count(25)->create();

        // Test con per_page=10 (debería tener 3 páginas)
        $response = $this->getJson('/api/v1/conductores?per_page=10');

        $meta = $response->json('meta');

        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(25, $meta['total']);
        $this->assertEquals(3, $meta['last_page']);

        // Test segunda página
        $response = $this->getJson('/api/v1/conductores?per_page=10&page=2');

        $meta = $response->json('meta');

        $this->assertEquals(2, $meta['current_page']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(25, $meta['total']);
        $this->assertEquals(3, $meta['last_page']);

        // Verificar links en segunda página
        $links = $response->json('links');

        $this->assertIsString($links['prev']); // Hay página anterior
        $this->assertIsString($links['next']); // Hay página siguiente
    }

    public function test_api_error_422_includes_validation_errors(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Intentar crear conductor sin datos requeridos
        $response = $this->postJson('/api/v1/conductores', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);

        $errors = $response->json('errors');

        $this->assertIsArray($errors);
        // Verificar que al menos algunos campos requeridos tienen errores
        $requiredFields = ['nombres', 'apellidos', 'cedula', 'conductor_tipo', 'rh', 'estado'];
        $hasErrors = false;

        foreach ($requiredFields as $field) {
            if (isset($errors[$field])) {
                $hasErrors = true;
                // Verificar que cada error es un array de mensajes
                $this->assertIsArray($errors[$field]);
                $this->assertNotEmpty($errors[$field]);
                $this->assertIsString($errors[$field][0]);
            }
        }

        $this->assertTrue($hasErrors, 'Debe haber al menos un error de validación');
    }

    public function test_api_error_404_has_correct_format(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Intentar acceder a un recurso que no existe
        $response = $this->getJson('/api/v1/conductores/99999');

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_api_error_401_has_correct_format(): void
    {
        // Intentar acceder sin autenticación
        $response = $this->getJson('/api/v1/conductores');

        $response->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_api_error_403_has_correct_format(): void
    {
        $user = User::factory()->create();
        // Sin permisos
        Sanctum::actingAs($user);

        // Intentar crear conductor sin permisos
        $response = $this->postJson('/api/v1/conductores', [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ]);

        $response->assertStatus(403)
            ->assertHeader('Content-Type', 'application/json');
    }
}
