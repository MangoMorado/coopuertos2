<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class StorageHelper
{
    /**
     * Asegura que un directorio exista, creándolo si es necesario
     *
     * @param  string  $path  Ruta del directorio a crear
     * @param  int  $permissions  Permisos del directorio (por defecto 0755)
     * @return bool True si el directorio existe o se creó exitosamente, false en caso contrario
     *
     * @throws \RuntimeException Si no se puede crear el directorio y está en producción
     */
    public static function ensureDirectoryExists(string $path, int $permissions = 0755): bool
    {
        if (File::exists($path)) {
            return true;
        }

        try {
            File::makeDirectory($path, $permissions, true);
            Log::info("Directorio creado: {$path}");

            return true;
        } catch (\Exception $e) {
            Log::error("Error al crear directorio {$path}: {$e->getMessage()}");

            // En producción, intentar con permisos más permisivos como fallback
            if (app()->environment('production')) {
                try {
                    // Intentar crear el directorio padre primero si es necesario
                    $parent = dirname($path);
                    if (! File::exists($parent)) {
                        File::makeDirectory($parent, 0775, true);
                    }
                    File::makeDirectory($path, 0775, true);
                    Log::warning("Directorio creado con permisos alternativos (0775): {$path}");

                    return true;
                } catch (\Exception $e2) {
                    Log::critical("Error crítico al crear directorio {$path}: {$e2->getMessage()}");
                    throw new \RuntimeException(
                        "No se pudo crear el directorio {$path}. ".
                        "Verifique los permisos del sistema. Error: {$e2->getMessage()}"
                    );
                }
            }

            // En desarrollo, relanzar la excepción original
            throw $e;
        }
    }

    /**
     * Obtiene la URL de la foto desde base64
     *
     * @param  string|null  $foto  Base64 de la foto
     * @return string|null Base64 de la foto o null si no existe
     */
    public static function getFotoUrl(?string $foto): ?string
    {
        if (! $foto) {
            return null;
        }

        // Debe ser base64 (empieza con "data:")
        if (str_starts_with($foto, 'data:')) {
            return $foto;
        }

        // Si no es base64, retornar null (datos inválidos)
        return null;
    }
}
