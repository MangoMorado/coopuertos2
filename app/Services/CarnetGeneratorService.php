<?php

namespace App\Services;

use App\Models\CarnetTemplate;
use App\Models\Conductor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CarnetGeneratorService
{
    public function __construct(
        protected ImageProcessorService $imageProcessor,
        protected FontManager $fontManager,
        protected CarnetPdfConverter $pdfConverter
    ) {}

    /**
     * Genera un PDF de carnet para un conductor
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
     * Prepara los datos del conductor para el carnet
     */
    protected function prepararDatosConductor(Conductor $conductor): array
    {
        $vehiculo = $conductor->asignacionActiva && $conductor->asignacionActiva->vehicle
            ? $conductor->asignacionActiva->vehicle
            : null;

        $fotoUrl = null;
        if ($conductor->foto) {
            if (Str::startsWith($conductor->foto, 'uploads/')) {
                $fotoUrl = public_path($conductor->foto);
            } else {
                $fotoUrl = storage_path('app/public/'.$conductor->foto);
            }
        }

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
            'foto' => $fotoUrl,
            'vehiculo' => $conductor->vehiculo ? (string) $conductor->vehiculo : 'Relevo',
            'vehiculo_placa' => $vehiculo ? $vehiculo->placa : 'Sin asignar',
            'vehiculo_marca' => $vehiculo ? $vehiculo->marca : '',
            'vehiculo_modelo' => $vehiculo ? $vehiculo->modelo : '',
            'qr_code' => route('conductor.public', $conductor->uuid),
        ];
    }

    /**
     * Carga la imagen de plantilla
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
     * Renderiza las variables sobre la imagen del carnet
     */
    protected function renderizarVariables($templateImage, array $variablesConfig, array $datosConductor, Conductor $conductor, string $tempDir, int $width): void
    {
        foreach ($variablesConfig as $key => $config) {
            if (isset($config['activo']) && $config['activo'] && isset($config['x']) && isset($config['y'])) {
                $value = $datosConductor[$key] ?? '';

                if ($key === 'foto' && $datosConductor['foto'] && File::exists($datosConductor['foto'])) {
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
     * Renderiza la foto del conductor
     */
    protected function renderizarFoto($templateImage, string $fotoUrl, array $config): void
    {
        $fotoSize = $config['size'] ?? 100;
        $fotoImg = $this->imageProcessor->loadImage($fotoUrl);
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
