<?php

namespace App\Services\ConductorImport;

use Illuminate\Support\Facades\Log;

class GoogleDriveImageDownloader
{
    /**
     * Descargar imagen desde Google Drive y convertir a base64
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
     * Obtener extensión desde MIME type
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
