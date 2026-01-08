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

class CarnetController extends Controller
{
    public function __construct(
        protected CarnetTemplateService $templateService
    ) {}

    public function index()
    {
        $template = CarnetTemplate::where('activo', true)->first();
        $conductores = Conductor::all();

        return view('carnets.index', compact('template', 'conductores'));
    }

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
     * Iniciar generación manual de carnets
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

        if ($conductorIds && is_array($conductorIds)) {
            $conductores = Conductor::whereIn('id', $conductorIds)->get();
        } else {
            $conductores = Conductor::all();
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

    public function personalizar()
    {
        $template = CarnetTemplate::where('activo', true)->first();

        $variables = $this->templateService->getAvailableVariables();
        $variablesConfig = $this->templateService->prepareVariablesConfig(
            $template?->variables_config
        );

        return view('carnets.personalizar', compact('template', 'variables', 'variablesConfig'));
    }

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
            $imagenPath = $this->templateService->storeImage($request->file('imagen_plantilla'));
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

    public function descargarZip(string $sessionId)
    {
        $log = CarnetGenerationLog::where('session_id', $sessionId)->first();

        if (! $log || $log->estado !== 'completado' || ! $log->archivo_zip) {
            return redirect()->route('carnets.index')
                ->with('error', 'El archivo ZIP no está disponible');
        }

        $filePath = public_path('storage/'.$log->archivo_zip);

        if (! File::exists($filePath)) {
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
