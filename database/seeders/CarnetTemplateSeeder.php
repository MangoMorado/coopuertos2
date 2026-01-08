<?php

namespace Database\Seeders;

use App\Models\CarnetTemplate;
use Illuminate\Database\Seeder;

class CarnetTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Desactivar todas las plantillas existentes
        CarnetTemplate::where('activo', true)->update(['activo' => false]);

        $variablesConfig = [
            'rh' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'foto' => [
                'x' => 2.65625,
                'y' => 385.6875,
                'size' => '400',
                'activo' => true,
            ],
            'cedula' => [
                'x' => 447.84375,
                'y' => 682.125,
                'color' => '#000000',
                'activo' => true,
                'centrado' => false,
                'fontSize' => '40',
                'fontStyle' => 'normal',
                'fontFamily' => 'Century Gothic',
            ],
            'correo' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'estado' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'celular' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'nombres' => [
                'x' => 451.03125,
                'y' => 467.5,
                'color' => '#000000',
                'activo' => true,
                'centrado' => false,
                'fontSize' => '45',
                'fontStyle' => 'normal',
                'fontFamily' => 'Century Gothic',
            ],
            'qr_code' => [
                'x' => 80.21875,
                'y' => 848.9375,
                'size' => '280',
                'activo' => true,
            ],
            'vehiculo' => [
                'x' => 478.65625,
                'y' => 822.375,
                'color' => '#000000',
                'activo' => true,
                'centrado' => false,
                'fontSize' => '40',
                'fontStyle' => 'normal',
                'fontFamily' => 'Century Gothic',
            ],
            'apellidos' => [
                'x' => 451.03125,
                'y' => 533.375,
                'color' => '#000000',
                'activo' => true,
                'centrado' => false,
                'fontSize' => '40',
                'fontStyle' => 'normal',
                'fontFamily' => 'Century Gothic',
            ],
            'conductor_tipo' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'nivel_estudios' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'numero_interno' => [
                'x' => 413.84375,
                'y' => 328.3125,
                'color' => '#000000',
                'activo' => true,
                'centrado' => true,
                'fontSize' => '40',
                'fontStyle' => 'normal',
                'fontFamily' => 'Century Gothic',
            ],
            'otra_profesion' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'vehiculo_marca' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'vehiculo_placa' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => '40',
                'fontStyle' => 'normal',
                'fontFamily' => 'Century Gothic',
            ],
            'nombre_completo' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'vehiculo_modelo' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
            'fecha_nacimiento' => [
                'color' => '#000000',
                'activo' => false,
                'centrado' => false,
                'fontSize' => 14,
                'fontStyle' => 'normal',
                'fontFamily' => 'Arial',
            ],
        ];

        // Crear la plantilla
        CarnetTemplate::create([
            'nombre' => 'Coopuertos',
            'imagen_plantilla' => 'images/fondo_carnet.png',
            'variables_config' => $variablesConfig,
            'activo' => true,
        ]);
    }
}
