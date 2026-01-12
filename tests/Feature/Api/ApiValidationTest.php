<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiValidationTest extends TestCase
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
        Permission::firstOrCreate(['name' => 'ver vehiculos', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crear vehiculos', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ver propietarios', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crear propietarios', 'guard_name' => 'web']);

        // Crear roles si no existen
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(['ver conductores', 'crear conductores', 'editar conductores', 'eliminar conductores', 'ver vehiculos', 'crear vehiculos', 'ver propietarios', 'crear propietarios']);
    }

    public function test_api_validates_request_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Probar validación en endpoint de conductores
        $response = $this->postJson('/api/v1/conductores', []);

        $response->assertStatus(422);
        $this->assertTrue($response->json('message') !== null || $response->json('errors') !== null);

        // Probar validación en endpoint de vehículos
        $response = $this->postJson('/api/v1/vehiculos', []);

        $response->assertStatus(422);
        $this->assertTrue($response->json('message') !== null || $response->json('errors') !== null);

        // Probar validación en endpoint de propietarios
        $response = $this->postJson('/api/v1/propietarios', []);

        $response->assertStatus(422);
        $this->assertTrue($response->json('message') !== null || $response->json('errors') !== null);

        // Probar validación en endpoint de login
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
        $this->assertTrue($response->json('message') !== null || $response->json('errors') !== null);
    }

    public function test_api_returns_validation_errors(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Probar errores de validación en conductores
        $response = $this->postJson('/api/v1/conductores', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nombres', 'apellidos', 'cedula', 'conductor_tipo', 'rh', 'estado']);

        // Probar errores de validación en vehículos
        $response = $this->postJson('/api/v1/vehiculos', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tipo', 'marca', 'modelo', 'anio_fabricacion', 'placa', 'combustible', 'estado', 'propietario_nombre']);

        // Probar errores de validación en propietarios
        $response = $this->postJson('/api/v1/propietarios', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tipo_identificacion', 'numero_identificacion', 'nombre_completo', 'tipo_propietario', 'estado']);

        // Probar errores de validación en login
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_api_validation_errors_have_correct_format(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        Sanctum::actingAs($user);

        // Probar formato de errores en conductores
        $response = $this->postJson('/api/v1/conductores', []);

        $response->assertStatus(422);

        $json = $response->json();

        // Verificar que tiene estructura de errores de validación de Laravel
        // Laravel retorna errores en formato: { "message": "...", "errors": { "field": ["error"] } }
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('errors', $json);
        $this->assertIsArray($json['errors']);

        // Verificar que los errores son arrays de mensajes
        foreach ($json['errors'] as $field => $messages) {
            $this->assertIsArray($messages, "El campo '{$field}' debe tener un array de mensajes");
            $this->assertNotEmpty($messages, "El campo '{$field}' debe tener al menos un mensaje de error");
            foreach ($messages as $message) {
                $this->assertIsString($message, 'Cada mensaje de error debe ser un string');
            }
        }

        // Probar formato de errores en vehículos
        $response = $this->postJson('/api/v1/vehiculos', []);

        $response->assertStatus(422);

        $json = $response->json();
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('errors', $json);
        $this->assertIsArray($json['errors']);

        // Probar formato de errores en propietarios
        $response = $this->postJson('/api/v1/propietarios', [
            'correo_electronico' => 'email-invalido',
        ]);

        $response->assertStatus(422);

        $json = $response->json();
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('errors', $json);
        $this->assertIsArray($json['errors']);

        // Verificar que el error de email tiene el formato correcto
        if (isset($json['errors']['correo_electronico'])) {
            $this->assertIsArray($json['errors']['correo_electronico']);
            $this->assertNotEmpty($json['errors']['correo_electronico']);
        }

        // Probar formato de errores en login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email-format',
        ]);

        $response->assertStatus(422);

        $json = $response->json();
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('errors', $json);
        $this->assertIsArray($json['errors']);

        // Verificar que el error de email tiene el formato correcto
        if (isset($json['errors']['email'])) {
            $this->assertIsArray($json['errors']['email']);
            $this->assertNotEmpty($json['errors']['email']);
        }
    }
}
