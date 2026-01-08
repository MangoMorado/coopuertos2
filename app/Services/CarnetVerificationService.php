<?php

namespace App\Services;

use App\Models\CarnetTemplate;
use App\Models\Conductor;
use Illuminate\Support\Facades\File;

class CarnetVerificationService
{
    /**
     * Verifica qué conductores necesitan regenerar su carnet
     *
     * @return array Array con información de qué regenerar
     */
    public function verificarCarnetsFaltantes(): array
    {
        $template = CarnetTemplate::where('activo', true)->first();

        if (! $template) {
            return [
                'hay_plantilla' => false,
                'conductores_sin_carnet' => [],
                'conductores_con_archivo_faltante' => [],
                'total_necesitan_generacion' => 0,
            ];
        }

        $conductores = Conductor::all();
        $conductoresSinCarnet = [];
        $conductoresConArchivoFaltante = [];
        $templateHash = $this->calcularHashTemplate($template);

        foreach ($conductores as $conductor) {
            // Verificar si no tiene ruta_carnet
            if (! $conductor->ruta_carnet) {
                $conductoresSinCarnet[] = $conductor;

                continue;
            }

            // Verificar si el archivo físico existe
            $rutaCompleta = storage_path('app/'.$conductor->ruta_carnet);
            if (! File::exists($rutaCompleta)) {
                $conductoresConArchivoFaltante[] = $conductor;

                continue;
            }

            // Verificar si el carnet fue generado con una plantilla diferente
            // Esto se puede hacer verificando la fecha de modificación del archivo
            // vs la fecha de actualización de la plantilla
            if ($this->necesitaRegeneracionPorPlantilla($conductor, $template)) {
                $conductoresConArchivoFaltante[] = $conductor;
            }
        }

        return [
            'hay_plantilla' => true,
            'template_id' => $template->id,
            'template_hash' => $templateHash,
            'conductores_sin_carnet' => $conductoresSinCarnet,
            'conductores_con_archivo_faltante' => $conductoresConArchivoFaltante,
            'total_necesitan_generacion' => count($conductoresSinCarnet) + count($conductoresConArchivoFaltante),
            'total_conductores' => $conductores->count(),
            'total_con_carnet_valido' => $conductores->count() - (count($conductoresSinCarnet) + count($conductoresConArchivoFaltante)),
        ];
    }

    /**
     * Verifica si un conductor necesita regenerar su carnet por cambio de plantilla
     */
    protected function necesitaRegeneracionPorPlantilla(Conductor $conductor, CarnetTemplate $template): bool
    {
        // Si el conductor no tiene carnet, no necesita regeneración
        if (! $conductor->ruta_carnet) {
            return false;
        }

        $rutaCompleta = storage_path('app/'.$conductor->ruta_carnet);

        // Si el archivo no existe, necesita regeneración
        if (! File::exists($rutaCompleta)) {
            return true;
        }

        // Verificar si la plantilla fue modificada después de que se generó el carnet
        // Comparar fecha de modificación del archivo con fecha de actualización de la plantilla
        $fechaModificacionArchivo = File::lastModified($rutaCompleta);
        $fechaActualizacionTemplate = $template->updated_at->timestamp;

        // Si la plantilla fue actualizada después de generar el carnet, necesita regeneración
        return $fechaActualizacionTemplate > $fechaModificacionArchivo;
    }

    /**
     * Calcula un hash de la plantilla para detectar cambios
     */
    protected function calcularHashTemplate(CarnetTemplate $template): string
    {
        $datosParaHash = [
            'imagen_plantilla' => $template->imagen_plantilla,
            'variables_config' => $template->variables_config,
            'updated_at' => $template->updated_at?->timestamp ?? 0,
        ];

        return md5(json_encode($datosParaHash));
    }

    /**
     * Verifica si un archivo de carnet existe y es válido
     */
    public function carnetExisteYEsValido(Conductor $conductor): bool
    {
        if (! $conductor->ruta_carnet) {
            return false;
        }

        $rutaCompleta = storage_path('app/'.$conductor->ruta_carnet);

        if (! File::exists($rutaCompleta)) {
            return false;
        }

        // Verificar que el archivo no esté vacío
        if (File::size($rutaCompleta) === 0) {
            return false;
        }

        // Verificar que sea un PDF válido (mínimo verificar la extensión y headers)
        $extension = strtolower(File::extension($rutaCompleta));
        if ($extension !== 'pdf') {
            return false;
        }

        // Verificar que el archivo empiece con el header PDF
        $handle = fopen($rutaCompleta, 'r');
        $header = fread($handle, 4);
        fclose($handle);

        return strpos($header, '%PDF') === 0;
    }

    /**
     * Obtiene todos los conductores que necesitan regenerar su carnet
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerConductoresQueNecesitanRegeneracion()
    {
        $verificacion = $this->verificarCarnetsFaltantes();

        $ids = collect($verificacion['conductores_sin_carnet'])
            ->merge($verificacion['conductores_con_archivo_faltante'])
            ->pluck('id')
            ->unique()
            ->toArray();

        return Conductor::whereIn('id', $ids)->get();
    }
}
