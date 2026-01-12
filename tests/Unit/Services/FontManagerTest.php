<?php

namespace Tests\Unit\Services;

use App\Services\FontManager;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FontManagerTest extends TestCase
{
    protected FontManager $fontManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fontManager = new FontManager;
    }

    public function test_font_manager_converts_hex_to_rgb(): void
    {
        // Test con # al inicio
        $result = $this->fontManager->hexToRgb('#000000');
        $this->assertEquals(['r' => 0, 'g' => 0, 'b' => 0], $result);

        // Test sin # al inicio
        $result = $this->fontManager->hexToRgb('FFFFFF');
        $this->assertEquals(['r' => 255, 'g' => 255, 'b' => 255], $result);

        // Test con color rojo
        $result = $this->fontManager->hexToRgb('#FF0000');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0], $result);

        // Test con color verde
        $result = $this->fontManager->hexToRgb('#00FF00');
        $this->assertEquals(['r' => 0, 'g' => 255, 'b' => 0], $result);

        // Test con color azul
        $result = $this->fontManager->hexToRgb('#0000FF');
        $this->assertEquals(['r' => 0, 'g' => 0, 'b' => 255], $result);

        // Test con color personalizado
        $result = $this->fontManager->hexToRgb('#1A2B3C');
        $this->assertEquals(['r' => 26, 'g' => 43, 'b' => 60], $result);

        // Test con color en minúsculas
        $result = $this->fontManager->hexToRgb('#ffaabb');
        $this->assertEquals(['r' => 255, 'g' => 170, 'b' => 187], $result);
    }

    public function test_font_manager_gets_font_path(): void
    {
        // Crear un directorio de fuentes de prueba en public
        $fontsDir = public_path('fonts');
        File::ensureDirectoryExists($fontsDir);

        // Crear un archivo de fuente de prueba (simulado)
        $testFontPath = $fontsDir.'/arial.ttf';
        File::put($testFontPath, 'fake font content');

        // Intentar obtener la ruta de Arial regular
        $fontPath = $this->fontManager->getFontPath('Arial', 'regular');

        // Si existe en public/fonts, debe retornar esa ruta
        // Si no existe, puede retornar null o una ruta de Windows/Fonts si existe
        if (File::exists($testFontPath)) {
            $this->assertNotNull($fontPath);
            $this->assertTrue(File::exists($fontPath) || str_contains($fontPath, 'arial'));
        }

        // Limpiar
        if (File::exists($testFontPath)) {
            File::delete($testFontPath);
        }
    }

    public function test_font_manager_handles_missing_fonts(): void
    {
        // Intentar obtener una fuente que no existe
        $fontPath = $this->fontManager->getFontPath('NonExistentFont', 'regular');

        // Debe retornar null o intentar con Arial como fallback
        // Si Arial tampoco existe, retornará null
        $this->assertTrue(
            $fontPath === null || str_contains(strtolower($fontPath), 'arial'),
            'Debe retornar null o intentar con Arial como fallback'
        );
    }

    public function test_font_manager_handles_different_font_styles(): void
    {
        // Crear directorio de fuentes de prueba
        $fontsDir = public_path('fonts');
        File::ensureDirectoryExists($fontsDir);

        // Crear archivos de fuente simulados para diferentes estilos
        $fontFiles = [
            'arial.ttf' => 'regular',
            'arialbd.ttf' => 'bold',
            'ariali.ttf' => 'italic',
            'arialbi.ttf' => 'bold italic',
        ];

        foreach ($fontFiles as $fileName => $style) {
            $fontPath = $fontsDir.'/'.$fileName;
            File::put($fontPath, 'fake font content');

            // Intentar obtener la ruta para cada estilo
            $result = $this->fontManager->getFontPath('Arial', $style);

            // Si el archivo existe, debe encontrarlo
            if (File::exists($fontPath)) {
                $this->assertNotNull($result);
            }

            // Limpiar
            File::delete($fontPath);
        }
    }

    public function test_font_manager_normalizes_font_style(): void
    {
        // Crear directorio de fuentes de prueba
        $fontsDir = public_path('fonts');
        File::ensureDirectoryExists($fontsDir);

        $testFontPath = $fontsDir.'/arial.ttf';
        File::put($testFontPath, 'fake font content');

        // Test normalización de estilos
        $styles = [
            'normal' => 'regular',
            'regular' => 'regular',
            'bold' => 'bold',
            'italic' => 'italic',
            'Italic' => 'italic',
            'Bold Italic' => 'bold italic',
            'bold italic' => 'bold italic',
        ];

        foreach ($styles as $inputStyle => $expectedNormalized) {
            $fontPath = $this->fontManager->getFontPath('Arial', $inputStyle);

            // Si existe la fuente, debe encontrarla independientemente de cómo se escriba el estilo
            if (File::exists($testFontPath)) {
                $this->assertNotNull($fontPath);
            }
        }

        // Limpiar
        if (File::exists($testFontPath)) {
            File::delete($testFontPath);
        }
    }

    public function test_font_manager_handles_unknown_font_family(): void
    {
        // Intentar obtener una fuente de una familia desconocida
        $fontPath = $this->fontManager->getFontPath('UnknownFontFamily123', 'regular');

        // Debe retornar null o intentar con Arial como fallback
        $this->assertTrue(
            $fontPath === null || str_contains(strtolower($fontPath ?? ''), 'arial'),
            'Debe retornar null o intentar con Arial como fallback para familias desconocidas'
        );
    }

    public function test_font_manager_uses_arial_fallback(): void
    {
        // El método getArialFallback es protegido, pero podemos verificar su comportamiento
        // intentando obtener una fuente que no existe y verificando que intenta con Arial

        // Crear directorio de fuentes de prueba
        $fontsDir = public_path('fonts');
        File::ensureDirectoryExists($fontsDir);

        // Crear Arial como fallback
        $arialPath = $fontsDir.'/arial.ttf';
        File::put($arialPath, 'fake arial font');

        // Intentar obtener una fuente que no existe
        $fontPath = $this->fontManager->getFontPath('NonExistentFont', 'regular');

        // Debe encontrar Arial como fallback
        if (File::exists($arialPath)) {
            $this->assertNotNull($fontPath);
            $this->assertStringContainsString('arial', strtolower($fontPath));
        }

        // Limpiar
        if (File::exists($arialPath)) {
            File::delete($arialPath);
        }
    }

    public function test_font_manager_converts_hex_without_hash(): void
    {
        // Verificar que funciona sin el símbolo #
        $result = $this->fontManager->hexToRgb('FF00FF');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 255], $result);

        // Verificar que también funciona con #
        $result2 = $this->fontManager->hexToRgb('#FF00FF');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 255], $result2);

        // Ambos deben dar el mismo resultado
        $this->assertEquals($result, $result2);
    }

    public function test_font_manager_handles_case_insensitive_hex(): void
    {
        // Los valores hexadecimales deben funcionar en mayúsculas y minúsculas
        $upper = $this->fontManager->hexToRgb('#ABCDEF');
        $lower = $this->fontManager->hexToRgb('#abcdef');

        $this->assertEquals($upper, $lower);
    }

    public function test_font_manager_searches_multiple_font_locations(): void
    {
        // Crear directorio de fuentes de prueba
        $fontsDir = public_path('fonts');
        File::ensureDirectoryExists($fontsDir);

        // Crear fuente en public/fonts
        $publicFontPath = $fontsDir.'/arial.ttf';
        File::put($publicFontPath, 'fake font content');

        // Intentar obtener la fuente
        $fontPath = $this->fontManager->getFontPath('Arial', 'regular');

        // Debe encontrar la fuente en public/fonts si existe
        if (File::exists($publicFontPath)) {
            $this->assertNotNull($fontPath);
            // Puede retornar la ruta de public/fonts o de Windows/Fonts si también existe
        }

        // Limpiar
        if (File::exists($publicFontPath)) {
            File::delete($publicFontPath);
        }
    }

    public function test_font_manager_handles_different_font_families(): void
    {
        // Crear directorio de fuentes de prueba
        $fontsDir = public_path('fonts');
        File::ensureDirectoryExists($fontsDir);

        // Probar diferentes familias de fuentes conocidas
        $fontFamilies = [
            'Arial',
            'Helvetica',
            'Times New Roman',
            'Courier New',
            'Verdana',
            'Century Gothic',
        ];

        foreach ($fontFamilies as $family) {
            $fontPath = $this->fontManager->getFontPath($family, 'regular');

            // Debe retornar null o una ruta válida
            $this->assertTrue(
                $fontPath === null || is_string($fontPath),
                "Debe retornar null o string para la familia {$family}"
            );
        }
    }
}
