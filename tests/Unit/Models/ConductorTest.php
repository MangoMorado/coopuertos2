<?php

namespace Tests\Unit\Models;

use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConductorTest extends TestCase
{
    use RefreshDatabase;

    public function test_conductor_uuid_is_generated_on_creation(): void
    {
        $conductor = Conductor::factory()->create([
            'uuid' => null, // Intentar crear sin UUID
        ]);

        // Verificar que el UUID fue generado automáticamente
        $this->assertNotNull($conductor->uuid);
        $this->assertNotEmpty($conductor->uuid);
        $this->assertTrue(Str::isUuid($conductor->uuid), 'El UUID debe ser un UUID válido');

        // Verificar que cada conductor tiene un UUID único
        $conductor2 = Conductor::factory()->create();
        $this->assertNotEquals($conductor->uuid, $conductor2->uuid, 'Cada conductor debe tener un UUID único');
    }

    public function test_conductor_has_vehicles_relationship(): void
    {
        $conductor = Conductor::factory()->create();
        $vehicle1 = Vehicle::factory()->create();
        $vehicle2 = Vehicle::factory()->create();

        // Crear asignaciones
        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehicle1->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehicle2->id,
            'estado' => 'inactivo',
            'fecha_asignacion' => now()->subDays(10),
            'fecha_desasignacion' => now()->subDays(5),
        ]);

        // Verificar relación
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $conductor->vehicles());
        $this->assertCount(2, $conductor->vehicles);
        $this->assertTrue($conductor->vehicles->contains($vehicle1));
        $this->assertTrue($conductor->vehicles->contains($vehicle2));
    }

    public function test_conductor_has_vehiculo_activo_method(): void
    {
        $conductor = Conductor::factory()->create();
        $vehicleActivo = Vehicle::factory()->create();
        $vehicleInactivo = Vehicle::factory()->create();

        // Crear asignación activa
        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehicleActivo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        // Crear asignación inactiva
        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehicleInactivo->id,
            'estado' => 'inactivo',
            'fecha_asignacion' => now()->subDays(10),
            'fecha_desasignacion' => now()->subDays(5),
        ]);

        // Verificar que vehiculoActivo() retorna solo el vehículo activo
        $vehiculoActivo = $conductor->vehiculoActivo();

        $this->assertNotNull($vehiculoActivo);
        $this->assertEquals($vehicleActivo->id, $vehiculoActivo->id);
        $this->assertEquals('activo', $vehiculoActivo->pivot->estado);
    }

    public function test_conductor_has_asignaciones_relationship(): void
    {
        $conductor = Conductor::factory()->create();
        $vehicle1 = Vehicle::factory()->create();
        $vehicle2 = Vehicle::factory()->create();

        // Crear asignaciones
        $asignacion1 = ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehicle1->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        $asignacion2 = ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehicle2->id,
            'estado' => 'inactivo',
            'fecha_asignacion' => now()->subDays(10),
            'fecha_desasignacion' => now()->subDays(5),
        ]);

        // Verificar relación
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $conductor->asignaciones());
        $this->assertCount(2, $conductor->asignaciones);
        $this->assertTrue($conductor->asignaciones->contains($asignacion1));
        $this->assertTrue($conductor->asignaciones->contains($asignacion2));
    }

    public function test_conductor_has_asignacion_activa_method(): void
    {
        $conductor = Conductor::factory()->create();
        $vehicleActivo = Vehicle::factory()->create();
        $vehicleInactivo = Vehicle::factory()->create();

        // Crear asignación activa
        $asignacionActiva = ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehicleActivo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        // Crear asignación inactiva
        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehicleInactivo->id,
            'estado' => 'inactivo',
            'fecha_asignacion' => now()->subDays(10),
            'fecha_desasignacion' => now()->subDays(5),
        ]);

        // Verificar que asignacionActiva() retorna solo la asignación activa
        $asignacion = $conductor->asignacionActiva()->first();

        $this->assertNotNull($asignacion);
        $this->assertEquals($asignacionActiva->id, $asignacion->id);
        $this->assertEquals('activo', $asignacion->estado);
    }

    public function test_conductor_can_assign_vehiculo(): void
    {
        $conductor = Conductor::factory()->create();
        $vehicle = Vehicle::factory()->create();

        // Asignar vehículo
        $asignacion = $conductor->asignarVehiculo($vehicle->id, 'Asignación inicial');

        $this->assertInstanceOf(ConductorVehicle::class, $asignacion);
        $this->assertEquals($conductor->id, $asignacion->conductor_id);
        $this->assertEquals($vehicle->id, $asignacion->vehicle_id);
        $this->assertEquals('activo', $asignacion->estado);
        $this->assertEquals('Asignación inicial', $asignacion->observaciones);
        $this->assertNotNull($asignacion->fecha_asignacion);

        // Verificar en la base de datos
        $this->assertDatabaseHas('conductor_vehicle', [
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehicle->id,
            'estado' => 'activo',
        ]);
    }

    public function test_conductor_assign_vehiculo_deactivates_previous(): void
    {
        $conductor = Conductor::factory()->create();
        $vehicle1 = Vehicle::factory()->create();
        $vehicle2 = Vehicle::factory()->create();

        // Asignar primer vehículo
        $asignacion1 = $conductor->asignarVehiculo($vehicle1->id);

        $this->assertEquals('activo', $asignacion1->estado);

        // Asignar segundo vehículo (debe desactivar el primero)
        $asignacion2 = $conductor->asignarVehiculo($vehicle2->id);

        // Verificar que la primera asignación fue desactivada
        $asignacion1->refresh();
        $this->assertEquals('inactivo', $asignacion1->estado);
        $this->assertNotNull($asignacion1->fecha_desasignacion);

        // Verificar que la segunda asignación está activa
        $this->assertEquals('activo', $asignacion2->estado);
        $this->assertEquals($vehicle2->id, $asignacion2->vehicle_id);

        // Verificar que solo hay una asignación activa
        $asignacionesActivas = ConductorVehicle::where('conductor_id', $conductor->id)
            ->where('estado', 'activo')
            ->count();

        $this->assertEquals(1, $asignacionesActivas, 'Solo debe haber una asignación activa');
    }

    public function test_conductor_can_unassign_vehiculo(): void
    {
        $conductor = Conductor::factory()->create();
        $vehicle = Vehicle::factory()->create();

        // Asignar vehículo primero
        $asignacion = $conductor->asignarVehiculo($vehicle->id, 'Asignación inicial');
        $this->assertEquals('activo', $asignacion->estado);

        // Desasignar vehículo
        $result = $conductor->desasignarVehiculo('Desasignación por cambio');

        $this->assertTrue($result, 'desasignarVehiculo debe retornar true cuando hay una asignación activa');

        // Verificar que la asignación fue desactivada
        $asignacion->refresh();
        $this->assertEquals('inactivo', $asignacion->estado);
        $this->assertNotNull($asignacion->fecha_desasignacion);
        $this->assertEquals('Desasignación por cambio', $asignacion->observaciones);

        // Verificar que no hay asignaciones activas
        $asignacionActiva = $conductor->asignacionActiva()->first();
        $this->assertNull($asignacionActiva, 'No debe haber asignaciones activas después de desasignar');
    }

    public function test_conductor_can_unassign_vehiculo_returns_false_when_no_active_assignment(): void
    {
        $conductor = Conductor::factory()->create();

        // Intentar desasignar sin tener asignación activa
        $result = $conductor->desasignarVehiculo();

        $this->assertFalse($result, 'desasignarVehiculo debe retornar false cuando no hay asignación activa');
    }

    public function test_conductor_fecha_nacimiento_is_casted_to_date(): void
    {
        $conductor = Conductor::factory()->create([
            'fecha_nacimiento' => '1990-05-15',
        ]);

        // Verificar que fecha_nacimiento es una instancia de Carbon/Date
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $conductor->fecha_nacimiento);
        $this->assertEquals('1990-05-15', $conductor->fecha_nacimiento->format('Y-m-d'));

        // Verificar que también funciona con DateTime
        $conductor2 = Conductor::factory()->create([
            'fecha_nacimiento' => new \DateTime('1985-03-20'),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $conductor2->fecha_nacimiento);
        $this->assertEquals('1985-03-20', $conductor2->fecha_nacimiento->format('Y-m-d'));
    }

    public function test_conductor_relevo_is_casted_to_boolean(): void
    {
        // Probar con true
        $conductor1 = Conductor::factory()->create([
            'relevo' => true,
        ]);

        $this->assertIsBool($conductor1->relevo);
        $this->assertTrue($conductor1->relevo);

        // Probar con false
        $conductor2 = Conductor::factory()->create([
            'relevo' => false,
        ]);

        $this->assertIsBool($conductor2->relevo);
        $this->assertFalse($conductor2->relevo);

        // Probar con 1 y 0 (valores comunes en BD)
        $conductor3 = Conductor::factory()->create([
            'relevo' => 1,
        ]);

        $this->assertIsBool($conductor3->relevo);
        $this->assertTrue($conductor3->relevo);

        $conductor4 = Conductor::factory()->create([
            'relevo' => 0,
        ]);

        $this->assertIsBool($conductor4->relevo);
        $this->assertFalse($conductor4->relevo);
    }
}
