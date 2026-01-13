<?php

namespace App\Services;

use App\Models\CarnetTemplate;
use App\Models\Conductor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de generación de carnets para conductores
 *
 * Genera carnets en formato PNG (previsualización) y PDF (final) para conductores.
 * Renderiza variables personalizables sobre plantillas de imagen, incluyendo texto,
 * fotos y códigos QR. Maneja la conversión de formatos de imagen y PDF.
 */
class CarnetGeneratorService
{
    /**
     * @param  ImageProcessorService  $imageProcessor  Procesador de imágenes
     * @param  FontManager  $fontManager  Gestor de fuentes
     * @param  CarnetPdfConverter  $pdfConverter  Convertidor PNG a PDF
     */
    public function __construct(
        protected ImageProcessorService $imageProcessor,
        protected FontManager $fontManager,
        protected CarnetPdfConverter $pdfConverter
    ) {}

    /**
     * Genera una imagen PNG del carnet (sin convertir a PDF) para previsualización
     *
     * Crea una imagen PNG del carnet renderizando todas las variables configuradas
     * sobre la plantilla. Incluye texto, foto del conductor y código QR. Crea y
     * limpia automáticamente directorios temporales para recursos del QR.
     *
     * @param  Conductor  $conductor  Conductor para el cual generar el carnet
     * @param  CarnetTemplate  $template  Plantilla de carnet a utilizar
     * @param  string  $outputPath  Ruta completa donde guardar la imagen PNG generada
     * @return string Ruta completa del archivo PNG generado (igual a $outputPath)
     *
     * @throws \Exception Si no se puede cargar la plantilla o renderizar las variables
     */
    public function generarCarnetImagen(Conductor $conductor, CarnetTemplate $template, string $outputPath): string
    {
        // Preparar datos del conductor
        $datosConductor = $this->prepararDatosConductor($conductor);

        // Cargar imagen de plantilla
        $templateImage = $this->cargarImagenPlantilla($template);

        $width = imagesx($templateImage);
        $height = imagesy($templateImage);

        // Crear directorio temporal para procesamiento (para QR y otros recursos)
        $outputDir = dirname($outputPath);
        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        // Crear directorio temporal específico para recursos del QR
        $tempDir = storage_path('app/temp/preview_'.$conductor->id.'_'.time());
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        try {
            // Renderizar variables sobre la imagen
            $this->renderizarVariables($templateImage, $template->variables_config, $datosConductor, $conductor, $tempDir, $width);

            // Guardar imagen como PNG
            imagepng($templateImage, $outputPath, 9);
            imagedestroy($templateImage);
        } finally {
            // Limpiar directorio temporal de recursos
            if (File::exists($tempDir)) {
                try {
                    File::deleteDirectory($tempDir);
                } catch (\Exception $e) {
                    Log::warning('Error limpiando directorio temporal de previsualización: '.$e->getMessage());
                }
            }
        }

        return $outputPath;
    }

