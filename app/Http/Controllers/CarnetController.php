<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarGeneracionCarnets;
use App\Models\CarnetGenerationLog;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Services\CarnetTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

/**
 * Controlador web para gestión de carnets
 *
 * Gestiona la generación masiva de carnets, personalización de plantillas,
 * descarga de carnets generados y exportación de QRs. Utiliza jobs en cola
 * para procesar generaciones grandes en segundo plano.
 */
class CarnetController extends Controller
{
    /**
     * @param  CarnetTemplateService  $templateService  Servicio de gestión de plantillas de carnets
     */
    public function __construct(
        protected CarnetTemplateService $templateService
    ) {}

    /**
     * Muestra la lista de carnets y plantilla activa
     *
     * Obtiene la plantilla activa y todos los conductores con sus asignaciones
     * de vehículos para mostrar en la vista principal de carnets.
     *
     * @return \Illuminate\Contracts\View\View Vista de lista de carnets
     */
    public function index()
    {
        $template = CarnetTemplate::where('activo', true)->first();
        $conductores = Conductor::with(['asignacionActiva.vehicle'])->get();

        return view('carnets.index', compact('template', 'conductores'));
    }

    /**
     * Muestra la página de exportación de carnets
     *
     * Si se proporciona un session_id, muestra el estado de esa generación.
     * Si no, busca el último proceso de generación completado para mostrar.
     *
     * @param  Request  $request  Request HTTP con parámetro opcional 'session_id'
     * @return \Illuminate\Contracts\View\View Vista de exportación de carnets
     */
    public function exportar(Request $request)
    {
        $sessionId = $request->get('session_id');

        // Si hay un session_id, mostrar ese proceso
        // Si no, buscar el último proceso de generación completado
        if (! $sessionId) {
            $ultimoLog = CarnetGenerationLog::where('estado', 'completado')
                ->whereNotNull('archivo_zip')
                ->latest('completed_at')
                ->first();

            if ($ultimoLog) {
                $sessionId = $ultimoLog->session_id;
            }
        }

        return view('carnets.exportar', compact('sessionId'));
    }

