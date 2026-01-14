<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Servicio de gestión de plantillas de carnets
 *
 * Proporciona las variables disponibles para personalizar carnets y prepara
 * la configuración de variables con valores por defecto. Maneja el almacenamiento
 * de imágenes de plantillas.
 */
class CarnetTemplateService
{
    /**
     * Obtiene el listado de variables disponibles para personalizar carnets
     *
     * Retorna un array asociativo con todas las variables que pueden ser utilizadas
     * en las plantillas de carnets, incluyendo datos del conductor, vehículo y elementos
     * especiales como foto y código QR.
     *
     * @return array<string, string> Array asociativo de variables (clave => etiqueta): ['nombres' => 'Nombres', 'cedula' => 'Cédula', ...]
     */
    public function getAvailableVariables(): array
    {
        return [
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'nombre_completo' => 'Nombre Completo',
            'cedula' => 'Cédula',
            'conductor_tipo' => 'Tipo Conductor (A/B)',
            'rh' => 'Tipo de Sangre (RH)',
            'numero_interno' => 'Número Interno',
            'celular' => 'Celular',
            'correo' => 'Correo',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'nivel_estudios' => 'Nivel de Estudios',
            'otra_profesion' => 'Otra Profesión',
            'estado' => 'Estado',
            'foto' => 'Foto',
            'vehiculo' => 'Vehículo',
            'vehiculo_placa' => 'Placa del Vehículo',
            'vehiculo_marca' => 'Marca del Vehículo',
            'vehiculo_modelo' => 'Modelo del Vehículo',
            'qr_code' => 'Código QR',
        ];
    }

    /**
     * Prepara la configuración de variables con valores por defecto
     *
     * Toma una configuración existente (opcional) y asegura que todas las variables
     * disponibles tengan configuración. Para variables de tipo imagen (foto, qr_code)
     * usa una estructura diferente que para variables de texto. Agrega valores por
     * defecto para posiciones, tamaños, fuentes y colores.
     *
     * @param  array<string, array<string, mixed>>|null  $existingConfig  Configuración existente de variables (opcional)
     * @return array<string, array<string, mixed>> Configuración completa de variables con valores por defecto
     */
    public function prepareVariablesConfig(?array $existingConfig = null): array
    {
        $variables = $this->getAvailableVariables();
        $variablesConfig = $existingConfig ?? [];

        // Asegurar que todas las variables tengan configuración
        foreach ($variables as $key => $label) {
            if (! isset($variablesConfig[$key])) {
                if ($key === 'foto' || $key === 'qr_code') {
                    $variablesConfig[$key] = [
                        'activo' => false,
                        'x' => null,
                        'y' => null,
                        'size' => 100,
                    ];
                } else {
                    $variablesConfig[$key] = [
                        'activo' => false,
                        'x' => null,
                        'y' => null,
                        'fontSize' => 14,
                        'color' => '#000000',
                        'fontFamily' => 'Arial',
                        'fontStyle' => 'normal',
                        'centrado' => false,
                    ];
                }
            }
        }

        return $variablesConfig;
    }

    /**
     * Almacena una imagen de plantilla y retorna la ruta relativa
     *
     * Guarda un archivo de imagen de plantilla en el directorio público de uploads/carnets.
     * Genera un nombre único usando UUID para evitar colisiones. Crea el directorio
     * si no existe y verifica permisos de escritura.
     *
     * @param  \Illuminate\Http\UploadedFile  $file  Archivo de imagen subido
     * @return string Ruta relativa de la imagen almacenada (ej: 'uploads/carnets/uuid.jpg')
     *
     * @throws \RuntimeException Si no se puede crear el directorio o no tiene permisos de escritura
     */
    public function storeImage($file): string
    {
        $uploadPath = public_path('uploads/carnets');
        $uploadsDir = public_path('uploads');

        // Asegurar que el directorio padre 'uploads' existe
        if (! File::exists($uploadsDir)) {
            try {
                File::makeDirectory($uploadsDir, 0775, true);
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    "No se pudo crear el directorio padre 'uploads': {$e->getMessage()}"
                );
            }
        }

        // Crear el directorio 'uploads/carnets' si no existe
        if (! File::exists($uploadPath)) {
            try {
                File::makeDirectory($uploadPath, 0775, true);
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    "No se pudo crear el directorio 'uploads/carnets': {$e->getMessage()}"
                );
            }
        }

        // Verificar permisos de escritura
        if (! is_writable($uploadPath)) {
            // Intentar establecer permisos de escritura (solo en sistemas Unix)
            if (PHP_OS_FAMILY !== 'Windows') {
                try {
                    @chmod($uploadPath, 0775);
                } catch (\Exception $e) {
                    // Ignorar si no se pueden cambiar permisos
                }
            }

            // Verificar nuevamente después de intentar cambiar permisos
            if (! is_writable($uploadPath)) {
                throw new \RuntimeException(
                    "El directorio 'uploads/carnets' no tiene permisos de escritura. ".
                    "Por favor, verifica los permisos del directorio: {$uploadPath}"
                );
            }
        }

        // Generar nombre único para el archivo
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $filePath = $uploadPath.'/'.$filename;

        // Intentar mover el archivo
        try {
            $file->move($uploadPath, $filename);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "No se pudo guardar la imagen en 'uploads/carnets': {$e->getMessage()}"
            );
        }

        // Verificar que el archivo se guardó correctamente
        if (! File::exists($filePath)) {
            throw new \RuntimeException(
                "El archivo no se guardó correctamente en 'uploads/carnets'"
            );
        }

        return 'uploads/carnets/'.$filename;
    }
}
