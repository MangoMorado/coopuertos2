<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Gestor de fuentes para renderizado de texto en imágenes
 *
 * Proporciona acceso a archivos de fuentes TTF para renderizar texto en carnets.
 * Busca fuentes en public/fonts y en el directorio de fuentes de Windows.
 * Incluye conversión de colores hexadecimales a RGB.
 */
class FontManager
{
    /**
     * Obtiene la ruta del archivo de fuente según la familia y estilo
     *
     * Busca archivos de fuente TTF en múltiples ubicaciones: primero en public/fonts,
     * luego en C:/Windows/Fonts. Normaliza el estilo de fuente y mapea familias
     * comunes a nombres de archivos. Retorna Arial como fallback si no se encuentra la fuente.
     *
     * @param  string  $fontFamily  Familia de fuente (Arial, Times New Roman, Century Gothic, etc.)
     * @param  string  $fontStyle  Estilo de fuente: 'normal', 'bold', 'italic', 'bold italic' (default: 'normal')
     * @return string|null Ruta completa al archivo de fuente TTF o null si no se encuentra ninguna
     */
    public function getFontPath(string $fontFamily, string $fontStyle = 'normal'): ?string
    {
        // Mapear familias de fuentes a nombres de archivos en Windows
        $fontMap = [
            'Arial' => ['regular' => 'arial.ttf', 'bold' => 'arialbd.ttf', 'italic' => 'ariali.ttf', 'bold italic' => 'arialbi.ttf'],
            'Helvetica' => ['regular' => 'arial.ttf', 'bold' => 'arialbd.ttf', 'italic' => 'ariali.ttf', 'bold italic' => 'arialbi.ttf'],
            'Times New Roman' => ['regular' => 'times.ttf', 'bold' => 'timesbd.ttf', 'italic' => 'timesi.ttf', 'bold italic' => 'timesbi.ttf'],
            'Courier New' => ['regular' => 'cour.ttf', 'bold' => 'courbd.ttf', 'italic' => 'couri.ttf', 'bold italic' => 'courbi.ttf'],
            'Verdana' => ['regular' => 'verdana.ttf', 'bold' => 'verdanab.ttf', 'italic' => 'verdanai.ttf', 'bold italic' => 'verdanaz.ttf'],
            'Century Gothic' => ['regular' => 'gothic.ttf', 'bold' => 'gothicb.ttf', 'italic' => 'gothici.ttf', 'bold italic' => 'gothicbi.ttf'],
        ];

        // Normalizar estilo
        $style = $this->normalizeFontStyle($fontStyle);

        // Obtener nombre de archivo según familia y estilo
        $fontFile = null;
        if (isset($fontMap[$fontFamily][$style])) {
            $fontFile = $fontMap[$fontFamily][$style];
        } elseif (isset($fontMap[$fontFamily]['regular'])) {
            $fontFile = $fontMap[$fontFamily]['regular'];
        }

        // Rutas posibles para las fuentes
        $possiblePaths = [];

        if ($fontFile) {
            // Primero buscar en public/fonts
            $possiblePaths[] = public_path("fonts/{$fontFile}");
            $possiblePaths[] = public_path('fonts/'.strtolower($fontFile));
            $possiblePaths[] = public_path('fonts/'.ucfirst($fontFile));

            // Luego buscar en Windows/Fonts
            $possiblePaths[] = 'C:/Windows/Fonts/'.$fontFile;
            $possiblePaths[] = 'C:/Windows/Fonts/'.strtolower($fontFile);
            $possiblePaths[] = 'C:/Windows/Fonts/'.ucfirst($fontFile);

            // También buscar con el nombre completo de la familia
            $possiblePaths[] = 'C:/Windows/Fonts/'.str_replace(' ', '', $fontFamily).($style !== 'regular' ? ' '.ucwords($style) : '').'.ttf';
        }

        // Buscar la primera fuente disponible
        foreach ($possiblePaths as $path) {
            if ($path && File::exists($path)) {
                return $path;
            }
        }

        // Si no se encuentra, intentar con Arial regular como fallback
        return $this->getArialFallback();
    }

    /**
     * Convierte un color hexadecimal a RGB
     *
     * Convierte un color en formato hexadecimal (#RRGGBB) a un array asociativo
     * con los componentes RGB (red, green, blue) para usar en funciones de GD.
     *
     * @param  string  $hex  Color hexadecimal con o sin prefijo # (ej: '#000000' o 'FF00FF')
     * @return array{r: int, g: int, b: int} Array con componentes RGB (valores de 0 a 255)
     */
    public function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Normaliza el estilo de fuente
     */
    protected function normalizeFontStyle(string $style): string
    {
        $style = strtolower($style);
        if ($style === 'regular' || $style === 'normal') {
            return 'regular';
        } elseif ($style === 'bold') {
            return 'bold';
        } elseif ($style === 'italic' || $style === 'Italic') {
            return 'italic';
        } elseif ($style === 'bold italic' || $style === 'Bold Italic') {
            return 'bold italic';
        }

        return 'regular';
    }

    /**
     * Intenta obtener Arial como fallback
     */
    protected function getArialFallback(): ?string
    {
        $arialPaths = [
            public_path('fonts/arial.ttf'),
            public_path('fonts/Arial.ttf'),
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/Arial.ttf',
            'C:/Windows/Fonts/ARIAL.TTF',
        ];

        foreach ($arialPaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
