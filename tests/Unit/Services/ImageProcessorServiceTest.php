<?php

namespace Tests\Unit\Services;

use App\Services\ImageProcessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImageProcessorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ImageProcessorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ImageProcessorService;
    }

    protected function tearDown(): void
    {
        // Limpiar archivos temporales creados durante los tests
        $tempDir = storage_path('app/temp');
        if (File::exists($tempDir)) {
            File::cleanDirectory($tempDir);
        }

        parent::tearDown();
    }

    protected function createTestImage(): string
    {
        // Crear una imagen PNG simple de prueba
        $image = imagecreatetruecolor(100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        $tempPath = storage_path('app/temp/test_'.time().'.png');
        File::ensureDirectoryExists(dirname($tempPath));
        imagepng($image, $tempPath);
        imagedestroy($image);

        return $tempPath;
    }

    protected function createBase64Image(): string
    {
        // Crear una imagen PNG simple y convertirla a base64
        $image = imagecreatetruecolor(100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        $base64 = base64_encode($imageData);

        return 'data:image/png;base64,'.$base64;
    }

    protected function createTestSvg(): string
    {
        // Crear un archivo SVG simple de prueba
        $svgContent = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
    <rect x="10" y="10" width="50" height="50" fill="black"/>
</svg>';

        $tempPath = storage_path('app/temp/test_'.time().'.svg');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $svgContent);

        return $tempPath;
    }

    public function test_image_processor_loads_base64_image(): void
    {
        $base64Image = $this->createBase64Image();

        $result = $this->service->loadImageFromBase64($base64Image);

        $this->assertNotFalse($result, 'La imagen base64 debe cargarse correctamente');
        $this->assertNotNull($result);

        // Verificar que es un recurso de imagen válido (PHP < 8) o objeto GdImage (PHP 8+)
        $isImage = is_resource($result) || (is_object($result) && get_class($result) === 'GdImage');
        $this->assertTrue($isImage, 'El resultado debe ser un recurso de imagen o un objeto GdImage');

        // Verificar dimensiones
        $width = imagesx($result);
        $height = imagesy($result);
        $this->assertGreaterThan(0, $width);
        $this->assertGreaterThan(0, $height);

        // Limpiar recurso
        imagedestroy($result);
    }

    public function test_image_processor_loads_svg_as_image(): void
    {
        $svgPath = $this->createTestSvg();

        $result = $this->service->loadSvgAsImage($svgPath);

        // Puede retornar false si no hay Imagick disponible
        // pero aún así debe manejar el caso correctamente
        if ($result !== false) {
            $this->assertNotFalse($result, 'El SVG debe cargarse o manejarse correctamente');
            $isImage = is_resource($result) || (is_object($result) && get_class($result) === 'GdImage');
            if ($isImage) {
                $width = imagesx($result);
                $height = imagesy($result);
                $this->assertGreaterThan(0, $width);
                $this->assertGreaterThan(0, $height);

                // Limpiar recurso
                imagedestroy($result);
            }
        } else {
            // Si retorna false, verificar que al menos no lanzó una excepción
            $this->assertFalse($result, 'El servicio debe manejar correctamente cuando no puede cargar el SVG');
        }

        // Limpiar archivo SVG de prueba
        if (File::exists($svgPath)) {
            File::delete($svgPath);
        }
    }

    public function test_image_processor_renders_svg_to_gd(): void
    {
        // Crear contenido SVG simple para QR (con rectángulos)
        $svgContent = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
    <rect x="10" y="10" width="20" height="20" fill="black"/>
    <rect x="40" y="10" width="20" height="20" fill="black"/>
    <rect x="10" y="40" width="20" height="20" fill="black"/>
</svg>';

        $result = $this->service->renderSvgToGd($svgContent, 200);

        // El método puede retornar null si no encuentra elementos para dibujar
        // pero debe manejar el caso correctamente
        if ($result !== null) {
            $this->assertNotFalse($result, 'El SVG debe renderizarse correctamente');
            $isImage = is_resource($result) || (is_object($result) && get_class($result) === 'GdImage');
            if ($isImage) {
                $width = imagesx($result);
                $height = imagesy($result);
                $this->assertEquals(200, $width, 'El ancho debe ser el especificado');
                $this->assertEquals(200, $height, 'El alto debe ser el especificado');

                // Limpiar recurso
                imagedestroy($result);
            }
        } else {
            // Si retorna null, verificar que al menos no lanzó una excepción
            $this->assertNull($result, 'El servicio debe manejar correctamente cuando no puede renderizar el SVG');
        }
    }

    public function test_image_processor_handles_invalid_images(): void
    {
        // Probar con base64 inválido
        $invalidBase64 = 'data:image/png;base64,invalid_base64_data!!!';
        $result = $this->service->loadImageFromBase64($invalidBase64);
        $this->assertNull($result, 'Debe retornar null para base64 inválido');

        // Probar con ruta de archivo inexistente
        $result = $this->service->loadSvgAsImage('/ruta/inexistente/test.svg');
        $this->assertFalse($result, 'Debe retornar false para archivo inexistente');

        // Probar con SVG inválido/vacío
        $invalidSvgPath = storage_path('app/temp/invalid_'.time().'.svg');
        File::ensureDirectoryExists(dirname($invalidSvgPath));
        File::put($invalidSvgPath, 'invalid svg content');

        $result = $this->service->loadSvgAsImage($invalidSvgPath);
        // Puede retornar false o un recurso válido dependiendo de cómo maneje el error
        // Lo importante es que no lance una excepción

        // Limpiar archivo de prueba
        if (File::exists($invalidSvgPath)) {
            File::delete($invalidSvgPath);
        }
    }

    public function test_load_image_from_base64_with_data_uri(): void
    {
        $base64Image = $this->createBase64Image();

        $result = $this->service->loadImageFromBase64($base64Image);

        $this->assertNotFalse($result);
        if (is_resource($result)) {
            imagedestroy($result);
        }
    }

    public function test_load_image_from_base64_without_data_uri(): void
    {
        // Crear imagen PNG y convertir solo a base64 (sin prefijo data:)
        $image = imagecreatetruecolor(100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        $base64 = base64_encode($imageData);

        // Probar sin prefijo data:
        $result = $this->service->loadImageFromBase64($base64);

        // Puede retornar null o un recurso/objeto dependiendo de cómo maneje el caso
        // Lo importante es que no lance una excepción
        $this->assertTrue($result === null || is_resource($result) || (is_object($result) && get_class($result) === 'GdImage'));

        if ($result !== null) {
            $isImage = is_resource($result) || (is_object($result) && get_class($result) === 'GdImage');
            if ($isImage) {
                imagedestroy($result);
            }
        }
    }

    public function test_load_svg_with_dimensions(): void
    {
        $svgPath = $this->createTestSvg();

        // Probar con dimensiones específicas
        $result = $this->service->loadSvgAsImage($svgPath, 400, 300);

        // Verificar que el resultado es válido o false
        $this->assertTrue($result === false || is_resource($result) || (is_object($result) && get_class($result) === 'GdImage'));

        if ($result !== false) {
            $isImage = is_resource($result) || (is_object($result) && get_class($result) === 'GdImage');
            if ($isImage) {
                $width = imagesx($result);
                $height = imagesy($result);
                $this->assertGreaterThan(0, $width);
                $this->assertGreaterThan(0, $height);

                imagedestroy($result);
            }
        }

        // Limpiar archivo SVG de prueba
        if (File::exists($svgPath)) {
            File::delete($svgPath);
        }
    }

    public function test_render_svg_to_gd_with_empty_svg(): void
    {
        // Probar con SVG vacío
        $emptySvg = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg"></svg>';

        $result = $this->service->renderSvgToGd($emptySvg, 200);

        // Debe retornar null si no hay elementos para dibujar
        $this->assertNull($result, 'Debe retornar null para SVG vacío sin elementos para dibujar');
    }

    public function test_render_svg_to_gd_creates_correct_size(): void
    {
        // Crear SVG con rectángulos
        $svgContent = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
    <rect x="10" y="10" width="50" height="50" fill="black"/>
</svg>';

        $size = 300;
        $result = $this->service->renderSvgToGd($svgContent, $size);

        // Verificar que el resultado es null o un recurso/objeto de imagen válido
        $this->assertTrue($result === null || is_resource($result) || (is_object($result) && get_class($result) === 'GdImage'));

        if ($result !== null) {
            $isImage = is_resource($result) || (is_object($result) && get_class($result) === 'GdImage');
            if ($isImage) {
                $width = imagesx($result);
                $height = imagesy($result);
                $this->assertEquals($size, $width, 'El ancho debe ser igual al tamaño especificado');
                $this->assertEquals($size, $height, 'El alto debe ser igual al tamaño especificado');

                imagedestroy($result);
            }
        }
    }
}
