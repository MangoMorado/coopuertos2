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
     * si no existe.
     *
     * @param  \Illuminate\Http\UploadedFile  $file  Archivo de imagen subido
     * @return string Ruta relativa de la imagen almacenada (ej: 'uploads/carnets/uuid.jpg')
     */
    public function storeImage($file): string
    {
        $uploadPath = public_path('uploads/carnets');

        if (! File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return 'uploads/carnets/'.$filename;
    }
}
