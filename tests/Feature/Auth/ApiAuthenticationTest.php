<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_via_api_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'theme',
                        'roles',
                        'permissions',
                    ],
                    'token',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Autenticación exitosa',
            ]);

        $this->assertIsString($response->json('data.token'));
        $this->assertEquals('test@example.com', $response->json('data.user.email'));
    }

    public function test_user_cannot_login_via_api_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_login_via_api_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_login_via_api_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_can_logout_via_api(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente',
            ]);
    }

    public function test_user_can_get_authenticated_user_info_via_api(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'theme',
                    'roles',
                    'permissions',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                'message' => 'Usuario obtenido exitosamente',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_api_endpoints(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_logout_via_api(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_api_login_returns_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $token = $response->json('data.token');

        $this->assertIsString($token);
        $this->assertGreaterThan(0, strlen($token));

        // Verificar que el token puede ser usado para autenticación
        $authenticatedResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/user');

        $authenticatedResponse->assertStatus(200);
    }

    public function test_api_token_can_be_used_for_authentication(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Obtener token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        // Usar token para acceder a endpoint protegido
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'test@example.com',
                ],
            ]);
    }
}
