<?php

namespace Tests\Feature\Vehiculos;

use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_view_vehiculo_details(): void
    {
        $user = User::factory()->create();

        $vehiculo = Vehicle::factory()->create([
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'placa' => 'ABC123',
        ]);

        $response = $this->actingAs($user)->get("/vehiculos/{$vehiculo->id}");

        $response->assertStatus(200);
        $response->assertViewIs('vehiculos.show');
        $response->assertViewHas('vehiculo');
        $this->assertEquals($vehiculo->id, $response->viewData('vehiculo')->id);
    }

    public function test_vehiculo_show_displays_all_information(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehicle::factory()->create([
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'chasis_vin' => '1234567890ABCDEFG',
            'capacidad_pasajeros' => 30,
            'capacidad_carga_kg' => 1000,
            'combustible' => 'diesel',
            'ultima_revision_tecnica' => '2024-01-01',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'conductor_id' => $conductor->id,
        ]);

        // Crear asignación en conductor_vehicle
        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        $response = $this->actingAs($user)->get("/vehiculos/{$vehiculo->id}");

        $response->assertStatus(200);
        $vehiculoEnVista = $response->viewData('vehiculo');

        // Verificar que todos los campos están presentes
        $this->assertEquals('Bus', $vehiculoEnVista->tipo);
        $this->assertEquals('Toyota', $vehiculoEnVista->marca);
        $this->assertEquals('Corolla', $vehiculoEnVista->modelo);
        $this->assertEquals(2020, $vehiculoEnVista->anio_fabricacion);
        $this->assertEquals('ABC123', $vehiculoEnVista->placa);
        $this->assertEquals('1234567890ABCDEFG', $vehiculoEnVista->chasis_vin);
        $this->assertEquals(30, $vehiculoEnVista->capacidad_pasajeros);
        $this->assertEquals(1000, $vehiculoEnVista->capacidad_carga_kg);
        $this->assertEquals('diesel', $vehiculoEnVista->combustible);
        $this->assertEquals('Activo', $vehiculoEnVista->estado);
        $this->assertEquals('Juan Pérez', $vehiculoEnVista->propietario_nombre);

        // Verificar que la relación conductor está cargada
        $this->assertTrue($vehiculoEnVista->relationLoaded('conductor'));
        $this->assertNotNull($vehiculoEnVista->conductor);
        $this->assertEquals('Juan', $vehiculoEnVista->conductor->nombres);
        $this->assertEquals('Pérez', $vehiculoEnVista->conductor->apellidos);

        // Verificar que la vista contiene la información
        $response->assertSee('Bus', false);
        $response->assertSee('Toyota', false);
        $response->assertSee('Corolla', false);
        $response->assertSee('ABC123', false);
        $response->assertSee('Juan Pérez', false);
    }

    public function test_vehiculo_show_displays_vehiculo_without_conductor(): void
    {
        $user = User::factory()->create();

        $vehiculo = Vehicle::factory()->create([
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'placa' => 'ABC123',
            'conductor_id' => null,
        ]);

        $response = $this->actingAs($user)->get("/vehiculos/{$vehiculo->id}");

        $response->assertStatus(200);
        $vehiculoEnVista = $response->viewData('vehiculo');

        // Verificar que no tiene conductor asignado
        $this->assertNull($vehiculoEnVista->conductor_id);

        // Verificar que muestra "Sin asignar" en la vista
        $response->assertSee('Sin asignar', false);
    }
}
