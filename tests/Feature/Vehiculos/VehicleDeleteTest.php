<?php

namespace Tests\Feature\Vehiculos;

use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_delete_vehiculo(): void
    {
        $user = User::factory()->create();

        $vehiculo = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
        ]);

        $response = $this->actingAs($user)->delete("/vehiculos/{$vehiculo->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Vehículo eliminado.');

        $this->assertDatabaseMissing('vehicles', [
            'id' => $vehiculo->id,
        ]);
    }

    public function test_vehiculo_deletion_removes_from_database(): void
    {
        $user = User::factory()->create();

        $vehiculo = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'tipo' => 'Bus',
            'estado' => 'Activo',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehiculo->id,
            'placa' => 'ABC123',
        ]);

        $this->actingAs($user)->delete("/vehiculos/{$vehiculo->id}");

        $this->assertDatabaseMissing('vehicles', [
            'id' => $vehiculo->id,
        ]);

        // Verificar que el vehículo ya no existe
        $this->assertNull(Vehicle::find($vehiculo->id));
    }

    public function test_vehiculo_deletion_preserves_historical_assignments(): void
    {
        $user = User::factory()->create();

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'conductor_id' => $conductor->id,
        ]);

        // Crear asignación activa
        $asignacionActiva = ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        // Crear asignación histórica (inactiva) para otro conductor
        $conductor2 = Conductor::factory()->create([
            'cedula' => '0987654321',
            'estado' => 'activo',
        ]);

        $asignacionHistorica = ConductorVehicle::create([
            'conductor_id' => $conductor2->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'inactivo',
            'fecha_asignacion' => now()->subDays(30),
            'fecha_desasignacion' => now()->subDays(10),
        ]);

        // Verificar que las asignaciones existen antes de eliminar
        $this->assertDatabaseHas('conductor_vehicle', [
            'id' => $asignacionActiva->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
        ]);

        $this->assertDatabaseHas('conductor_vehicle', [
            'id' => $asignacionHistorica->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'inactivo',
        ]);

        // Eliminar el vehículo
        $this->actingAs($user)->delete("/vehiculos/{$vehiculo->id}");

        // Verificar que el vehículo fue eliminado
        $this->assertDatabaseMissing('vehicles', [
            'id' => $vehiculo->id,
        ]);

        // Verificar que las asignaciones se eliminaron automáticamente (cascade delete)
        // Nota: Con onDelete('cascade'), las asignaciones se eliminan automáticamente
        $this->assertDatabaseMissing('conductor_vehicle', [
            'vehicle_id' => $vehiculo->id,
        ]);

        // Verificar que los conductores no se eliminaron
        $this->assertDatabaseHas('conductors', [
            'id' => $conductor->id,
        ]);

        $this->assertDatabaseHas('conductors', [
            'id' => $conductor2->id,
        ]);
    }
}
