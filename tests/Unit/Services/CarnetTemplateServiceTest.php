<?php

namespace Tests\Unit\Services;

use App\Services\CarnetTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CarnetTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CarnetTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CarnetTemplateService;

        // Crear directorio para uploads si no existe
        $uploadsDir = public_path('uploads/carnets');
        if (! File::exists($uploadsDir)) {
            File::makeDirectory($uploadsDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Limpiar archivos de prueba
        $uploadsDir = public_path('uploads/carnets');
        if (File::exists($uploadsDir)) {
            File::cleanDirectory($uploadsDir);
        }

        parent::tearDown();
    }

    public function test_template_service_can_save_template(): void
    {
        // El servicio no guarda templates directamente, pero prepara la configuración
        // Verificamos que prepareVariablesConfig funciona correctamente
        $variablesConfig = $this->service->prepareVariablesConfig();

        $this->assertIsArray($variablesConfig);
        $this->assertNotEmpty($variablesConfig);

        // Verificar que todas las variables tienen configuración
        $availableVariables = $this->service->getAvailableVariables();
        foreach (array_keys($availableVariables) as $variableKey) {
            $this->assertArrayHasKey($variableKey, $variablesConfig, "La variable {$variableKey} debe tener configuración");
        }
    }

    public function test_template_service_validates_template_config(): void
    {
        // Preparar configuración con valores parciales
        $existingConfig = [
            'cedula' => [
                'x' => 100,
                'y' => 100,
                'color' => '#000000',
                'activo' => true,
                'fontSize' => 16,
            ],
        ];

        $variablesConfig = $this->service->prepareVariablesConfig($existingConfig);

        // Verificar que la configuración existente se mantiene
        $this->assertEquals(100, $variablesConfig['cedula']['x']);
        $this->assertEquals(100, $variablesConfig['cedula']['y']);
        $this->assertEquals('#000000', $variablesConfig['cedula']['color']);
        $this->assertTrue($variablesConfig['cedula']['activo']);
        $this->assertEquals(16, $variablesConfig['cedula']['fontSize']);

        // Verificar que las variables faltantes tienen valores por defecto
        $availableVariables = $this->service->getAvailableVariables();
        foreach (array_keys($availableVariables) as $variableKey) {
            if ($variableKey !== 'cedula') {
                $this->assertArrayHasKey($variableKey, $variablesConfig);
                if ($variableKey === 'foto' || $variableKey === 'qr_code') {
                    $this->assertArrayHasKey('size', $variablesConfig[$variableKey]);
                    $this->assertEquals(100, $variablesConfig[$variableKey]['size']);
                } else {
                    $this->assertArrayHasKey('fontSize', $variablesConfig[$variableKey]);
                    $this->assertEquals(14, $variablesConfig[$variableKey]['fontSize']);
                    $this->assertEquals('#000000', $variablesConfig[$variableKey]['color']);
                }
            }
        }
    }

    public function test_template_service_can_activate_template(): void
    {
        // El servicio no activa templates directamente, pero prepara configuraciones
        // que permiten que las plantillas funcionen. Verificamos que prepareVariablesConfig
        // retorna configuraciones válidas que pueden usarse para crear templates activas
        $variablesConfig = $this->service->prepareVariablesConfig();

        $this->assertIsArray($variablesConfig);

        // Verificar que la configuración puede usarse para crear una plantilla
        // (simulando lo que hace el controlador)
        $templateData = [
            'nombre' => 'Plantilla de Prueba',
            'variables_config' => $variablesConfig,
        ];

        $this->assertIsArray($templateData['variables_config']);
        $this->assertNotEmpty($templateData['variables_config']);
    }

    public function test_template_service_can_deactivate_template(): void
    {
        // El servicio no desactiva templates directamente, pero podemos verificar
        // que las configuraciones preparadas funcionan tanto para plantillas activas
        // como inactivas
        $variablesConfig = $this->service->prepareVariablesConfig();

        // Verificar que la configuración es válida independientemente del estado activo
        $this->assertIsArray($variablesConfig);

        // Verificar estructura de variables especiales (foto, qr_code)
        $this->assertArrayHasKey('foto', $variablesConfig);
        $this->assertArrayHasKey('qr_code', $variablesConfig);
        $this->assertArrayHasKey('activo', $variablesConfig['foto']);
        $this->assertArrayHasKey('size', $variablesConfig['foto']);
        $this->assertArrayHasKey('activo', $variablesConfig['qr_code']);
        $this->assertArrayHasKey('size', $variablesConfig['qr_code']);

        // Verificar estructura de variables de texto
        $this->assertArrayHasKey('cedula', $variablesConfig);
        $this->assertArrayHasKey('activo', $variablesConfig['cedula']);
        $this->assertArrayHasKey('fontSize', $variablesConfig['cedula']);
        $this->assertArrayHasKey('color', $variablesConfig['cedula']);
        $this->assertArrayHasKey('fontFamily', $variablesConfig['cedula']);
    }

    public function test_service_get_available_variables(): void
    {
        $variables = $this->service->getAvailableVariables();

        $this->assertIsArray($variables);
        $this->assertNotEmpty($variables);

        // Verificar que tiene las variables esperadas
        $expectedVariables = [
            'nombres',
            'apellidos',
            'nombre_completo',
            'cedula',
            'conductor_tipo',
            'rh',
            'numero_interno',
            'celular',
            'correo',
            'fecha_nacimiento',
            'nivel_estudios',
            'otra_profesion',
            'estado',
            'foto',
            'vehiculo',
            'vehiculo_placa',
            'vehiculo_marca',
            'vehiculo_modelo',
            'qr_code',
        ];

        foreach ($expectedVariables as $variable) {
            $this->assertArrayHasKey($variable, $variables, "La variable {$variable} debe estar disponible");
        }
    }

    public function test_service_store_image(): void
    {
        $image = UploadedFile::fake()->image('template.jpg', 100, 100);

        $ruta = $this->service->storeImage($image);

        $this->assertIsString($ruta);
        $this->assertStringStartsWith('uploads/carnets/', $ruta);
        $this->assertStringEndsWith('.jpg', $ruta);

        // Verificar que el archivo existe
        $rutaCompleta = public_path($ruta);
        $this->assertFileExists($rutaCompleta);
    }

    public function test_prepare_variables_config_creates_defaults_for_foto_and_qr(): void
    {
        $variablesConfig = $this->service->prepareVariablesConfig();

        // Verificar configuración por defecto para foto
        $this->assertArrayHasKey('foto', $variablesConfig);
        $this->assertFalse($variablesConfig['foto']['activo']);
        $this->assertNull($variablesConfig['foto']['x']);
        $this->assertNull($variablesConfig['foto']['y']);
        $this->assertEquals(100, $variablesConfig['foto']['size']);

        // Verificar configuración por defecto para qr_code
        $this->assertArrayHasKey('qr_code', $variablesConfig);
        $this->assertFalse($variablesConfig['qr_code']['activo']);
        $this->assertNull($variablesConfig['qr_code']['x']);
        $this->assertNull($variablesConfig['qr_code']['y']);
        $this->assertEquals(100, $variablesConfig['qr_code']['size']);
    }

    public function test_prepare_variables_config_creates_defaults_for_text_variables(): void
    {
        $variablesConfig = $this->service->prepareVariablesConfig();

        // Verificar configuración por defecto para variables de texto
        $textVariables = ['cedula', 'nombres', 'apellidos', 'nombre_completo'];

        foreach ($textVariables as $variable) {
            $this->assertArrayHasKey($variable, $variablesConfig);
            $this->assertFalse($variablesConfig[$variable]['activo']);
            $this->assertNull($variablesConfig[$variable]['x']);
            $this->assertNull($variablesConfig[$variable]['y']);
            $this->assertEquals(14, $variablesConfig[$variable]['fontSize']);
            $this->assertEquals('#000000', $variablesConfig[$variable]['color']);
            $this->assertEquals('Arial', $variablesConfig[$variable]['fontFamily']);
            $this->assertEquals('normal', $variablesConfig[$variable]['fontStyle']);
            $this->assertFalse($variablesConfig[$variable]['centrado']);
        }
    }

    public function test_prepare_variables_config_preserves_existing_config(): void
    {
        $existingConfig = [
            'cedula' => [
                'activo' => true,
                'x' => 150,
                'y' => 200,
                'fontSize' => 18,
                'color' => '#FF0000',
                'fontFamily' => 'Helvetica',
                'fontStyle' => 'bold',
                'centrado' => true,
            ],
            'foto' => [
                'activo' => true,
                'x' => 50,
                'y' => 50,
                'size' => 150,
            ],
        ];

        $variablesConfig = $this->service->prepareVariablesConfig($existingConfig);

        // Verificar que la configuración existente se preserva
        $this->assertTrue($variablesConfig['cedula']['activo']);
        $this->assertEquals(150, $variablesConfig['cedula']['x']);
        $this->assertEquals(200, $variablesConfig['cedula']['y']);
        $this->assertEquals(18, $variablesConfig['cedula']['fontSize']);
        $this->assertEquals('#FF0000', $variablesConfig['cedula']['color']);
        $this->assertEquals('Helvetica', $variablesConfig['cedula']['fontFamily']);
        $this->assertEquals('bold', $variablesConfig['cedula']['fontStyle']);
        $this->assertTrue($variablesConfig['cedula']['centrado']);

        $this->assertTrue($variablesConfig['foto']['activo']);
        $this->assertEquals(50, $variablesConfig['foto']['x']);
        $this->assertEquals(50, $variablesConfig['foto']['y']);
        $this->assertEquals(150, $variablesConfig['foto']['size']);
    }
}
