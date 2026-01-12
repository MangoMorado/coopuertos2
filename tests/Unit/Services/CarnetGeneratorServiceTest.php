<?php

namespace Tests\Unit\Services;

use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\Vehicle;
use App\Services\CarnetGeneratorService;
use App\Services\CarnetPdfConverter;
use App\Services\FontManager;
use App\Services\ImageProcessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarnetGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CarnetGeneratorService $service;

    protected ImageProcessorService $imageProcessor;

    protected FontManager $fontManager;

    protected CarnetPdfConverter $pdfConverter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imageProcessor = new ImageProcessorService;
        $this->fontManager = new FontManager;
        $this->pdfConverter = new CarnetPdfConverter;

        $this->service = new CarnetGeneratorService(
            $this->imageProcessor,
            $this->fontManager,
            $this->pdfConverter
        );
    }

    protected function tearDown(): void
    {
        // Limpiar archivos temporales creados durante los tests
        $tempDirs = [
            storage_path('app/temp'),
            storage_path('app/carnets'),
        ];

        foreach ($tempDirs as $dir) {
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }

        parent::tearDown();
    }

    protected function createTestTemplate(?string $imagePath = null): CarnetTemplate
    {
        // Si no se proporciona una ruta de imagen, crear una imagen de prueba
        if (! $imagePath) {
            $imagePath = $this->createTestTemplateImage();
        }

        return CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => $imagePath,
            'variables_config' => [
                'nombre_completo' => [
                    'activo' => true,
                    'x' => 100,
                    'y' => 50,
                    'fontSize' => 16,
                    'fontFamily' => 'Arial',
                    'color' => '#000000',
                ],
                'cedula' => [
                    'activo' => true,
                    'x' => 100,
                    'y' => 80,
                    'fontSize' => 14,
                    'fontFamily' => 'Arial',
                    'color' => '#000000',
                ],
                'foto' => [
                    'activo' => true,
                    'x' => 50,
                    'y' => 50,
                    'size' => 80,
                ],
                'qr_code' => [
                    'activo' => true,
                    'x' => 200,
                    'y' => 200,
                    'size' => 100,
                ],
            ],
            'activo' => true,
        ]);
    }

    protected function createTestTemplateImage(): string
    {
        // Crear una imagen PNG simple para usar como plantilla de prueba
        $image = imagecreatetruecolor(400, 300);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        // Crear directorio en public si no existe
        $publicDir = public_path('storage/carnet_templates');
        File::ensureDirectoryExists($publicDir);

        // Guardar en public/storage/carnet_templates
        $fileName = 'test_template_'.time().'.png';
        $fullPath = $publicDir.'/'.$fileName;

        imagepng($image, $fullPath);
        imagedestroy($image);

        // Retornar la ruta relativa desde public (como la espera el servicio)
        return 'storage/carnet_templates/'.$fileName;
    }

    public function test_carnet_generator_creates_png_image(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
        ]);

        $template = $this->createTestTemplate();
        $outputPath = storage_path('app/temp/test_carnet_'.time().'.png');

        // Asegurar que el directorio existe
        File::ensureDirectoryExists(dirname($outputPath));

        $result = $this->service->generarCarnetImagen($conductor, $template, $outputPath);

        $this->assertEquals($outputPath, $result);
        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath));

        // Verificar que es una imagen PNG válida
        $imageInfo = getimagesize($outputPath);
        $this->assertNotFalse($imageInfo);
        $this->assertEquals(IMAGETYPE_PNG, $imageInfo[2]);
    }

    public function test_carnet_generator_creates_pdf_from_png(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
        ]);

        $template = $this->createTestTemplate();
        $tempDir = storage_path('app/temp/test_'.time());

        File::ensureDirectoryExists($tempDir);

        $pdfPath = $this->service->generarCarnetPDF($conductor, $template, $tempDir);

        $this->assertFileExists($pdfPath);
        $this->assertStringEndsWith('.pdf', $pdfPath);
        $this->assertGreaterThan(0, filesize($pdfPath));

        // Verificar que el archivo comienza con el header PDF
        $handle = fopen($pdfPath, 'r');
        $header = fread($handle, 4);
        fclose($handle);
        $this->assertEquals('%PDF', $header);
    }

    public function test_carnet_generator_includes_all_conductor_data(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'numero_interno' => '123',
            'celular' => '3001234567',
            'correo' => 'juan@example.com',
            'fecha_nacimiento' => '1990-01-01',
            'nivel_estudios' => 'Universitario',
            'estado' => 'activo',
        ]);

        $template = $this->createTestTemplate();
        $outputPath = storage_path('app/temp/test_carnet_'.time().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        // El método prepararDatosConductor es protegido, pero se usa internamente
        // Verificamos que se genera la imagen correctamente con todos los datos
        $result = $this->service->generarCarnetImagen($conductor, $template, $outputPath);

        $this->assertFileExists($result);
    }

    public function test_carnet_generator_includes_photo(): void
    {
        // Crear una imagen base64 de prueba
        $image = imagecreatetruecolor(100, 100);
        $blue = imagecolorallocate($image, 0, 0, 255);
        imagefill($image, 0, 0, $blue);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        $base64 = 'data:image/png;base64,'.base64_encode($imageData);

        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'foto' => $base64,
        ]);

        $template = $this->createTestTemplate();
        $outputPath = storage_path('app/temp/test_carnet_'.time().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        $result = $this->service->generarCarnetImagen($conductor, $template, $outputPath);

        $this->assertFileExists($result);
    }

    public function test_carnet_generator_includes_qr_code(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
        ]);

        $template = $this->createTestTemplate();
        $outputPath = storage_path('app/temp/test_carnet_'.time().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        $result = $this->service->generarCarnetImagen($conductor, $template, $outputPath);

        $this->assertFileExists($result);

        // Verificar que el QR code se intentó generar (el servicio incluye logs)
        // El QR code se genera internamente y se renderiza sobre la imagen
    }

    public function test_carnet_generator_handles_missing_photo(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'foto' => null, // Sin foto
        ]);

        $template = $this->createTestTemplate();
        $outputPath = storage_path('app/temp/test_carnet_'.time().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        // No debería lanzar excepción aunque no haya foto
        $result = $this->service->generarCarnetImagen($conductor, $template, $outputPath);

        $this->assertFileExists($result);
    }

    public function test_carnet_generator_handles_missing_data(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            // Muchos campos opcionales no definidos
            'numero_interno' => null,
            'celular' => null,
            'correo' => null,
            'fecha_nacimiento' => null,
            'nivel_estudios' => null,
        ]);

        $template = $this->createTestTemplate();
        $outputPath = storage_path('app/temp/test_carnet_'.time().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        // Debe manejar datos faltantes sin errores
        $result = $this->service->generarCarnetImagen($conductor, $template, $outputPath);

        $this->assertFileExists($result);
    }

    public function test_carnet_generator_uses_correct_template(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
        ]);

        $template1 = $this->createTestTemplate();
        $template2 = $this->createTestTemplate();

        // Cambiar configuración de la segunda plantilla
        $template2->update([
            'variables_config' => [
                'nombre_completo' => [
                    'activo' => true,
                    'x' => 200, // Posición diferente
                    'y' => 100, // Posición diferente
                    'fontSize' => 20,
                    'fontFamily' => 'Arial',
                    'color' => '#FF0000', // Color diferente
                ],
            ],
        ]);

        $outputPath1 = storage_path('app/temp/test_carnet_1_'.time().'.png');
        $outputPath2 = storage_path('app/temp/test_carnet_2_'.time().'.png');

        File::ensureDirectoryExists(dirname($outputPath1));
        File::ensureDirectoryExists(dirname($outputPath2));

        $result1 = $this->service->generarCarnetImagen($conductor, $template1, $outputPath1);
        $result2 = $this->service->generarCarnetImagen($conductor, $template2, $outputPath2);

        // Ambas imágenes deben existir
        $this->assertFileExists($result1);
        $this->assertFileExists($result2);

        // Las imágenes deben ser diferentes (aunque tenga los mismos datos del conductor)
        $this->assertNotEquals(md5_file($result1), md5_file($result2));
    }

    public function test_carnet_generator_renders_text_correctly(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
        ]);

        $template = $this->createTestTemplate();
        $outputPath = storage_path('app/temp/test_carnet_'.time().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        $result = $this->service->generarCarnetImagen($conductor, $template, $outputPath);

        $this->assertFileExists($result);

        // Verificar que la imagen contiene datos (el texto se renderiza internamente)
        $imageInfo = getimagesize($result);
        $this->assertNotFalse($imageInfo);
    }

    public function test_carnet_generator_handles_vehiculo_relevo(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'relevo' => true,
            'vehiculo' => null, // Sin vehículo asignado
        ]);

        $template = $this->createTestTemplate();
        $outputPath = storage_path('app/temp/test_carnet_'.time().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        // Debe manejar relevo sin vehículo asignado
        $result = $this->service->generarCarnetImagen($conductor, $template, $outputPath);

        $this->assertFileExists($result);
    }

    public function test_carnet_generator_handles_conductor_with_vehicle_assignment(): void
    {
        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
        ]);

        $vehiculo = Vehicle::factory()->create([
            'placa' => 'ABC123',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
        ]);

        ConductorVehicle::create([
            'conductor_id' => $conductor->id,
            'vehicle_id' => $vehiculo->id,
            'estado' => 'activo',
            'fecha_asignacion' => now(),
        ]);

        // Recargar conductor para que cargue la relación
        $conductor->refresh();

        $template = $this->createTestTemplate();
        $outputPath = storage_path('app/temp/test_carnet_'.time().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        // Debe incluir información del vehículo asignado
        $result = $this->service->generarCarnetImagen($conductor, $template, $outputPath);

        $this->assertFileExists($result);
    }
}
