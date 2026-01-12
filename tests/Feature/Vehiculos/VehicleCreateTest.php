<?php

namespace Tests\Feature\Vehiculos;

use App\Models\Conductor;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VehicleCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_view_create_vehiculo_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/vehiculos/create');

        $response->assertStatus(200);
        $response->assertViewIs('vehiculos.create');
    }

    public function test_user_can_create_vehiculo_with_valid_data(): void
    {
        $user = User::factory()->create();

        $vehiculoData = [
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
        ];

        $response = $this->actingAs($user)->post('/vehiculos', $vehiculoData);

        $response->assertRedirect(route('vehiculos.index'));
        $response->assertSessionHas('success', 'Vehículo creado correctamente.');

        $this->assertDatabaseHas('vehicles', [
            'placa' => 'ABC123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'propietario_nombre' => 'Juan Pérez',
        ]);
    }

    public function test_user_cannot_create_vehiculo_without_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/vehiculos', []);

        $response->assertSessionHasErrors([
            'tipo',
            'marca',
            'modelo',
            'anio_fabricacion',
            'placa',
            'combustible',
            'estado',
            'propietario_nombre',
        ]);

        // Verificar que no se creó ningún vehículo
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_user_can_create_vehiculo_with_photo(): void
    {
        $user = User::factory()->create();

        $photo = UploadedFile::fake()->image('vehiculo.jpg', 600, 400);

        $vehiculoData = [
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'foto' => $photo,
        ];

        $response = $this->actingAs($user)->post('/vehiculos', $vehiculoData);

        $response->assertRedirect(route('vehiculos.index'));
        $response->assertSessionHas('success');

        $vehiculo = Vehicle::where('placa', 'ABC123')->first();
        $this->assertNotNull($vehiculo);
        $this->assertStringStartsWith('data:image/', $vehiculo->foto);
    }

    public function test_user_can_create_vehiculo_with_conductor_assignment(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'estado' => 'activo',
        ]);

        $vehiculoData = [
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'conductor_id' => $conductor->id,
        ];

        $response = $this->actingAs($user)->post('/vehiculos', $vehiculoData);

        $response->assertRedirect(route('vehiculos.index'));
        $response->assertSessionHas('success');

        $vehiculo = Vehicle::where('placa', 'ABC123')->first();
        $this->assertNotNull($vehiculo);
        $this->assertEquals($conductor->id, $vehiculo->conductor_id);

        // Verificar que se creó la asignación en conductor_vehicle
        $this->assertDatabaseHas('conductor_vehicle', [
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
        ]);
    }

    public function test_vehiculo_placa_is_converted_to_uppercase(): void
    {
        $user = User::factory()->create();

        $vehiculoData = [
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'abc123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
        ];

        $response = $this->actingAs($user)->post('/vehiculos', $vehiculoData);

        $response->assertRedirect(route('vehiculos.index'));

        // Verificar que la placa se guardó en mayúsculas
        $this->assertDatabaseHas('vehicles', [
            'placa' => 'ABC123',
        ]);

        $this->assertDatabaseMissing('vehicles', [
            'placa' => 'abc123',
        ]);
    }

    public function test_vehiculo_created_successfully_redirects_to_index(): void
    {
        $user = User::factory()->create();

        $vehiculoData = [
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
        ];

        $response = $this->actingAs($user)->post('/vehiculos', $vehiculoData);

        $response->assertRedirect(route('vehiculos.index'));
        $response->assertSessionHas('success', 'Vehículo creado correctamente.');
    }
}
