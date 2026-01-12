<?php

namespace Tests\Feature\Propietarios;

use App\Models\Propietario;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropietarioDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_delete_propietario(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::factory()->create([
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
        ]);

        $response = $this->actingAs($user)->delete("/propietarios/{$propietario->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Propietario eliminado correctamente.');

        $this->assertDatabaseMissing('propietarios', [
            'id' => $propietario->id,
        ]);
    }

    public function test_propietario_deletion_removes_from_database(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::factory()->create([
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        // Verificar que el propietario existe antes de eliminar
        $this->assertDatabaseHas('propietarios', [
            'id' => $propietario->id,
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
        ]);

        // Eliminar el propietario
        $this->actingAs($user)->delete("/propietarios/{$propietario->id}");

        // Verificar que el propietario fue eliminado
        $this->assertDatabaseMissing('propietarios', [
            'id' => $propietario->id,
        ]);

        // Verificar que el propietario ya no existe
        $this->assertNull(Propietario::find($propietario->id));
    }

    public function test_propietario_deletion_preserves_associated_vehiculos(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::factory()->create([
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
        ]);

        // Crear vehículos asociados con el nombre del propietario
        // Nota: Los vehículos solo almacenan el nombre del propietario como texto,
        // no hay una clave foránea, por lo que los vehículos deben permanecer
        $vehiculo1 = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'propietario_nombre' => $propietario->nombre_completo,
        ]);

        $vehiculo2 = Vehicle::factory()->create([
            'placa' => 'XYZ789',
            'marca' => 'Ford',
            'modelo' => 'Focus',
            'propietario_nombre' => $propietario->nombre_completo,
        ]);

        // Verificar que los vehículos existen antes de eliminar
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehiculo1->id,
            'propietario_nombre' => $propietario->nombre_completo,
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehiculo2->id,
            'propietario_nombre' => $propietario->nombre_completo,
        ]);

        // Eliminar el propietario
        $this->actingAs($user)->delete("/propietarios/{$propietario->id}");

        // Verificar que el propietario fue eliminado
        $this->assertDatabaseMissing('propietarios', [
            'id' => $propietario->id,
        ]);

        // Verificar que los vehículos NO fueron eliminados
        // Los vehículos deben permanecer en la base de datos
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehiculo1->id,
            'placa' => 'ABC123',
            'propietario_nombre' => $propietario->nombre_completo, // El nombre sigue estando
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehiculo2->id,
            'placa' => 'XYZ789',
            'propietario_nombre' => $propietario->nombre_completo, // El nombre sigue estando
        ]);

        // Verificar que los vehículos aún existen como modelos
        $this->assertNotNull(Vehicle::find($vehiculo1->id));
        $this->assertNotNull(Vehicle::find($vehiculo2->id));
    }

    public function test_propietario_deletion_with_no_associated_vehiculos(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::factory()->create([
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
        ]);

        // Verificar que el propietario existe
        $this->assertDatabaseHas('propietarios', [
            'id' => $propietario->id,
        ]);

        // Eliminar el propietario
        $response = $this->actingAs($user)->delete("/propietarios/{$propietario->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que el propietario fue eliminado
        $this->assertDatabaseMissing('propietarios', [
            'id' => $propietario->id,
        ]);
    }

    public function test_propietario_deletion_handles_multiple_vehiculos_with_same_name(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::factory()->create([
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
        ]);

        // Crear múltiples vehículos con el mismo nombre de propietario
        $vehiculos = Vehicle::factory()->count(5)->create([
            'propietario_nombre' => $propietario->nombre_completo,
        ]);

        // Eliminar el propietario
        $this->actingAs($user)->delete("/propietarios/{$propietario->id}");

        // Verificar que todos los vehículos permanecen
        foreach ($vehiculos as $vehiculo) {
            $this->assertDatabaseHas('vehicles', [
                'id' => $vehiculo->id,
            ]);
            $this->assertNotNull(Vehicle::find($vehiculo->id));
        }

        // Verificar que el propietario fue eliminado
        $this->assertDatabaseMissing('propietarios', [
            'id' => $propietario->id,
        ]);
    }

    public function test_propietario_deletion_preserves_vehiculo_data_integrity(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::factory()->create([
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
        ]);

        $vehiculo = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio_fabricacion' => 2020,
            'propietario_nombre' => $propietario->nombre_completo,
            'tipo' => 'Bus',
            'estado' => 'Activo',
        ]);

        // Guardar los datos originales del vehículo
        $placaOriginal = $vehiculo->placa;
        $marcaOriginal = $vehiculo->marca;
        $modeloOriginal = $vehiculo->modelo;
        $propietarioNombreOriginal = $vehiculo->propietario_nombre;

        // Eliminar el propietario
        $this->actingAs($user)->delete("/propietarios/{$propietario->id}");

        // Recargar el vehículo desde la base de datos
        $vehiculoRefresh = Vehicle::find($vehiculo->id);

        // Verificar que todos los datos del vehículo se mantienen intactos
        $this->assertNotNull($vehiculoRefresh);
        $this->assertEquals($placaOriginal, $vehiculoRefresh->placa);
        $this->assertEquals($marcaOriginal, $vehiculoRefresh->marca);
        $this->assertEquals($modeloOriginal, $vehiculoRefresh->modelo);
        $this->assertEquals($propietarioNombreOriginal, $vehiculoRefresh->propietario_nombre);
        $this->assertEquals(2020, $vehiculoRefresh->anio_fabricacion);
        $this->assertEquals('Bus', $vehiculoRefresh->tipo);
        $this->assertEquals('Activo', $vehiculoRefresh->estado);
    }
}
