<?php

namespace Tests\Feature\Vehiculos;

use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_view_vehiculos_index(): void
    {
        $user = User::factory()->create();

        Vehicle::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/vehiculos');

        $response->assertStatus(200);
        $response->assertViewIs('vehiculos.index');
        $response->assertViewHas('vehiculos');
    }

    public function test_vehiculos_index_displays_paginated_results(): void
    {
        $user = User::factory()->create();

        Vehicle::factory()->count(25)->create();

        $response = $this->actingAs($user)->get('/vehiculos');

        $response->assertStatus(200);
        $vehiculos = $response->viewData('vehiculos');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $vehiculos);
        $this->assertEquals(10, $vehiculos->perPage());
        $this->assertGreaterThanOrEqual(1, $vehiculos->count());
    }

    public function test_vehiculos_index_can_search_by_placa(): void
    {
        $user = User::factory()->create();

        Vehicle::factory()->create(['placa' => 'ABC123']);
        Vehicle::factory()->create(['placa' => 'XYZ789']);

        $response = $this->actingAs($user)->get('/vehiculos?search=ABC');

        $response->assertStatus(200);
        $vehiculos = $response->viewData('vehiculos');
        $this->assertTrue(
            $vehiculos->contains('placa', 'ABC123'),
            'Los resultados deben incluir vehículos con placa que contenga "ABC"'
        );
    }

    public function test_vehiculos_index_can_search_by_marca(): void
    {
        $user = User::factory()->create();

        Vehicle::factory()->create(['marca' => 'Toyota']);
        Vehicle::factory()->create(['marca' => 'Ford']);

        $response = $this->actingAs($user)->get('/vehiculos?search=Toyota');

        $response->assertStatus(200);
        $vehiculos = $response->viewData('vehiculos');
        $this->assertTrue(
            $vehiculos->contains('marca', 'Toyota'),
            'Los resultados deben incluir vehículos con marca "Toyota"'
        );
    }

    public function test_vehiculos_index_can_search_by_modelo(): void
    {
        $user = User::factory()->create();

        Vehicle::factory()->create(['modelo' => 'Corolla']);
        Vehicle::factory()->create(['modelo' => 'Camry']);

        $response = $this->actingAs($user)->get('/vehiculos?search=Corolla');

        $response->assertStatus(200);
        $vehiculos = $response->viewData('vehiculos');
        $this->assertTrue(
            $vehiculos->contains('modelo', 'Corolla'),
            'Los resultados deben incluir vehículos con modelo "Corolla"'
        );
    }

    public function test_vehiculos_index_can_search_by_tipo(): void
    {
        $user = User::factory()->create();

        Vehicle::factory()->create(['tipo' => 'Bus']);
        Vehicle::factory()->create(['tipo' => 'Camioneta']);

        $response = $this->actingAs($user)->get('/vehiculos?search=Bus');

        $response->assertStatus(200);
        $vehiculos = $response->viewData('vehiculos');
        $this->assertTrue(
            $vehiculos->contains('tipo', 'Bus'),
            'Los resultados deben incluir vehículos con tipo "Bus"'
        );
    }

    public function test_vehiculos_index_can_search_by_propietario_nombre(): void
    {
        $user = User::factory()->create();

        Vehicle::factory()->create(['propietario_nombre' => 'Juan Pérez']);
        Vehicle::factory()->create(['propietario_nombre' => 'María González']);

        $response = $this->actingAs($user)->get('/vehiculos?search=Juan');

        $response->assertStatus(200);
        $vehiculos = $response->viewData('vehiculos');
        $this->assertTrue(
            $vehiculos->contains('propietario_nombre', 'Juan Pérez'),
            'Los resultados deben incluir vehículos con propietario que contenga "Juan"'
        );
    }

    public function test_vehiculos_index_can_search_by_conductor_name(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehicle::factory()->create();
        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        Vehicle::factory()->create(); // Vehículo sin conductor asignado

        $response = $this->actingAs($user)->get('/vehiculos?search=Juan');

        $response->assertStatus(200);
        $vehiculos = $response->viewData('vehiculos');
        $this->assertTrue(
            $vehiculos->contains('id', $vehiculo->id),
            'Los resultados deben incluir vehículos asignados a conductores con nombre "Juan"'
        );
    }

    public function test_vehiculos_index_includes_eager_loaded_relationships(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehicle::factory()->create();
        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        $response = $this->actingAs($user)->get('/vehiculos');

        $response->assertStatus(200);
        $vehiculos = $response->viewData('vehiculos');
        $vehiculoEncontrado = $vehiculos->firstWhere('id', $vehiculo->id);

        $this->assertNotNull($vehiculoEncontrado);
        $this->assertTrue($vehiculoEncontrado->relationLoaded('asignaciones'), 'La relación asignaciones debe estar cargada');
    }

    public function test_vehiculos_index_ajax_returns_json_response(): void
    {
        $user = User::factory()->create();

        Vehicle::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/vehiculos?ajax=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'html',
            'pagination',
        ]);
    }
}
