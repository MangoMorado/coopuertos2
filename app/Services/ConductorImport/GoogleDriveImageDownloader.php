<?php

namespace App\Services\ConductorImport;

use Illuminate\Support\Facades\Log;

/**
 * Descargador de imágenes desde Google Drive
 *
 * Descarga imágenes desde URLs de Google Drive y las convierte a formato base64
 * para almacenamiento en base de datos. Soporta múltiples formatos de URL de Google Drive.
 */
class GoogleDriveImageDownloader
{
    /**
     * Descarga una imagen desde Google Drive y la convierte a base64
     *
     * Extrae el ID del archivo de la URL de Google Drive, descarga la imagen usando
     * múltiples métodos de URL, valida que sea una imagen válida y la convierte a
     * formato base64 con data URI (data:image/mime;base64,...).
     *
     * @param  string  $url  URL de Google Drive (soporta múltiples formatos: /open?id=, /file/d/, /uc?id=)
     * @return string|null Data URI de la imagen en base64 (data:image/mime;base64,...) o null si falla
     */
    public function downloadAsBase64(string $url): ?string
    {
        try {
            // Extraer el ID del archivo de Google Drive
            $fileId = $this->extractFileId($url);

            if (! $fileId) {
                Log::warning('No se pudo extraer el ID de Google Drive de la URL: '.$url);

                return null;
            }

            // Intentar diferentes métodos de descarga
            // Método 1: URL directa simple
            $downloadUrl = "https://drive.google.com/uc?export=download&id={$fileId}";
            $imageContent = @file_get_contents($downloadUrl);

            // Si falla, intentar método alternativo
            if ($imageContent === false || empty($imageContent)) {
                $downloadUrl = "https://drive.google.com/uc?export=download&confirm=t&id={$fileId}";
                $imageContent = @file_get_contents($downloadUrl);
            }

            if ($imageContent === false || empty($imageContent)) {
                Log::warning('No se pudo descargar la imagen de Google Drive. ID: '.$fileId);

                return null;
            }

            // Verificar que sea una imagen válida
            $imageInfo = @getimagesizefromstring($imageContent);
            if ($imageInfo === false) {
                Log::warning('El contenido descargado no es una imagen válida. ID: '.$fileId);

                return null;
            }

            // Convertir a base64
            $base64 = base64_encode($imageContent);
            $base64String = 'data:'.$imageInfo['mime'].';base64,'.$base64;

            return $base64String;

        } catch (\Exception $e) {
            Log::error('Error descargando imagen de Google Drive: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Extraer ID de archivo de URL de Google Drive
     */
    private function extractFileId(string $url): ?string
    {
        // Patrón 1: https://drive.google.com/open?id=FILE_ID
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Patrón 2: https://drive.google.com/file/d/FILE_ID/view
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Patrón 3: https://drive.google.com/uc?id=FILE_ID
        if (preg_match('/\/uc\?id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Obtiene la extensión de archivo correspondiente a un tipo MIME de imagen
     *
     * Convierte un tipo MIME de imagen (ej: 'image/jpeg') a su extensión de archivo
     * correspondiente (ej: 'jpg'). Retorna 'jpg' como valor por defecto si no se reconoce.
     *
     * @param  string  $mimeType  Tipo MIME de la imagen (ej: 'image/jpeg', 'image/png')
     * @return string Extensión de archivo sin punto (ej: 'jpg', 'png', 'gif', 'webp')
     */
    public function getImageExtension(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $extensions[$mimeType] ?? 'jpg';
    }
}
