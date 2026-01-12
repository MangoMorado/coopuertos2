<?php

namespace Tests\Feature\Vehiculos;

use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleConductorAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_assign_conductor_to_vehiculo(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
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
            'conductor_id' => null,
        ]);

        // Asignar conductor actualizando el vehículo
        $updateData = [
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

        $response = $this->actingAs($user)->put("/vehiculos/{$vehiculo->id}", $updateData);

        $response->assertRedirect(route('vehiculos.index'));
        $response->assertSessionHas('success');

        // Verificar que el conductor fue asignado
        $vehiculo->refresh();
        $this->assertEquals($conductor->id, $vehiculo->conductor_id);
    }

    public function test_assigning_conductor_creates_conductor_vehicle_record(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'conductor_id' => null,
        ]);

        // Asignar conductor
        $updateData = [
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

        $this->actingAs($user)->put("/vehiculos/{$vehiculo->id}", $updateData);

        // Verificar que se creó el registro en conductor_vehicle
        $this->assertDatabaseHas('conductor_vehicle', [
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
        ]);

        $asignacion = ConductorVehicle::where('conductor_id', $conductor->id)
            ->where('vehicle_id', $vehiculo->id)
            ->first();

        $this->assertNotNull($asignacion);
        $this->assertNotNull($asignacion->fecha_asignacion);
        $this->assertNull($asignacion->fecha_desasignacion);
    }

    public function test_assigning_new_conductor_deactivates_previous_assignment(): void
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
            'placa' => 'ABC123',
            'conductor_id' => $conductor1->id,
        ]);

        // Crear asignación inicial
        $asignacionInicial = ConductorVehicle::create([
            'conductor_id' => $conductor1->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now()->subDays(10),
        ]);

        // Asignar un nuevo conductor
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

        $this->actingAs($user)->put("/vehiculos/{$vehiculo->id}", $updateData);

        // Verificar que la asignación anterior se desactivó
        $asignacionInicial->refresh();
        $this->assertEquals('inactivo', $asignacionInicial->estado);
        $this->assertNotNull($asignacionInicial->fecha_desasignacion);

        // Verificar que se creó la nueva asignación
        $this->assertDatabaseHas('conductor_vehicle', [
            'conductor_id' => $conductor2->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
        ]);

        // Verificar que el vehículo tiene el nuevo conductor
        $vehiculo->refresh();
        $this->assertEquals($conductor2->id, $vehiculo->conductor_id);
    }

    public function test_user_can_unassign_conductor_from_vehiculo(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'conductor_id' => $conductor->id,
        ]);

        // Crear asignación activa
        $asignacion = ConductorVehicle::create([
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

        // Verificar que el conductor fue removido
        $vehiculo->refresh();
        $this->assertNull($vehiculo->conductor_id);

        // Verificar que la asignación se desactivó
        $asignacion->refresh();
        $this->assertEquals('inactivo', $asignacion->estado);
        $this->assertNotNull($asignacion->fecha_desasignacion);
    }

    public function test_assigning_conductor_deactivates_other_vehicles_for_same_conductor(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'estado' => 'activo',
        ]);

        $vehiculo1 = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'conductor_id' => $conductor->id,
        ]);

        $vehiculo2 = Vehicle::factory()->create([
            'placa' => 'XYZ789',
            'conductor_id' => null,
        ]);

        // Crear asignación activa del conductor al vehículo 1
        $asignacion1 = ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo1->id,
            'estado' => 'activo',
            'fecha_asignacion' => now()->subDays(10),
        ]);

        // Asignar el mismo conductor al vehículo 2
        $updateData = [
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'placa' => 'XYZ789',
            'combustible' => 'diesel',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
            'conductor_id' => $conductor->id,
        ];

        $this->actingAs($user)->put("/vehiculos/{$vehiculo2->id}", $updateData);

        // Verificar que la asignación del vehículo 1 se desactivó
        $asignacion1->refresh();
        $this->assertEquals('inactivo', $asignacion1->estado);
        $this->assertNotNull($asignacion1->fecha_desasignacion);

        // Verificar que se creó la nueva asignación para el vehículo 2
        $this->assertDatabaseHas('conductor_vehicle', [
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo2->id,
            'estado' => 'activo',
        ]);

        // Verificar que la asignación activa del conductor ahora es solo para el vehículo 2
        $asignacionesActivas = ConductorVehicle::where('conductor_id', $conductor->id)
            ->where('estado', 'activo')
            ->get();
        $this->assertCount(1, $asignacionesActivas);
        $this->assertEquals($vehiculo2->id, $asignacionesActivas->first()->vehicle_id);

        // Verificar que el vehículo 2 tiene el conductor
        $vehiculo2->refresh();
        $this->assertEquals($conductor->id, $vehiculo2->conductor_id);
    }
}
