<?php

namespace Tests\Unit\Models;

use App\Models\CarnetTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarnetTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_carnet_template_has_correct_structure(): void
    {
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla Test',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [
                'nombre' => ['x' => 100, 'y' => 200],
                'cedula' => ['x' => 100, 'y' => 250],
            ],
            'activo' => true,
        ]);

        // Verificar campos básicos
        $this->assertEquals('Plantilla Test', $template->nombre);
        $this->assertEquals('test.png', $template->imagen_plantilla);
        $this->assertTrue($template->activo);

        // Verificar casts
        $this->assertIsArray($template->variables_config);
        $this->assertIsBool($template->activo);
        $this->assertArrayHasKey('nombre', $template->variables_config);
        $this->assertArrayHasKey('cedula', $template->variables_config);
    }

    public function test_carnet_template_can_be_activated(): void
    {
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla Inactiva',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => false,
        ]);

        // Verificar que está inactiva
        $this->assertFalse($template->activo);

        // Activar
        $template->update(['activo' => true]);
        $template->refresh();

        // Verificar que está activa
        $this->assertTrue($template->activo);
    }

    public function test_carnet_template_can_be_deactivated(): void
    {
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla Activa',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Verificar que está activa
        $this->assertTrue($template->activo);

        // Desactivar
        $template->update(['activo' => false]);
        $template->refresh();

        // Verificar que está inactiva
        $this->assertFalse($template->activo);
    }

    public function test_carnet_template_variables_config_is_casted_to_array(): void
    {
        $variablesConfig = [
            'nombre' => ['x' => 100, 'y' => 200],
            'cedula' => ['x' => 100, 'y' => 250],
            'foto' => ['x' => 50, 'y' => 50, 'width' => 100, 'height' => 100],
        ];

        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla Test',
            'imagen_plantilla' => 'test.png',
            'variables_config' => $variablesConfig,
            'activo' => true,
        ]);

        // Verificar que variables_config es un array
        $this->assertIsArray($template->variables_config);
        $this->assertEquals($variablesConfig, $template->variables_config);

        // Verificar que se puede acceder a las propiedades
        $this->assertArrayHasKey('nombre', $template->variables_config);
        $this->assertArrayHasKey('cedula', $template->variables_config);
        $this->assertArrayHasKey('foto', $template->variables_config);
    }
}
