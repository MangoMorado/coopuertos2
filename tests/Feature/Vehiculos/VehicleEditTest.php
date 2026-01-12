<?php

namespace Tests\Feature\Vehiculos;

use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VehicleEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_view_edit_vehiculo_form(): void
    {
        $user = User::factory()->create();

        $vehiculo = Vehicle::factory()->create([
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'placa' => 'ABC123',
        ]);

        $response = $this->actingAs($user)->get("/vehiculos/{$vehiculo->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('vehiculos.edit');
        $response->assertViewHas('vehiculo');
        $this->assertEquals($vehiculo->id, $response->viewData('vehiculo')->id);
    }

    public function test_user_can_update_vehiculo_with_valid_data(): void
    {
        $user = User::factory()->create();

        $vehiculo = Vehicle::factory()->create([
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'placa' => 'ABC123',
            'estado' => 'Activo',
        ]);

        $updateData = [
            'tipo' => 'Camioneta',
            'marca' => 'Ford',
            'modelo' => 'Ranger',
            'anio_fabricacion' => 2021,
            'placa' => 'ABC123',
            'chasis_vin' => 'NEWVIN1234567890',
            'capacidad_pasajeros' => 5,
            'capacidad_carga_kg' => 2000,
            'combustible' => 'gasolina',
            'estado' => 'En Mantenimiento',
            'propietario_nombre' => 'María González',
        ];

        $response = $this->actingAs($user)->put("/vehiculos/{$vehiculo->id}", $updateData);

        $response->assertRedirect(route('vehiculos.index'));
        $response->assertSessionHas('success', 'Vehículo actualizado correctamente.');

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehiculo->id,
            'tipo' => 'Camioneta',
            'marca' => 'Ford',
            'modelo' => 'Ranger',
            'estado' => 'En Mantenimiento',
            'propietario_nombre' => 'María González',
        ]);
    }

    public function test_user_cannot_update_vehiculo_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $vehiculo = Vehicle::factory()->create([
            'placa' => 'ABC123',
        ]);

        // Intentar actualizar con datos inválidos
        $response = $this->actingAs($user)->put("/vehiculos/{$vehiculo->id}", [
            'tipo' => 'InvalidType',
            'marca' => '',
            'placa' => 'ABC123',
            'estado' => 'InvalidState',
        ]);

        $response->assertSessionHasErrors([
            'tipo',
            'marca',
            'estado',
        ]);

        // Verificar que el vehículo no se actualizó con datos inválidos
        $vehiculo->refresh();
        $this->assertNotEquals('InvalidType', $vehiculo->tipo);
    }

    public function test_user_can_update_vehiculo_photo(): void
    {
        $user = User::factory()->create();

        $vehiculo = Vehicle::factory()->create([
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'foto' => null,
        ]);

        $newPhoto = UploadedFile::fake()->image('nueva_foto.jpg', 600, 400);

        $updateData = [
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'foto' => $newPhoto,
        ];

        $response = $this->actingAs($user)->put("/vehiculos/{$vehiculo->id}", $updateData);

        $response->assertRedirect(route('vehiculos.index'));
        $response->assertSessionHas('success');

        $vehiculo->refresh();
        $this->assertNotNull($vehiculo->foto);
        $this->assertStringStartsWith('data:image/', $vehiculo->foto);
    }

    public function test_user_can_update_vehiculo_conductor_assignment(): void
    {
        $user = User::factory()->create();

        $conductor1 = Conductor::factory()->create([
            'cedula' => '1234567890',
            'estado' => 'activo',
        ]);

        $conductor2 = Conductor::factory()->create([
            'cedula' => '0987654321',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehicle::factory()->create([
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'conductor_id' => $conductor1->id,
        ]);

        // Crear asignación inicial
        ConductorVehicle::create([
            'conductor_id' => $conductor1->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        // Actualizar asignando un nuevo conductor
        $updateData = [
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'conductor_id' => $conductor2->id,
        ];

        $response = $this->actingAs($user)->put("/vehiculos/{$vehiculo->id}", $updateData);

        $response->assertRedirect(route('vehiculos.index'));
        $response->assertSessionHas('success');

        $vehiculo->refresh();
        $this->assertEquals($conductor2->id, $vehiculo->conductor_id);

        // Verificar que la asignación anterior se desactivó
        $this->assertDatabaseHas('conductor_vehicle', [
            'conductor_id' => $conductor1->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'inactivo',
        ]);

        // Verificar que se creó la nueva asignación
        $this->assertDatabaseHas('conductor_vehicle', [
            'conductor_id' => $conductor2->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
        ]);
    }

    public function test_user_can_remove_conductor_assignment(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehicle::factory()->create([
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'conductor_id' => $conductor->id,
        ]);

        // Crear asignación inicial
        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        // Remover la asignación
        $updateData = [
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'ABC123',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'conductor_id' => null,
        ];

        $response = $this->actingAs($user)->put("/vehiculos/{$vehiculo->id}", $updateData);

        $response->assertRedirect(route('vehiculos.index'));
        $response->assertSessionHas('success');

        $vehiculo->refresh();
        $this->assertNull($vehiculo->conductor_id);

        // Verificar que la asignación se desactivó
        $this->assertDatabaseHas('conductor_vehicle', [
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'inactivo',
        ]);
    }
}
