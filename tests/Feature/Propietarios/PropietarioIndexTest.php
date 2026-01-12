<?php

namespace Tests\Feature\Propietarios;

use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropietarioIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_propietarios_index(): void
    {
        $user = User::factory()->create();

        Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($user)->get('/propietarios');

        $response->assertStatus(200);
        $response->assertViewIs('propietarios.index');
        $response->assertViewHas('propietarios');
    }

    public function test_propietarios_index_displays_paginated_results(): void
    {
        $user = User::factory()->create();

        // Crear 25 propietarios para probar la paginación
        for ($i = 1; $i <= 25; $i++) {
            Propietario::create([
                'tipo_identificacion' => 'Cédula de Ciudadanía',
                'numero_identificacion' => "123456789{$i}",
                'nombre_completo' => "Propietario {$i}",
                'tipo_propietario' => 'Persona Natural',
                'estado' => 'Activo',
            ]);
        }

        $response = $this->actingAs($user)->get('/propietarios');

        $response->assertStatus(200);
        $propietarios = $response->viewData('propietarios');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $propietarios);
        $this->assertEquals(10, $propietarios->perPage());
        $this->assertGreaterThanOrEqual(1, $propietarios->count());
    }

    public function test_propietarios_index_can_search_by_name(): void
    {
        $user = User::factory()->create();

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

        $response = $this->actingAs($user)->get('/propietarios?search=Juan');

        $response->assertStatus(200);
        $propietarios = $response->viewData('propietarios');
        $this->assertTrue(
            $propietarios->contains('nombre_completo', 'Juan Pérez'),
            'Los resultados deben incluir propietarios con nombre que contenga "Juan"'
        );
        $this->assertFalse(
            $propietarios->contains('nombre_completo', 'María González'),
            'Los resultados no deben incluir propietarios que no coincidan con la búsqueda'
        );
    }

    public function test_propietarios_index_can_search_by_cedula(): void
    {
        $user = User::factory()->create();

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

        $response = $this->actingAs($user)->get('/propietarios?search=1234567890');

        $response->assertStatus(200);
        $propietarios = $response->viewData('propietarios');
        $this->assertTrue(
            $propietarios->contains('numero_identificacion', '1234567890'),
            'Los resultados deben incluir propietarios con número de identificación "1234567890"'
        );
        $this->assertFalse(
            $propietarios->contains('numero_identificacion', '0987654321'),
            'Los resultados no deben incluir propietarios que no coincidan con la búsqueda'
        );
    }

    public function test_propietarios_index_can_search_by_email(): void
    {
        $user = User::factory()->create();

        Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'correo_electronico' => 'juan@example.com',
            'estado' => 'Activo',
        ]);

        Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '0987654321',
            'nombre_completo' => 'María González',
            'tipo_propietario' => 'Persona Natural',
            'correo_electronico' => 'maria@example.com',
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($user)->get('/propietarios?search=juan@example.com');

        $response->assertStatus(200);
        $propietarios = $response->viewData('propietarios');
        $this->assertTrue(
            $propietarios->contains('correo_electronico', 'juan@example.com'),
            'Los resultados deben incluir propietarios con correo "juan@example.com"'
        );
        $this->assertFalse(
            $propietarios->contains('correo_electronico', 'maria@example.com'),
            'Los resultados no deben incluir propietarios que no coincidan con la búsqueda'
        );
    }

    public function test_propietarios_index_ajax_returns_json_response(): void
    {
        $user = User::factory()->create();

        Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($user)->get('/propietarios?ajax=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'html',
            'pagination',
        ]);
    }
}
