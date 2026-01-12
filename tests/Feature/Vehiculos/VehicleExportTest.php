<?php

namespace Tests\Feature\Vehiculos;

use App\Exports\VehiculosExport;
use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_export_vehiculos_to_excel(): void
    {
        $user = User::factory()->create();

        // Crear algunos vehículos de prueba
        Vehicle::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/vehiculos/exportar');

        $response->assertStatus(200);

        // Verificar que la respuesta es una descarga de archivo
        $this->assertTrue(
            $response->headers->has('Content-Disposition'),
            'La respuesta debe incluir el header Content-Disposition para descarga'
        );

        // Verificar que el tipo de contenido es Excel
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('spreadsheet', $contentType ?? '');
    }

    public function test_exported_excel_contains_all_vehiculos(): void
    {
        $user = User::factory()->create();

        // Crear vehículos de prueba con datos específicos
        $vehiculo1 = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
        ]);

        $vehiculo2 = Vehicle::factory()->create([
            'placa' => 'XYZ789',
            'marca' => 'Ford',
            'modelo' => 'Focus',
        ]);

        $vehiculo3 = Vehicle::factory()->create([
            'placa' => 'DEF456',
            'marca' => 'Nissan',
            'modelo' => 'Sentra',
        ]);

        // Verificar que todos los vehículos existen en la base de datos
        $this->assertDatabaseHas('vehicles', ['placa' => 'ABC123']);
        $this->assertDatabaseHas('vehicles', ['placa' => 'XYZ789']);
        $this->assertDatabaseHas('vehicles', ['placa' => 'DEF456']);

        // Verificar que el export contiene todos los vehículos
        $export = new VehiculosExport;
        $collection = $export->collection();

        $this->assertGreaterThanOrEqual(3, $collection->count());

        // Verificar que cada vehículo está en la colección
        $placas = $collection->pluck('placa')->toArray();
        $this->assertContains('ABC123', $placas);
        $this->assertContains('XYZ789', $placas);
        $this->assertContains('DEF456', $placas);
    }

    public function test_exported_excel_has_correct_columns(): void
    {
        $user = User::factory()->create();

        // Crear un vehículo de prueba
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'estado' => 'activo',
        ]);

        $vehiculo = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'tipo' => 'Bus',
            'marca' => 'Toyota',
            'modelo' => 'Coaster',
            'anio_fabricacion' => 2020,
            'chasis_vin' => 'VIN12345678901234',
            'capacidad_pasajeros' => 30,
            'capacidad_carga_kg' => 2000,
            'combustible' => 'diesel',
            'ultima_revision_tecnica' => '2024-01-15',
            'estado' => 'Activo',
            'propietario_nombre' => 'Juan Pérez',
        ]);

        // Asignar conductor al vehículo
        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        // Verificar que el export tiene las columnas correctas
        $export = new VehiculosExport;
        $headings = $export->headings();

        $expectedColumns = [
            'Placa',
            'Tipo',
            'Marca',
            'Modelo',
            'Año de Fabricación',
            'Chasis/VIN',
            'Capacidad Pasajeros',
            'Capacidad Carga (kg)',
            'Combustible',
            'Última Revisión Técnica',
            'Estado',
            'Propietario',
            'Conductor',
        ];

        $this->assertEquals($expectedColumns, $headings);
        $this->assertCount(13, $headings);
    }
}
