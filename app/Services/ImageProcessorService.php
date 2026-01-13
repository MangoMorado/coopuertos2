<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de procesamiento de imágenes
 *
 * Proporciona funcionalidades para cargar imágenes desde diferentes fuentes
 * (rutas de archivo, base64, SVG), convertirlas a recursos GD y renderizar
 * SVG a imágenes GD para códigos QR.
 */
class ImageProcessorService
{
    /**
     * Carga una imagen desde una ruta de archivo o data URI base64
     *
     * Detecta automáticamente si el parámetro es una ruta de archivo o un
     * data URI base64. Soporta formatos JPEG, PNG, GIF y SVG. Para SVG
     * utiliza métodos especializados de renderizado.
     *
     * @param  string  $path  Ruta de archivo o data URI base64 (data:image/mime;base64,...)
     * @return \GdImage|false|null Recurso GD de la imagen, false si es SVG sin renderizar, o null si falla
     */
    public function loadImage(string $path)
    {
        // Si es base64 (data URI)
        if (str_starts_with($path, 'data:')) {
            return $this->loadImageFromBase64($path);
        }

        // Si es una ruta de archivo
        if (! File::exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'svg') {
            return $this->loadSvgAsImage($path);
        }

        $imageInfo = getimagesize($path);
        if (! $imageInfo) {
            return null;
        }
        $imageType = $imageInfo[2];

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return null;
        }
    }

    /**
     * Carga una imagen desde un string base64 (data URI)
     *
     * Extrae y decodifica el contenido base64 de un data URI, luego crea
     * un recurso GD desde los datos de imagen. Soporta data URIs con prefijo
     * (data:image/mime;base64,...) o strings base64 puros.
     *
     * @param  string  $base64String  Data URI base64 o string base64 puro
     * @return \GdImage|null Recurso GD de la imagen o null si falla la decodificación
     */
    public function loadImageFromBase64(string $base64String)
    {
        try {
            // Extraer el base64 del data URI
            if (preg_match('/^data:([^;]+);base64,(.+)$/', $base64String, $matches)) {
                $mimeType = $matches[1];
                $base64Data = $matches[2];
            } else {
                // Si no tiene el prefijo data:, asumir que es solo base64
                $base64Data = $base64String;
                $mimeType = 'image/jpeg'; // Por defecto
            }

            // Decodificar base64
            $imageData = base64_decode($base64Data, true);
            if ($imageData === false) {
                Log::warning('Error decodificando base64');

                return null;
            }

            // Crear imagen desde string usando imagecreatefromstring
            $image = @imagecreatefromstring($imageData);
            if ($image === false) {
                Log::warning('Error creando imagen desde base64');

                return null;
            }

            return $image;
        } catch (\Exception $e) {
            Log::error('Error cargando imagen desde base64: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Convierte un archivo SVG a una imagen GD
     *
     * Carga un archivo SVG, lo sanitiza y lo convierte a un recurso GD.
     * Intenta usar Imagick primero (mejor calidad), y si no está disponible
     * usa un método alternativo creando una imagen básica con dimensiones
     * extraídas del SVG.
     *
     * @param  string  $svgPath  Ruta completa al archivo SVG
     * @param  int|null  $width  Ancho deseado de la imagen (opcional)
     * @param  int|null  $height  Alto deseado de la imagen (opcional)
     * @return \GdImage|false Recurso GD de la imagen o false si falla
     */
    public function loadSvgAsImage(string $svgPath, ?int $width = null, ?int $height = null)
    {
        try {
            $svgContent = File::get($svgPath);

            // Sanitizar SVG (opcional, pero recomendado)
            try {
                $sanitizer = new \enshrined\svgSanitize\Sanitizer;
                $svgContent = $sanitizer->sanitize($svgContent);
            } catch (\Exception $e) {
                Log::warning('Error sanitizando SVG: '.$e->getMessage());
            }

            // Intentar usar Imagick si está disponible (mejor calidad)
            if (extension_loaded('imagick')) {
                try {
                    $imagick = new \Imagick;
                    $imagick->setBackgroundColor(new \ImagickPixel('white'));
                    $imagick->setResolution(300, 300);
                    $imagick->readImageBlob($svgContent);
                    $imagick->setImageFormat('png');

                    // Si se especifican dimensiones, redimensionar
                    if ($width && $height) {
                        $imagick->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
                    }

                    // Convertir Imagick a GD
                    $imageData = $imagick->getImageBlob();
                    $imagick->clear();
                    $imagick->destroy();

                    // Crear imagen GD desde los datos PNG
                    $image = imagecreatefromstring($imageData);

                    if ($image) {
                        Log::info("SVG renderizado con Imagick: {$width}x{$height}");

                        return $image;
                    }
                } catch (\Exception $e) {
                    Log::warning('Error usando Imagick para SVG: '.$e->getMessage());
                }
            }

            // Método alternativo: extraer dimensiones y crear imagen básica
            [$svgWidth, $svgHeight] = $this->extractSvgDimensions($svgContent, $width, $height);

            // Crear imagen con dimensiones correctas
            $image = imagecreatetruecolor((int) $svgWidth, (int) $svgHeight);

            // Habilitar transparencia para PNG
            imagealphablending($image, false);
            imagesavealpha($image, true);

            // Rellenar con fondo blanco
            $white = imagecolorallocatealpha($image, 255, 255, 255, 127);
            imagefill($image, 0, 0, $white);

            Log::info("SVG cargado con dimensiones: {$svgWidth}x{$svgHeight} (sin renderizado completo)");

            return $image;

        } catch (\Exception $e) {
            Log::error('Error cargando SVG: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return false;
        }
    }

    /**
     * Renderiza contenido SVG a imagen GD (específico para códigos QR)
     *
     * Renderiza un SVG en formato string a un recurso GD. Extrae el viewBox
     * del SVG, calcula factores de escala y renderiza rectángulos y paths.
     * Especializado para renderizar códigos QR generados como SVG.
     *
     * @param  string  $svgContent  Contenido SVG como string
     * @param  int  $size  Tamaño deseado de la imagen cuadrada resultante
     * @return \GdImage|null Recurso GD de la imagen renderizada o null si no se puede renderizar
     */
    public function renderSvgToGd(string $svgContent, int $size)
    {
        try {
            // Extraer el viewBox del SVG
            preg_match('/viewBox="([^"]*)"/i', $svgContent, $viewBoxMatch);
            $viewBox = $viewBoxMatch[1] ?? '0 0 200 200';
            $viewBoxParts = preg_split('/\s+/', trim($viewBox));
            $svgWidth = isset($viewBoxParts[2]) ? (float) $viewBoxParts[2] : 200;
            $svgHeight = isset($viewBoxParts[3]) ? (float) $viewBoxParts[3] : 200;

            Log::info("SVG viewBox: $viewBox, Width: $svgWidth, Height: $svgHeight, Target size: $size");

            // Crear imagen en blanco
            $image = imagecreatetruecolor($size, $size);
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            imagefill($image, 0, 0, $white);

            // Calcular factor de escala
            $scaleX = $size / $svgWidth;
            $scaleY = $size / $svgHeight;

            $rectsDrawn = $this->renderSvgRectangles($svgContent, $image, $black, $scaleX, $scaleY);

            // Si no se encontraron rectángulos, intentar con paths
            if ($rectsDrawn === 0) {
                $rectsDrawn = $this->renderSvgPaths($svgContent, $image, $black, $scaleX, $scaleY);
            }

            Log::info("Dibujados $rectsDrawn elementos en la imagen QR");

            if ($rectsDrawn > 0) {
                return $image;
            } else {
                imagedestroy($image);
                Log::warning('No se dibujaron elementos en el QR. Primeros 500 caracteres del SVG: '.substr($svgContent, 0, 500));

                return null;
            }
        } catch (\Exception $e) {
            Log::warning('Error en renderSvgToGd: '.$e->getMessage());
            Log::warning('Stack trace: '.$e->getTraceAsString());
        }

        return null;
    }

    /**
     * Extrae las dimensiones de un SVG
     */
    protected function extractSvgDimensions(string $svgContent, ?int $width = null, ?int $height = null): array
    {
        $svgWidth = $width;
        $svgHeight = $height;

        // Intentar obtener dimensiones del viewBox
        preg_match('/viewBox=["\']?\s*([0-9.\-\s]+)\s*["\']?/i', $svgContent, $viewBoxMatch);
        if (isset($viewBoxMatch[1])) {
            $viewBoxParts = preg_split('/[\s,]+/', trim($viewBoxMatch[1]));
            if (count($viewBoxParts) >= 4) {
                $svgWidth = (float) $viewBoxParts[2];
                $svgHeight = (float) $viewBoxParts[3];
            }
        }

        // Si no hay viewBox, intentar obtener de atributos width/height
        if (! $svgWidth || ! $svgHeight) {
            preg_match('/width=["\']?\s*([0-9.]+)(px)?\s*["\']?/i', $svgContent, $widthMatch);
            preg_match('/height=["\']?\s*([0-9.]+)(px)?\s*["\']?/i', $svgContent, $heightMatch);

            if (isset($widthMatch[1])) {
                $svgWidth = (float) $widthMatch[1];
            }
            if (isset($heightMatch[1])) {
                $svgHeight = (float) $heightMatch[1];
            }
        }

        // Valores por defecto si no se pueden determinar
        if (! $svgWidth || ! $svgHeight) {
            $svgWidth = 800;
            $svgHeight = 600;
            Log::warning("No se pudieron determinar dimensiones del SVG, usando valores por defecto: {$svgWidth}x{$svgHeight}");
        }

        return [(int) $svgWidth, (int) $svgHeight];
    }

    /**
     * Renderiza rectángulos de un SVG
     */
    protected function renderSvgRectangles(string $svgContent, $image, int $black, float $scaleX, float $scaleY): int
    {
        preg_match_all('/<rect[^>]*>/i', $svgContent, $rectMatches);

        if (empty($rectMatches[0])) {
            return 0;
        }

        $rectsDrawn = 0;

        foreach ($rectMatches[0] as $rectTag) {
            preg_match('/x="([^"]*)"/i', $rectTag, $xMatch);
            preg_match('/y="([^"]*)"/i', $rectTag, $yMatch);
            preg_match('/width="([^"]*)"/i', $rectTag, $wMatch);
            preg_match('/height="([^"]*)"/i', $rectTag, $hMatch);
            preg_match('/fill="([^"]*)"/i', $rectTag, $fillMatch);

            if (isset($xMatch[1]) && isset($yMatch[1]) && isset($wMatch[1]) && isset($hMatch[1])) {
                $x = (float) $xMatch[1];
                $y = (float) $yMatch[1];
                $w = (float) $wMatch[1];
                $h = (float) $hMatch[1];
                $fill = isset($fillMatch[1]) ? strtolower(trim($fillMatch[1])) : 'black';

                if (empty($fill)) {
                    preg_match('/fill="([^"]*)"/i', $svgContent, $defaultFill);
                    $fill = isset($defaultFill[1]) ? strtolower(trim($defaultFill[1])) : 'black';
                }

                $xScaled = (int) ($x * $scaleX);
                $yScaled = (int) ($y * $scaleY);
                $wScaled = max(1, (int) ($w * $scaleX));
                $hScaled = max(1, (int) ($h * $scaleY));

                $isBlack = ($fill === '#000000' || $fill === 'black' || $fill === '#000' ||
                           $fill === 'rgb(0,0,0)' || $fill === 'none' || empty($fill));

                if ($isBlack) {
                    imagefilledrectangle($image, $xScaled, $yScaled, $xScaled + $wScaled - 1, $yScaled + $hScaled - 1, $black);
                    $rectsDrawn++;
                }
            }
        }

        return $rectsDrawn;
    }

    /**
     * Renderiza paths de un SVG
     */
    protected function renderSvgPaths(string $svgContent, $image, int $black, float $scaleX, float $scaleY): int
    {
        Log::info('No se encontraron rectángulos, buscando paths en el SVG');

        $scale = 1.0;
        $translateX = 0;
        $translateY = 0;

        preg_match('/<g[^>]*transform="[^"]*scale\(([^)]+)\)[^"]*"/i', $svgContent, $scaleMatch);
        if (isset($scaleMatch[1])) {
            $scale = (float) $scaleMatch[1];
        }

        preg_match('/<g[^>]*transform="[^"]*translate\(([^,]+),([^)]+)\)[^"]*"/i', $svgContent, $translateMatch);
        if (isset($translateMatch[1]) && isset($translateMatch[2])) {
            $translateX = (float) $translateMatch[1];
            $translateY = (float) $translateMatch[2];
        }

        preg_match_all('/<path[^>]*d="([^"]*)"[^>]*>/i', $svgContent, $pathMatches);

        $rectsDrawn = 0;

        foreach ($pathMatches[1] as $pathData) {
            $pathData = preg_replace('/([MLHVZ])([-\d.])/', '$1 $2', $pathData);
            $pathData = preg_replace('/([-\d.])([MLHVZ])/', '$1 $2', $pathData);
            $pathData = preg_replace('/([-\d.])([-\d.])/', '$1 $2', $pathData);

            preg_match_all('/([MLHVZ])\s*([-\d.\s]+)/i', $pathData, $commands);

            $currentX = 0;
            $currentY = 0;
            $polygonPoints = [];

            $drawPolygon = function ($points) use ($image, $black, $scaleX, $scaleY, &$rectsDrawn) {
                if (count($points) >= 3) {
                    $xCoords = array_column($points, 0);
                    $yCoords = array_column($points, 1);
                    $minX = min($xCoords);
                    $minY = min($yCoords);
                    $maxX = max($xCoords);
                    $maxY = max($yCoords);

                    $xScaled = (int) ($minX * $scaleX);
                    $yScaled = (int) ($minY * $scaleY);
                    $wScaled = max(1, (int) (($maxX - $minX) * $scaleX));
                    $hScaled = max(1, (int) (($maxY - $minY) * $scaleY));

                    imagefilledrectangle($image, $xScaled, $yScaled, $xScaled + $wScaled - 1, $yScaled + $hScaled - 1, $black);
                    $rectsDrawn++;

                    return true;
                }

                return false;
            };

            for ($i = 0; $i < count($commands[0]); $i++) {
                $cmd = strtoupper($commands[1][$i]);
                $coordsStr = trim($commands[2][$i]);
                $coords = preg_split('/[\s,]+/', $coordsStr);
                $coords = array_filter($coords, fn ($v) => $v !== '' && $v !== ' ');
                $coords = array_values($coords);

                if ($cmd === 'M' && count($coords) >= 2) {
                    if (count($polygonPoints) >= 3) {
                        $drawPolygon($polygonPoints);
                    }
                    $currentX = ((float) $coords[0] * $scale) + $translateX;
                    $currentY = ((float) $coords[1] * $scale) + $translateY;
                    $polygonPoints = [[$currentX, $currentY]];
                } elseif ($cmd === 'L' && count($coords) >= 2) {
                    for ($j = 0; $j < count($coords) - 1; $j += 2) {
                        if (isset($coords[$j]) && isset($coords[$j + 1])) {
                            $currentX = ((float) $coords[$j] * $scale) + $translateX;
                            $currentY = ((float) $coords[$j + 1] * $scale) + $translateY;
                            $polygonPoints[] = [$currentX, $currentY];
                        }
                    }
                } elseif ($cmd === 'H' && count($coords) >= 1) {
                    $currentX = ((float) $coords[0] * $scale) + $translateX;
                    $polygonPoints[] = [$currentX, $currentY];
                } elseif ($cmd === 'V' && count($coords) >= 1) {
                    $currentY = ((float) $coords[0] * $scale) + $translateY;
                    $polygonPoints[] = [$currentX, $currentY];
                } elseif ($cmd === 'Z') {
                    $drawPolygon($polygonPoints);
                    $polygonPoints = [];
                }
            }

            if (count($polygonPoints) >= 3) {
                $drawPolygon($polygonPoints);
            }
        }

        return $rectsDrawn;
    }
}