    /**
     * Inicia la generación manual masiva de carnets
     *
     * Verifica que haya una plantilla activa, obtiene los conductores a procesar
     * (todos o los seleccionados), crea un CarnetGenerationLog y encola un job
     * supervisor (ProcesarGeneracionCarnets) para procesar la generación en segundo plano.
     *
     * @param  Request  $request  Request HTTP con parámetro opcional 'conductor_ids' (array)
     * @return \Illuminate\Http\RedirectResponse Redirección a la página de exportación
     */
    public function generar(Request $request)
    {
        // Verificar que hay una plantilla activa
        $template = CarnetTemplate::where('activo', true)->first();

        if (! $template) {
            return redirect()->route('carnets.exportar')
                ->with('error', 'No hay plantilla activa para generar los carnets. Por favor, configure una plantilla primero.');
        }

        // Obtener conductores a procesar (todos por defecto, o los seleccionados si se envía)
        $conductorIds = $request->input('conductor_ids');

        $query = Conductor::with(['asignacionActiva.vehicle']);
        if ($conductorIds && is_array($conductorIds)) {
            $conductores = $query->whereIn('id', $conductorIds)->get();
        } else {
            $conductores = $query->get();
        }

        if ($conductores->isEmpty()) {
            return redirect()->route('carnets.exportar')
                ->with('error', 'No hay conductores para generar carnets.');
        }

        // Crear log de generación masiva
        $sessionId = Str::uuid()->toString();
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => auth()->id(),
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => $conductores->count(),
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
            'mensaje' => 'Iniciando generación de carnets...',
            'logs' => [
                [
                    'timestamp' => now()->toDateTimeString(),
                    'tipo' => 'info',
                    'mensaje' => '🔄 Iniciando generación masiva de carnets...',
                    'data' => [
                        'template_id' => $template->id,
                        'template_nombre' => $template->nombre,
                        'total_conductores' => $conductores->count(),
                    ],
                ],
            ],
        ]);

        // Encolar job supervisor
        ProcesarGeneracionCarnets::dispatch(
            $sessionId,
            'masivo',
            auth()->id(),
            $template->id,
            $conductores->pluck('id')->toArray()
        )->onQueue('carnets');

        Log::info("Generación masiva de carnets iniciada manualmente - Session ID: {$sessionId}, Total: {$conductores->count()}");

        return redirect()->route('carnets.exportar', ['session_id' => $sessionId])
            ->with('success', 'Generación de carnets iniciada. El proceso se ejecutará en segundo plano.');
    }

    /**
     * Muestra la página de personalización de plantillas
     *
     * Obtiene la plantilla activa y las variables disponibles junto con su
     * configuración para mostrar en el editor de plantillas.
     *
     * @return \Illuminate\Contracts\View\View Vista de personalización de plantillas
     */
    public function personalizar()
    {
        $template = CarnetTemplate::where('activo', true)->first();

        $variables = $this->templateService->getAvailableVariables();
        $variablesConfig = $this->templateService->prepareVariablesConfig(
            $template?->variables_config
        );

        return view('carnets.personalizar', compact('template', 'variables', 'variablesConfig'));
    }

    /**
     * Guarda una nueva plantilla de carnet
     *
     * Valida los datos, desactiva todas las plantillas anteriores, maneja
     * la imagen de plantilla (nueva o mantiene la anterior), y crea una
     * nueva plantilla activa con la configuración de variables.
     *
     * @param  Request  $request  Request HTTP con datos de la plantilla (nombre, imagen, variables_config)
     * @return \Illuminate\Http\RedirectResponse Redirección a la lista de carnets
     */
    public function guardarPlantilla(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'imagen_plantilla' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'variables_config' => 'required|json',
        ]);

        // Desactivar todas las plantillas anteriores
        CarnetTemplate::where('activo', true)->update(['activo' => false]);

        // Manejo de la imagen si se sube
        $imagenPath = null;
        if ($request->hasFile('imagen_plantilla')) {
            try {
                $imagenPath = $this->templateService->storeImage($request->file('imagen_plantilla'));
            } catch (\RuntimeException $e) {
                Log::error('Error al guardar imagen de plantilla de carnet: '.$e->getMessage(), [
                    'error' => $e->getMessage(),
                    'upload_path' => public_path('uploads/carnets'),
                ]);

                return redirect()->route('carnets.personalizar')
                    ->with('error', 'Error al subir la imagen de plantilla: '.$e->getMessage())
                    ->withInput();
            }
        } else {
            // Si no se sube nueva imagen, mantener la anterior si existe
            $templateAnterior = CarnetTemplate::latest()->first();
            if ($templateAnterior && $templateAnterior->imagen_plantilla) {
                $imagenPath = $templateAnterior->imagen_plantilla;
            }
        }

        // Decodificar variables_config
        $variablesConfig = json_decode($validated['variables_config'], true);

        // Crear o actualizar plantilla
        CarnetTemplate::create([
            'nombre' => $validated['nombre'] ?? 'Plantilla Principal',
            'imagen_plantilla' => $imagenPath,
            'variables_config' => $variablesConfig,
            'activo' => true,
        ]);

        return redirect()->route('carnets.index')
            ->with('success', 'Plantilla de carnet guardada correctamente.');
    }

    public function obtenerProgreso(string $sessionId)
    {
        $log = CarnetGenerationLog::where('session_id', $sessionId)->first();

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada',
            ], 404);
        }

        $archivoUrl = null;
        if ($log->archivo_zip) {
            $archivoUrl = asset('storage/'.$log->archivo_zip);
        }

        return response()->json([
            'success' => true,
            'total' => $log->total,
            'procesados' => $log->procesados,
            'exitosos' => $log->exitosos,
            'errores' => $log->errores,
            'estado' => $log->estado,
            'progreso' => $log->total > 0 ? round(($log->procesados / $log->total) * 100, 2) : 0,
            'archivo' => $archivoUrl,
            'error' => $log->error,
            'mensaje' => $log->mensaje,
            'logs' => $log->logs ?? [],
            'tiempo_transcurrido' => $log->tiempo_transcurrido,
            'tiempo_estimado_restante' => $log->tiempo_estimado_restante,
        ]);
    }

    /**
     * Descarga el archivo ZIP de carnets generados
     *
     * Busca el archivo ZIP asociado a la sesión de generación, intentando
     * primero la ruta del log y luego la ruta esperada. Actualiza el log
     * si encuentra el archivo pero el log no tenía la ruta. El archivo no
     * se elimina después de la descarga ya que los carnets se guardan permanentemente.
     *
     * @param  string  $sessionId  Identificador único de la sesión de generación
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse Archivo ZIP o redirección con error
     */
    public function descargarZip(string $sessionId)
    {
        $log = CarnetGenerationLog::where('session_id', $sessionId)->first();

        if (! $log) {
            return redirect()->route('carnets.index')
                ->with('error', 'Sesión no encontrada');
        }

        // Intentar usar la ruta del log si está disponible
        $filePath = null;
        if ($log->archivo_zip) {
            $filePath = public_path('storage/'.$log->archivo_zip);
        }

        // Si no hay ruta en el log o el archivo no existe, intentar construir la ruta esperada
        if (! $filePath || ! File::exists($filePath)) {
            $expectedPath = public_path('storage/carnets/carnets_'.$sessionId.'.zip');
            if (File::exists($expectedPath)) {
                $filePath = $expectedPath;
                // Actualizar el log con la ruta correcta si no tenía
                if (! $log->archivo_zip) {
                    $log->update(['archivo_zip' => 'carnets/carnets_'.$sessionId.'.zip']);
                }
            }
        }

        if (! $filePath || ! File::exists($filePath)) {
            Log::warning('ZIP no encontrado para descarga', [
                'session_id' => $sessionId,
                'log_archivo_zip' => $log->archivo_zip,
                'expected_path' => public_path('storage/carnets/carnets_'.$sessionId.'.zip'),
                'log_estado' => $log->estado,
            ]);

            return redirect()->route('carnets.index')
                ->with('error', 'El archivo ZIP no se encontró');
        }

        return response()->download($filePath, 'carnets_'.date('YmdHis').'.zip')
            ->deleteFileAfterSend(false); // No eliminar, los carnets se guardan permanentemente
    }

    /**
     * Descargar el último ZIP generado
     */
    public function descargarUltimoZip()
    {
        // Buscar el último ZIP generado
        $ultimoLog = CarnetGenerationLog::where('estado', 'completado')
            ->whereNotNull('archivo_zip')
            ->latest('completed_at')
            ->first();

        if (! $ultimoLog || ! $ultimoLog->archivo_zip) {
            return redirect()->route('carnets.exportar')
                ->with('error', 'No hay archivo ZIP disponible para descargar');
        }

        $filePath = public_path('storage/'.$ultimoLog->archivo_zip);

        if (! File::exists($filePath)) {
            return redirect()->route('carnets.exportar')
                ->with('error', 'El archivo ZIP no se encontró');
        }

        return response()->download($filePath, 'carnets_'.date('YmdHis').'.zip')
            ->deleteFileAfterSend(false);
    }

    /**
     * Exporta todos los QRs de conductores en un archivo ZIP
     *
     * Genera códigos QR para todos los conductores (URL pública de cada conductor),
     * los guarda como archivos SVG con el nombre del conductor en formato slug
     * (ej: juanito-perez.svg) en un directorio temporal, crea un ZIP con todos
     * los QRs y lo descarga. El directorio temporal se limpia después de la descarga.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse Archivo ZIP o redirección con error
     */
    public function exportarQRs()
    {
        try {
            // Obtener todos los conductores con eager loading
            $conductores = Conductor::with(['asignacionActiva.vehicle'])->get();

            if ($conductores->isEmpty()) {
                return redirect()->route('carnets.exportar')
                    ->with('error', 'No hay conductores para exportar QRs.');
            }

            // Crear directorio temporal para QRs
            $tempDir = storage_path('app/temp/qrs_'.time());
            if (! File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            $qrGenerados = 0;

            // Generar QR para cada conductor
            foreach ($conductores as $conductor) {
                try {
                    $qrCode = QrCode::size(300)
                        ->generate(route('conductor.public', $conductor->uuid));

                    // Guardar QR como SVG con nombre del conductor en formato slug
                    $nombreCompleto = trim($conductor->nombres.' '.$conductor->apellidos);
                    $qrFileName = Str::slug($nombreCompleto).'.svg';
                    $qrPath = $tempDir.'/'.$qrFileName;
                    File::put($qrPath, $qrCode);
                    $qrGenerados++;
                } catch (\Exception $e) {
                    Log::warning('Error generando QR para conductor: '.$e->getMessage(), [
                        'conductor_id' => $conductor->id,
                        'cedula' => $conductor->cedula,
                    ]);
                    // Continuar con el siguiente conductor
                }
            }

            if ($qrGenerados === 0) {
                // Limpiar directorio temporal
                File::deleteDirectory($tempDir);

                return redirect()->route('carnets.exportar')
                    ->with('error', 'No se pudieron generar QRs.');
            }

            // Crear ZIP con todos los QRs
            $zipFileName = 'qrs_conductores_'.date('YmdHis').'.zip';
            $zipPath = storage_path('app/temp/'.$zipFileName);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                File::deleteDirectory($tempDir);

                return redirect()->route('carnets.exportar')
                    ->with('error', 'No se pudo crear el archivo ZIP.');
            }

            // Agregar todos los archivos SVG al ZIP
            $files = File::files($tempDir);
            foreach ($files as $file) {
                $zip->addFile($file->getPathname(), $file->getFilename());
            }

            $zip->close();

            // Limpiar directorio temporal de QRs
            File::deleteDirectory($tempDir);

            // Descargar el ZIP y eliminarlo después
            return response()->download($zipPath, $zipFileName)
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error exportando QRs: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('carnets.exportar')
                ->with('error', 'Error al exportar QRs: '.$e->getMessage());
        }
    }

    /**
     * Limpiar ZIPs viejos, manteniendo solo los 2 más recientes
     */
    protected function limpiarZipsViejos(): void
    {
        $zipDir = public_path('storage/carnets');

        if (! File::exists($zipDir)) {
            return;
        }

        // Obtener todos los archivos ZIP ordenados por fecha de modificación
        $zips = collect(File::files($zipDir))
            ->filter(function ($file) {
                return strtolower($file->getExtension()) === 'zip';
            })
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            })
            ->values();

        // Si hay más de 2, eliminar los más viejos
        if ($zips->count() > 2) {
            $zipsParaEliminar = $zips->slice(2);

            foreach ($zipsParaEliminar as $zip) {
                try {
                    File::delete($zip->getPathname());
                    Log::info('ZIP antiguo eliminado: '.$zip->getFilename());
                } catch (\Exception $e) {
                    Log::warning('Error eliminando ZIP antiguo: '.$e->getMessage());
                }
            }
        }
    }
}
