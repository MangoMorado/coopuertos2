<?php

namespace Tests\Feature\Carnets;

use App\Models\CarnetTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CarnetTemplateCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear directorio para imágenes de plantilla si no existe
        $publicStorageDir = public_path('storage/carnet_templates');
        if (! File::exists($publicStorageDir)) {
            File::makeDirectory($publicStorageDir, 0755, true);
        }

        // Crear directorio para uploads si no existe
        $uploadsDir = public_path('uploads/carnets');
        if (! File::exists($uploadsDir)) {
            File::makeDirectory($uploadsDir, 0755, true);
        }
    }

    public function test_user_can_view_template_customization_page(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla Principal',
            'imagen_plantilla' => 'carnet_templates/test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get('/carnets/personalizar');

        $response->assertStatus(200);
        $response->assertViewIs('carnets.personalizar');
        $response->assertViewHas('template');
        $response->assertViewHas('variables');
        $response->assertViewHas('variablesConfig');
    }

    public function test_user_can_save_template_configuration(): void
    {
        $user = User::factory()->create();

        // Crear plantilla anterior (será desactivada)
        $templateAnterior = CarnetTemplate::create([
            'nombre' => 'Plantilla Anterior',
            'imagen_plantilla' => 'carnet_templates/test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        $variablesConfig = [
            'cedula' => [
                'x' => 100,
                'y' => 100,
                'color' => '#000000',
                'activo' => true,
                'fontSize' => 14,
            ],
            'nombres' => [
                'x' => 100,
                'y' => 150,
                'color' => '#000000',
                'activo' => true,
                'fontSize' => 14,
            ],
        ];

        $response = $this->actingAs($user)->post('/carnets/guardar-plantilla', [
            'nombre' => 'Nueva Plantilla',
            'variables_config' => json_encode($variablesConfig),
        ]);

        $response->assertRedirect(route('carnets.index'));
        $response->assertSessionHas('success', 'Plantilla de carnet guardada correctamente.');

        // Verificar que se creó la nueva plantilla
        $nuevaPlantilla = CarnetTemplate::where('nombre', 'Nueva Plantilla')->first();
        $this->assertNotNull($nuevaPlantilla);
        $this->assertTrue($nuevaPlantilla->activo);
        $this->assertEquals($variablesConfig, $nuevaPlantilla->variables_config);

        // Verificar que la plantilla anterior fue desactivada
        $templateAnterior->refresh();
        $this->assertFalse($templateAnterior->activo);
    }

    public function test_template_configuration_validates_correctly(): void
    {
        $user = User::factory()->create();

        // Intentar guardar sin variables_config (requerido)
        $response = $this->actingAs($user)->post('/carnets/guardar-plantilla', [
            'nombre' => 'Plantilla Sin Config',
        ]);

        $response->assertSessionHasErrors(['variables_config']);

        // Verificar que no se creó ninguna plantilla
        $this->assertDatabaseCount('carnet_templates', 0);
    }

    public function test_template_can_be_activated(): void
    {
        $user = User::factory()->create();

        // Crear plantilla inactiva
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla Inactiva',
            'imagen_plantilla' => 'carnet_templates/test.png',
            'variables_config' => [],
            'activo' => false,
        ]);

        $variablesConfig = [
            'cedula' => [
                'x' => 100,
                'y' => 100,
                'color' => '#000000',
                'activo' => true,
                'fontSize' => 14,
            ],
        ];

        // Al guardar una nueva plantilla, se activa automáticamente
        $response = $this->actingAs($user)->post('/carnets/guardar-plantilla', [
            'nombre' => 'Nueva Plantilla Activa',
            'variables_config' => json_encode($variablesConfig),
        ]);

        $response->assertRedirect(route('carnets.index'));

        // Verificar que la nueva plantilla está activa
        $nuevaPlantilla = CarnetTemplate::where('nombre', 'Nueva Plantilla Activa')->first();
        $this->assertNotNull($nuevaPlantilla);
        $this->assertTrue($nuevaPlantilla->activo);
    }

    public function test_saving_template_deactivates_previous_active_template(): void
    {
        $user = User::factory()->create();

        // Crear plantilla activa
        $templateAnterior = CarnetTemplate::create([
            'nombre' => 'Plantilla Anterior Activa',
            'imagen_plantilla' => 'carnet_templates/test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        $variablesConfig = [
            'cedula' => [
                'x' => 100,
                'y' => 100,
                'color' => '#000000',
                'activo' => true,
                'fontSize' => 14,
            ],
        ];

        // Guardar nueva plantilla
        $response = $this->actingAs($user)->post('/carnets/guardar-plantilla', [
            'nombre' => 'Nueva Plantilla',
            'variables_config' => json_encode($variablesConfig),
        ]);

        $response->assertRedirect(route('carnets.index'));

        // Verificar que la plantilla anterior fue desactivada
        $templateAnterior->refresh();
        $this->assertFalse($templateAnterior->activo, 'La plantilla anterior debe estar desactivada');

        // Verificar que solo hay una plantilla activa
        $plantillasActivas = CarnetTemplate::where('activo', true)->count();
        $this->assertEquals(1, $plantillasActivas, 'Debe haber solo una plantilla activa');
    }

    public function test_template_configuration_accepts_valid_image_upload(): void
    {
        $user = User::factory()->create();

        $image = UploadedFile::fake()->image('template.jpg', 100, 100);

        $variablesConfig = [
            'cedula' => [
                'x' => 100,
                'y' => 100,
                'color' => '#000000',
                'activo' => true,
                'fontSize' => 14,
            ],
        ];

        $response = $this->actingAs($user)->post('/carnets/guardar-plantilla', [
            'nombre' => 'Plantilla con Imagen',
            'imagen_plantilla' => $image,
            'variables_config' => json_encode($variablesConfig),
        ]);

        $response->assertRedirect(route('carnets.index'));
        $response->assertSessionHas('success');

        // Verificar que se creó la plantilla
        $plantilla = CarnetTemplate::where('nombre', 'Plantilla con Imagen')->first();
        $this->assertNotNull($plantilla);
        $this->assertNotNull($plantilla->imagen_plantilla);
        $this->assertStringStartsWith('uploads/carnets/', $plantilla->imagen_plantilla);
    }
}
