<?php

namespace Tests\Unit\Models;

use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_has_conductores_relationship(): void
    {
        $vehicle = Vehicle::factory()->create();
        $conductor1 = Conductor::factory()->create();
        $conductor2 = Conductor::factory()->create();

        // Crear asignaciones
        ConductorVehicle::create([
            'conductor_id' => $conductor1->id,
            'vehicle_id' => $vehicle->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        ConductorVehicle::create([
            'conductor_id' => $conductor2->id,
            'vehicle_id' => $vehicle->id,
            'estado' => 'inactivo',
            'fecha_asignacion' => now()->subDays(10),
            'fecha_desasignacion' => now()->subDays(5),
        ]);

        // Verificar relación
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $vehicle->conductores());
        $this->assertCount(2, $vehicle->conductores);
        $this->assertTrue($vehicle->conductores->contains($conductor1));
        $this->assertTrue($vehicle->conductores->contains($conductor2));
    }

    public function test_vehicle_has_conductores_activos_method(): void
    {
        $vehicle = Vehicle::factory()->create();
        $conductorActivo = Conductor::factory()->create();
        $conductorInactivo = Conductor::factory()->create();

        // Crear asignación activa
        ConductorVehicle::create([
            'conductor_id' => $conductorActivo->id,
            'vehicle_id' => $vehicle->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        // Crear asignación inactiva
        ConductorVehicle::create([
            'conductor_id' => $conductorInactivo->id,
            'vehicle_id' => $vehicle->id,
            'estado' => 'inactivo',
            'fecha_asignacion' => now()->subDays(10),
            'fecha_desasignacion' => now()->subDays(5),
        ]);

        // Verificar que conductoresActivos() retorna solo los conductores activos
        $conductoresActivos = $vehicle->conductoresActivos();

        $this->assertCount(1, $conductoresActivos);
        $this->assertTrue($conductoresActivos->contains($conductorActivo));
        $this->assertFalse($conductoresActivos->contains($conductorInactivo));
    }

    public function test_vehicle_has_asignaciones_relationship(): void
    {
        $vehicle = Vehicle::factory()->create();
        $conductor1 = Conductor::factory()->create();
        $conductor2 = Conductor::factory()->create();

        // Crear asignaciones
        $asignacion1 = ConductorVehicle::create([
            'conductor_id' => $conductor1->id,
            'vehicle_id' => $vehicle->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        $asignacion2 = ConductorVehicle::create([
            'conductor_id' => $conductor2->id,
            'vehicle_id' => $vehicle->id,
            'estado' => 'inactivo',
            'fecha_asignacion' => now()->subDays(10),
            'fecha_desasignacion' => now()->subDays(5),
        ]);

        // Verificar relación
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $vehicle->asignaciones());
        $this->assertCount(2, $vehicle->asignaciones);
        $this->assertTrue($vehicle->asignaciones->contains($asignacion1));
        $this->assertTrue($vehicle->asignaciones->contains($asignacion2));
    }

    public function test_vehicle_has_conductor_relationship(): void
    {
        $conductor = Conductor::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'conductor_id' => $conductor->id,
        ]);

        // Verificar relación belongsTo
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $vehicle->conductor());

        // Verificar que se puede acceder al conductor
        $this->assertNotNull($vehicle->conductor);
        $this->assertEquals($conductor->id, $vehicle->conductor->id);

        // Verificar vehículo sin conductor
        $vehicleSinConductor = Vehicle::factory()->create([
            'conductor_id' => null,
        ]);

        $this->assertNull($vehicleSinConductor->conductor);
    }
}