    /**
     * Genera un PDF de carnet para un conductor
     *
     * Crea una imagen PNG del carnet, la convierte a PDF y la guarda en el directorio
     * temporal. Si la conversión a PDF falla, retorna el PNG como fallback. Elimina
     * automáticamente el PNG intermedio después de la conversión exitosa.
     *
     * @param  Conductor  $conductor  Conductor para el cual generar el carnet
     * @param  CarnetTemplate  $template  Plantilla de carnet a utilizar
     * @param  string  $tempDir  Directorio temporal donde guardar el PDF generado
     * @return string Ruta completa del archivo PDF generado
     *
     * @throws \Exception Si no se puede generar la imagen PNG o cargar la plantilla
     */
    public function generarCarnetPDF(Conductor $conductor, CarnetTemplate $template, string $tempDir): string
    {
        // Preparar datos del conductor
        $datosConductor = $this->prepararDatosConductor($conductor);

        // Cargar imagen de plantilla
        $templateImage = $this->cargarImagenPlantilla($template);

        $width = imagesx($templateImage);
        $height = imagesy($templateImage);

        // Renderizar variables sobre la imagen
        $this->renderizarVariables($templateImage, $template->variables_config, $datosConductor, $conductor, $tempDir, $width);

        // Guardar imagen temporal como PNG
        $imagePath = $tempDir.'/carnet_'.$conductor->cedula.'.png';
        imagepng($templateImage, $imagePath, 9);
        imagedestroy($templateImage);

        // Convertir PNG a PDF
        $pdfPath = $tempDir.'/carnet_'.$conductor->cedula.'.pdf';
        try {
            $this->pdfConverter->convertirImagenAPDF($imagePath, $pdfPath, $width, $height);
            if (File::exists($pdfPath) && File::exists($imagePath)) {
                File::delete($imagePath);
            }

            return $pdfPath;
        } catch (\Exception $e) {
            Log::warning('Error convirtiendo a PDF para conductor '.$conductor->cedula.', usando PNG: '.$e->getMessage());
            $pngAsPdf = $tempDir.'/carnet_'.$conductor->cedula.'.pdf';
            File::copy($imagePath, $pngAsPdf);
            File::delete($imagePath);

            return $pngAsPdf;
        }
    }

    /**
     * Prepara los datos del conductor en formato para renderizar en el carnet
     *
     * Transforma los datos del conductor y su vehículo asignado en un array
     * estructurado con todos los campos disponibles para renderizar en la plantilla.
     * Incluye información del vehículo activo, foto en base64, y URL del código QR.
     *
     * @param  Conductor  $conductor  Conductor del cual extraer los datos
     * @return array<string, mixed> Array con todos los datos disponibles para el carnet (nombres, cedula, vehiculo, qr_code, etc.)
     */
    protected function prepararDatosConductor(Conductor $conductor): array
    {
        $vehiculo = $conductor->asignacionActiva && $conductor->asignacionActiva->vehicle
            ? $conductor->asignacionActiva->vehicle
            : null;

        // La foto es base64
        $fotoBase64 = $conductor->foto;

        return [
            'nombres' => $conductor->nombres,
            'apellidos' => $conductor->apellidos,
            'nombre_completo' => $conductor->nombres.' '.$conductor->apellidos,
            'cedula' => $conductor->cedula,
            'conductor_tipo' => $conductor->conductor_tipo,
            'rh' => $conductor->rh,
            'numero_interno' => $conductor->numero_interno ?? '',
            'celular' => $conductor->celular ?? '',
            'correo' => $conductor->correo ?? '',
            'fecha_nacimiento' => $conductor->fecha_nacimiento ? $conductor->fecha_nacimiento->format('d/m/Y') : '',
            'nivel_estudios' => $conductor->nivel_estudios ?? '',
            'otra_profesion' => $conductor->otra_profesion ?? '',
            'estado' => ucfirst($conductor->estado),
            'foto' => $fotoBase64,
            'vehiculo' => $conductor->vehiculo ? (string) $conductor->vehiculo : 'Relevo',
            'vehiculo_placa' => $vehiculo ? $vehiculo->placa : 'Sin asignar',
            'vehiculo_marca' => $vehiculo ? $vehiculo->marca : '',
            'vehiculo_modelo' => $vehiculo ? $vehiculo->modelo : '',
            'qr_code' => route('conductor.public', $conductor->uuid),
        ];
    }

