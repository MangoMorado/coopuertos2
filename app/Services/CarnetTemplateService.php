<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CarnetTemplateService
{
    /**
     * Obtiene las variables disponibles para los carnets
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
