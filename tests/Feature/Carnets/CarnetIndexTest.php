<?php

namespace Tests\Feature\Carnets;

use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarnetIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_view_carnets_index(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear algunos conductores
        Conductor::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/carnets');

        $response->assertStatus(200);
        $response->assertViewIs('carnets.index');
        $response->assertViewHas('template');
        $response->assertViewHas('conductores');
    }

    public function test_carnets_index_displays_active_template(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $templateActiva = CarnetTemplate::create([
            'nombre' => 'Plantilla Activa',
            'imagen_plantilla' => 'active.png',
            'variables_config' => ['variable1' => ['x' => 10, 'y' => 20]],
            'activo' => true,
        ]);

        // Crear una plantilla inactiva (no debería mostrarse)
        CarnetTemplate::create([
            'nombre' => 'Plantilla Inactiva',
            'imagen_plantilla' => 'inactive.png',
            'variables_config' => [],
            'activo' => false,
        ]);

        $response = $this->actingAs($user)->get('/carnets');

        $response->assertStatus(200);
        $templateEnVista = $response->viewData('template');
        $this->assertNotNull($templateEnVista);
        $this->assertEquals($templateActiva->id, $templateEnVista->id);
        $this->assertEquals('Plantilla Activa', $templateEnVista->nombre);
        $this->assertTrue($templateEnVista->activo);
    }

    public function test_carnets_index_displays_all_conductores(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear conductores de prueba
        $conductor1 = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        $conductor2 = Conductor::factory()->create([
            'cedula' => '0987654321',
            'nombres' => 'María',
            'apellidos' => 'González',
        ]);

        $conductor3 = Conductor::factory()->create([
            'cedula' => '1122334455',
            'nombres' => 'Carlos',
            'apellidos' => 'Rodríguez',
        ]);

        $response = $this->actingAs($user)->get('/carnets');

        $response->assertStatus(200);
        $conductoresEnVista = $response->viewData('conductores');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $conductoresEnVista);
        $this->assertGreaterThanOrEqual(3, $conductoresEnVista->count());

        // Verificar que todos los conductores están presentes
        $cedulas = $conductoresEnVista->pluck('cedula')->toArray();
        $this->assertContains('1234567890', $cedulas);
        $this->assertContains('0987654321', $cedulas);
        $this->assertContains('1122334455', $cedulas);
    }
}
