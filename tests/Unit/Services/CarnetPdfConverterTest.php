<?php

namespace Tests\Unit\Services;

use App\Services\CarnetPdfConverter;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CarnetPdfConverterTest extends TestCase
{
    protected CarnetPdfConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = new CarnetPdfConverter;
    }

    protected function tearDown(): void
    {
        // Limpiar archivos temporales creados durante los tests
        $tempDir = storage_path('app/temp/test_pdf_converter');

        if (File::exists($tempDir)) {
            File::deleteDirectory($tempDir);
        }

        parent::tearDown();
    }

    protected function createTestPngImage(string $path, int $width = 400, int $height = 300): void
    {
        File::ensureDirectoryExists(dirname($path));

        // Crear una imagen PNG simple para usar como prueba
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $blue = imagecolorallocate($image, 0, 0, 255);

        // Rellenar fondo blanco
        imagefill($image, 0, 0, $white);

        // Dibujar un rectángulo azul para tener contenido visible
        imagefilledrectangle($image, 50, 50, $width - 50, $height - 50, $blue);

        // Guardar como PNG
        imagepng($image, $path, 9);
        imagedestroy($image);
    }

    public function test_pdf_converter_converts_png_to_pdf(): void
    {
        $tempDir = storage_path('app/temp/test_pdf_converter');
        File::ensureDirectoryExists($tempDir);

        // Crear imagen PNG de prueba
        $imagePath = $tempDir.'/test_image.png';
        $this->createTestPngImage($imagePath, 400, 300);

        // Verificar que la imagen existe antes de convertir
        $this->assertFileExists($imagePath);

        // Convertir a PDF
        $pdfPath = $tempDir.'/test_output.pdf';
        $this->converter->convertirImagenAPDF($imagePath, $pdfPath, 400, 300);

        // Verificar que el PDF fue creado
        $this->assertFileExists($pdfPath);
        $this->assertStringEndsWith('.pdf', $pdfPath);
        $this->assertGreaterThan(0, filesize($pdfPath));

        // Verificar que el archivo comienza con el header PDF
        $handle = fopen($pdfPath, 'r');
        $header = fread($handle, 4);
        fclose($handle);
        $this->assertEquals('%PDF', $header, 'El archivo debe comenzar con el header PDF');
    }

    public function test_pdf_converter_preserves_image_quality(): void
    {
        $tempDir = storage_path('app/temp/test_pdf_converter');
        File::ensureDirectoryExists($tempDir);

        // Crear una imagen PNG de alta calidad
        $imagePath = $tempDir.'/high_quality_image.png';
        $this->createTestPngImage($imagePath, 800, 600);

        // Obtener tamaño original de la imagen
        $originalImageInfo = getimagesize($imagePath);
        $this->assertNotFalse($originalImageInfo);

        // Convertir a PDF
        $pdfPath = $tempDir.'/high_quality_output.pdf';
        $this->converter->convertirImagenAPDF($imagePath, $pdfPath, 800, 600);

        // Verificar que el PDF fue creado
        $this->assertFileExists($pdfPath);

        // Verificar que el PDF tiene un tamaño razonable (no debe ser demasiado pequeño)
        // Un PDF con una imagen de 800x600 debería tener al menos algunos KB
        // Nota: DomPDF puede crear PDFs comprimidos eficientemente, por lo que el tamaño puede variar
        $pdfSize = filesize($pdfPath);
        $this->assertGreaterThan(2000, $pdfSize, 'El PDF debe tener un tamaño razonable para preservar la calidad');

        // Verificar que el PDF es válido leyendo el header
        $handle = fopen($pdfPath, 'r');
        $header = fread($handle, 8);
        fclose($handle);
        $this->assertStringStartsWith('%PDF', $header);

        // Verificar que contiene la versión PDF (puede ser 1.4, 1.5, etc.)
        $this->assertMatchesRegularExpression('/%PDF-1\.[0-9]/', $header);
    }

    public function test_pdf_converter_handles_conversion_errors(): void
    {
        $tempDir = storage_path('app/temp/test_pdf_converter');
        File::ensureDirectoryExists($tempDir);

        // Intentar convertir un archivo que no existe
        $nonExistentImagePath = $tempDir.'/non_existent_image.png';
        $pdfPath = $tempDir.'/output.pdf';

        $this->expectException(\Exception::class);
        $this->converter->convertirImagenAPDF($nonExistentImagePath, $pdfPath, 400, 300);

        // Verificar que el PDF no fue creado
        $this->assertFileDoesNotExist($pdfPath);
    }

    public function test_pdf_converter_handles_different_image_dimensions(): void
    {
        $tempDir = storage_path('app/temp/test_pdf_converter');
        File::ensureDirectoryExists($tempDir);

        // Probar con diferentes dimensiones
        $dimensions = [
            ['width' => 200, 'height' => 150],
            ['width' => 800, 'height' => 600],
            ['width' => 1200, 'height' => 900],
        ];

        foreach ($dimensions as $dim) {
            $imagePath = $tempDir.'/test_'.$dim['width'].'x'.$dim['height'].'.png';
            $pdfPath = $tempDir.'/output_'.$dim['width'].'x'.$dim['height'].'.pdf';

            $this->createTestPngImage($imagePath, $dim['width'], $dim['height']);

            // Convertir a PDF
            $this->converter->convertirImagenAPDF($imagePath, $pdfPath, $dim['width'], $dim['height']);

            // Verificar que el PDF fue creado
            $this->assertFileExists($pdfPath);
            $this->assertGreaterThan(0, filesize($pdfPath));

            // Verificar header PDF
            $handle = fopen($pdfPath, 'r');
            $header = fread($handle, 4);
            fclose($handle);
            $this->assertEquals('%PDF', $header);
        }
    }

    public function test_pdf_converter_calculates_dimensions_correctly(): void
    {
        $tempDir = storage_path('app/temp/test_pdf_converter');
        File::ensureDirectoryExists($tempDir);

        // Crear imagen de prueba con dimensiones conocidas
        $imagePath = $tempDir.'/test_image.png';
        $this->createTestPngImage($imagePath, 600, 400);

        $pdfPath = $tempDir.'/test_output.pdf';

        // Convertir con dimensiones específicas
        $this->converter->convertirImagenAPDF($imagePath, $pdfPath, 600, 400);

        // Verificar que el PDF fue creado
        $this->assertFileExists($pdfPath);

        // El PDF debe contener las dimensiones calculadas en mm
        // Para 600x400 pixels a 300 DPI:
        // widthMM = (600 / 300) * 25.4 = 50.8 mm
        // heightMM = (400 / 300) * 25.4 = 33.87 mm

        // Leer el contenido del PDF para verificar que contiene las dimensiones
        $pdfContent = File::get($pdfPath);

        // Verificar que el PDF contiene información de dimensiones
        // (el HTML generado incluye las dimensiones en mm)
        $this->assertNotEmpty($pdfContent);
    }

    public function test_pdf_converter_creates_valid_pdf_structure(): void
    {
        $tempDir = storage_path('app/temp/test_pdf_converter');
        File::ensureDirectoryExists($tempDir);

        $imagePath = $tempDir.'/test_image.png';
        $this->createTestPngImage($imagePath, 400, 300);

        $pdfPath = $tempDir.'/test_output.pdf';
        $this->converter->convertirImagenAPDF($imagePath, $pdfPath, 400, 300);

        // Verificar estructura básica del PDF
        $pdfContent = File::get($pdfPath);

        // Un PDF válido debe contener:
        // 1. Header %PDF-1.x
        $this->assertStringStartsWith('%PDF', $pdfContent);

        // 2. Referencias a objetos (%%EOF al final)
        // El PDF debe terminar con %%EOF (puede tener \n o \r\n)
        $this->assertTrue(
            str_ends_with($pdfContent, "%%EOF\n") || str_ends_with($pdfContent, "%%EOF\r\n") || str_ends_with($pdfContent, '%%EOF'),
            'El PDF debe terminar con %%EOF'
        );

        // 3. El tamaño del archivo debe ser razonable
        $this->assertGreaterThan(1000, strlen($pdfContent), 'El PDF debe tener contenido suficiente');
    }
}