    /**
     * Carga la imagen de plantilla como recurso GD
     *
     * Carga la imagen de plantilla desde el directorio público y la convierte
     * a un recurso GD (GdImage). Soporta múltiples formatos: JPEG, PNG, GIF y SVG.
     * Los SVG se procesan mediante ImageProcessorService.
     *
     * @param  CarnetTemplate  $template  Plantilla de carnet con la ruta de la imagen
     * @return \GdImage Recurso GD de la imagen de plantilla
     *
     * @throws \Exception Si no se encuentra la imagen, no se puede leer o el formato no es soportado
     */
    protected function cargarImagenPlantilla(CarnetTemplate $template)
    {
        $templateImagePath = public_path($template->imagen_plantilla);

        if (! File::exists($templateImagePath)) {
            throw new \Exception('No se encontró la imagen de plantilla');
        }

        $extension = strtolower(pathinfo($templateImagePath, PATHINFO_EXTENSION));

        if ($extension === 'svg') {
            $templateImage = $this->imageProcessor->loadSvgAsImage($templateImagePath);
            if (! $templateImage) {
                throw new \Exception('No se pudo cargar la imagen SVG');
            }

            return $templateImage;
        }

        $imageInfo = getimagesize($templateImagePath);
        if (! $imageInfo) {
            throw new \Exception('No se pudo leer la imagen de plantilla');
        }
        $imageType = $imageInfo[2];

        return match ($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($templateImagePath),
            IMAGETYPE_PNG => imagecreatefrompng($templateImagePath),
            IMAGETYPE_GIF => imagecreatefromgif($templateImagePath),
            default => throw new \Exception('Formato de imagen no soportado'),
        };
    }

    /**
     * Renderiza todas las variables configuradas sobre la imagen del carnet
     *
     * Itera sobre la configuración de variables y renderiza cada una según su tipo:
     * - Foto: Renderiza la foto del conductor desde base64
     * - QR Code: Genera y renderiza el código QR
     * - Texto: Renderiza campos de texto con formato personalizado
     *
     * @param  \GdImage  $templateImage  Recurso GD de la imagen de plantilla
     * @param  array<string, array<string, mixed>>  $variablesConfig  Configuración de variables desde la plantilla (x, y, activo, size, etc.)
     * @param  array<string, mixed>  $datosConductor  Datos del conductor (retornado por prepararDatosConductor)
     * @param  Conductor  $conductor  Conductor para generar QR y logs
     * @param  string  $tempDir  Directorio temporal para recursos del QR
     * @param  int  $width  Ancho de la imagen de plantilla (para centrado de texto)
     */
    protected function renderizarVariables($templateImage, array $variablesConfig, array $datosConductor, Conductor $conductor, string $tempDir, int $width): void
    {
        foreach ($variablesConfig as $key => $config) {
            if (isset($config['activo']) && $config['activo'] && isset($config['x']) && isset($config['y'])) {
                $value = $datosConductor[$key] ?? '';

                if ($key === 'foto' && $datosConductor['foto']) {
                    $this->renderizarFoto($templateImage, $datosConductor['foto'], $config);
                } elseif ($key === 'qr_code') {
                    $this->renderizarQR($templateImage, $datosConductor['qr_code'], $config, $conductor, $tempDir);
                } elseif ($key !== 'foto' && $value !== '') {
                    $this->renderizarTexto($templateImage, $value, $config, $width);
                }
            }
        }
    }

    /**
     * Renderiza la foto del conductor desde base64
     */
    protected function renderizarFoto($templateImage, string $fotoBase64, array $config): void
    {
        $fotoSize = $config['size'] ?? 100;

        // Debe ser base64
        if (! str_starts_with($fotoBase64, 'data:')) {
            return;
        }

        $fotoImg = $this->imageProcessor->loadImage($fotoBase64);
        if ($fotoImg) {
            imagecopyresampled(
                $templateImage, $fotoImg,
                $config['x'], $config['y'], 0, 0,
                $fotoSize, $fotoSize,
                imagesx($fotoImg), imagesy($fotoImg)
            );
            imagedestroy($fotoImg);
        }
    }

