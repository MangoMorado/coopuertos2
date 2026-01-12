<?php

namespace Tests\Feature\Propietarios\Api;

use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropietarioSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_propietarios_via_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '0987654321',
            'nombre_completo' => 'María González',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $response = $this->getJson('/api/v1/propietarios/search?q=Juan');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'numero_identificacion',
                        'nombre_completo',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
        $this->assertTrue(
            collect($data)->contains('nombre_completo', 'Juan Pérez'),
            'Los resultados deben incluir propietarios con el nombre "Juan Pérez"'
        );
    }

    public function test_search_returns_relevant_results(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Crear propietarios con diferentes nombres y números de identificación
        $propietario1 = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $propietario2 = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '0987654321',
            'nombre_completo' => 'María González',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $propietario3 = Propietario::create([
            'tipo_identificacion' => 'RUC/NIT',
            'numero_identificacion' => '900123456-1',
            'nombre_completo' => 'Empresa Juan S.A.S',
            'tipo_propietario' => 'Persona Jurídica',
            'estado' => 'Activo',
        ]);

        // Buscar por nombre
        $response = $this->getJson('/api/v1/propietarios/search?q=Juan');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $nombres = collect($data)->pluck('nombre_completo')->toArray();

        // Debe encontrar propietario1 y propietario3 (ambos contienen "Juan")
        $this->assertContains('Juan Pérez', $nombres);
        $this->assertContains('Empresa Juan S.A.S', $nombres);
        $this->assertNotContains('María González', $nombres);

        // Buscar por número de identificación
        $response2 = $this->getJson('/api/v1/propietarios/search?q=1234567890');

        $response2->assertStatus(200)
            ->assertJson(['success' => true]);

        $data2 = $response2->json('data');
        $numeros = collect($data2)->pluck('numero_identificacion')->toArray();

        // Debe encontrar solo propietario1
        $this->assertContains('1234567890', $numeros);
        $this->assertNotContains('0987654321', $numeros);
        $this->assertNotContains('900123456-1', $numeros);
    }

    public function test_search_requires_minimum_2_characters(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        // Búsqueda con menos de 2 caracteres debe retornar array vacío
        $response = $this->getJson('/api/v1/propietarios/search?q=J');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    public function test_search_returns_empty_array_when_no_results(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        // Búsqueda que no encuentra resultados
        $response = $this->getJson('/api/v1/propietarios/search?q=NoExiste');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    public function test_unauthenticated_user_cannot_search_propietarios_via_api(): void
    {
        $response = $this->getJson('/api/v1/propietarios/search?q=Juan');

        $response->assertStatus(401);
    }

    public function test_search_limits_results_to_10(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Crear más de 10 propietarios con nombres que contengan "Juan"
        for ($i = 1; $i <= 15; $i++) {
            Propietario::create([
                'tipo_identificacion' => 'Cédula de Ciudadanía',
                'numero_identificacion' => "123456789{$i}",
                'nombre_completo' => "Juan Pérez {$i}",
                'tipo_propietario' => 'Persona Natural',
                'estado' => 'Activo',
            ]);
        }

        $response = $this->getJson('/api/v1/propietarios/search?q=Juan');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertLessThanOrEqual(10, count($data));
    }
}
