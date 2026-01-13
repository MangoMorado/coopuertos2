<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Convertidor de imágenes PNG a PDF para carnets
 *
 * Convierte imágenes PNG de carnets a formato PDF usando DomPDF. Calcula
 * las dimensiones en milímetros basándose en 300 DPI y genera un PDF
 * con el tamaño exacto de la imagen.
 */
class CarnetPdfConverter
{
    /**
     * Convierte una imagen PNG a PDF usando DomPDF
     *
     * Lee una imagen PNG, la convierte a base64, genera HTML con la imagen
     * y usa DomPDF para crear un PDF con las dimensiones exactas. Asume
     * 300 DPI para el cálculo de dimensiones en milímetros.
     *
     * @param  string  $imagePath  Ruta completa al archivo PNG de entrada
     * @param  string  $pdfPath  Ruta completa donde guardar el PDF generado
     * @param  int  $width  Ancho de la imagen en píxeles
     * @param  int  $height  Alto de la imagen en píxeles
     *
     * @throws \Exception Si no se puede leer la imagen o convertir a PDF
     */
    public function convertirImagenAPDF(string $imagePath, string $pdfPath, int $width, int $height): void
    {
        // Calcular dimensiones en mm (asumiendo 300 DPI)
        $dpi = 300;
        $mmPerInch = 25.4;
        $widthMM = ($width / $dpi) * $mmPerInch;
        $heightMM = ($height / $dpi) * $mmPerInch;

        // Leer imagen como base64
        $imageData = base64_encode(File::get($imagePath));
        $imageBase64 = 'data:image/png;base64,'.$imageData;

        // Crear HTML con la imagen
        $html = $this->generatePdfHtml($imageBase64, $widthMM, $heightMM);

        // Configurar DomPDF
        try {
            $options = new Options;
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper([0, 0, $widthMM, $heightMM], 'portrait');
            $dompdf->render();

            // Guardar PDF
            File::put($pdfPath, $dompdf->output());
        } catch (\Exception $e) {
            Log::error('Error convirtiendo imagen a PDF: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Genera el HTML para el PDF
     */
    protected function generatePdfHtml(string $imageBase64, float $widthMM, float $heightMM): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: '.$widthMM.'mm '.$heightMM.'mm;
        }
        body {
            margin: 0;
            padding: 0;
            width: '.$widthMM.'mm;
            height: '.$heightMM.'mm;
        }
        img {
            width: '.$widthMM.'mm;
            height: '.$heightMM.'mm;
            display: block;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <img src="'.$imageBase64.'" />
</body>
</html>';
    }
}