    /**
     * Renderiza el código QR usando la misma lógica que el frontend
     */
    protected function renderizarQR($templateImage, string $qrData, array $config, Conductor $conductor, string $tempDir): void
    {
        $qrSize = $config['size'] ?? 100;
        try {
            // Usar la misma generación que el frontend
            $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size($qrSize)
                ->format('svg')
                ->generate($qrData);

            // Guardar SVG temporal para debug
            $qrTempPathSvg = $tempDir.'/qr_'.$conductor->id.'.svg';
            File::put($qrTempPathSvg, $qrCodeSvg);

            // Intentar renderizar con Imagick primero (mejor calidad)
            $qrImage = null;
            if (extension_loaded('imagick')) {
                try {
                    $imagick = new \Imagick;
                    $imagick->setBackgroundColor(new \ImagickPixel('white'));
                    $imagick->setResolution(300, 300);
                    $imagick->readImageBlob($qrCodeSvg);
                    $imagick->setImageFormat('png');
                    $imagick->resizeImage($qrSize, $qrSize, \Imagick::FILTER_LANCZOS, 1, true);

                    // Convertir Imagick a GD
                    $imageData = $imagick->getImageBlob();
                    $imagick->clear();
                    $imagick->destroy();

                    $qrImage = imagecreatefromstring($imageData);
                    Log::info('QR renderizado con Imagick para conductor '.$conductor->id);
                } catch (\Exception $e) {
                    Log::warning('Error usando Imagick para QR: '.$e->getMessage());
                }
            }

            // Si Imagick falló, intentar con el método alternativo
            if (! $qrImage) {
                $qrImage = $this->imageProcessor->renderSvgToGd($qrCodeSvg, $qrSize);
                if ($qrImage) {
                    Log::info('QR renderizado con método alternativo para conductor '.$conductor->id);
                }
            }

            if ($qrImage) {
                // Asegurar que las dimensiones sean correctas
                $srcWidth = imagesx($qrImage);
                $srcHeight = imagesy($qrImage);

                if ($srcWidth > 0 && $srcHeight > 0) {
                    imagecopyresampled(
                        $templateImage, $qrImage,
                        $config['x'], $config['y'], 0, 0,
                        $qrSize, $qrSize,
                        $srcWidth, $srcHeight
                    );
                    imagedestroy($qrImage);
                    Log::info('QR dibujado exitosamente en carnet para conductor '.$conductor->id);
                } else {
                    Log::warning('Dimensiones inválidas del QR para conductor '.$conductor->id.': '.$srcWidth.'x'.$srcHeight);
                    imagedestroy($qrImage);
                }
            } else {
                Log::warning('No se pudo renderizar SVG QR para conductor '.$conductor->id.'. SVG guardado en: '.$qrTempPathSvg);
            }
        } catch (\Exception $e) {
            Log::error('Error generando QR para conductor '.$conductor->id.': '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());
        }
    }

    /**
     * Renderiza texto sobre la imagen
     */
    protected function renderizarTexto($templateImage, string $value, array $config, int $width): void
    {
        $fontSize = $config['fontSize'] ?? 14;
        $fontFamily = $config['fontFamily'] ?? 'Arial';
        $fontStyle = $config['fontStyle'] ?? 'normal';
        $color = $this->fontManager->hexToRgb($config['color'] ?? '#000000');
        $textColor = imagecolorallocate($templateImage, $color['r'], $color['g'], $color['b']);

        $fontPath = $this->fontManager->getFontPath($fontFamily, $fontStyle);

        $x = $config['x'];
        if (isset($config['centrado']) && $config['centrado']) {
            if ($fontPath && File::exists($fontPath)) {
                $bbox = imagettfbbox($fontSize, 0, $fontPath, $value);
                if ($bbox) {
                    $textWidth = $bbox[4] - $bbox[0];
                    $x = ($width - $textWidth) / 2;
                }
            } else {
                $textWidth = strlen($value) * imagefontwidth(5);
                $x = ($width - $textWidth) / 2;
            }
        }

        if ($fontPath && File::exists($fontPath)) {
            imagettftext($templateImage, $fontSize, 0, $x, $config['y'], $textColor, $fontPath, $value);
        } else {
            imagestring($templateImage, 5, $x, $config['y'], $value, $textColor);
        }
    }
}
