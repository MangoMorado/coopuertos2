<?php

namespace App\Services;

use App\Models\CarnetTemplate;
use App\Models\Conductor;
use Illuminate\Support\Facades\File;

/**
 * Servicio de verificación de carnets de conductores
 *
 * Verifica el estado de los carnets generados, identifica conductores sin carnet,
 * archivos faltantes y carnets que necesitan regeneración por cambios en la plantilla.
 */
class CarnetVerificationService
{
    /**
     * Verifica qué conductores necesitan regenerar su carnet
     *
     * Analiza todos los conductores y determina cuáles no tienen carnet, cuáles
     * tienen archivos faltantes o necesitan regeneración por cambios en la plantilla.
     * Compara fechas de modificación de archivos con fechas de actualización de plantilla.
     *
     * @return array{
     *     hay_plantilla: bool,
     *     template_id?: int,
     *     template_hash?: string,
     *     conductores_sin_carnet: array<int, Conductor>,
     *     conductores_con_archivo_faltante: array<int, Conductor>,
     *     total_necesitan_generacion: int,
     *     total_conductores: int,
     *     total_con_carnet_valido: int
     * }
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
     *
     * Verifica que el conductor tenga una ruta de carnet configurada, que el archivo
     * exista físicamente, que no esté vacío, que tenga extensión PDF y que el
     * archivo empiece con el header PDF válido (%PDF).
     *
     * @param  Conductor  $conductor  Conductor a verificar
     * @return bool True si el carnet existe y es válido, false en caso contrario
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
     * Utiliza verificarCarnetsFaltantes() para obtener los conductores sin carnet
     * y con archivos faltantes, luego retorna una colección Eloquent con esos conductores.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Conductor> Colección de conductores que necesitan regeneración
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
