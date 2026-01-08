<?php

namespace App\Http\Controllers;

use App\Models\CarnetDownload;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Services\CarnetBatchProcessor;
use App\Services\CarnetTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CarnetController extends Controller
{
    public function __construct(
        protected CarnetTemplateService $templateService,
        protected CarnetBatchProcessor $batchProcessor
    ) {}

    public function index()
    {
        $template = CarnetTemplate::where('activo', true)->first();
        $conductores = Conductor::all();

        return view('carnets.index', compact('template', 'conductores'));
    }

    public function exportar(Request $request)
    {
        $template = CarnetTemplate::where('activo', true)->first();

        if (! $template || ! $template->imagen_plantilla) {
            return redirect()->route('carnets.index')
                ->with('error', 'No hay plantilla configurada para generar los carnets');
        }

        $conductores = Conductor::all();

        if ($conductores->isEmpty()) {
            return redirect()->route('carnets.index')
                ->with('error', 'No hay conductores para generar carnets');
        }

        $sessionId = $request->get('session_id');

        // Si no hay session_id, iniciar el proceso
        if (! $sessionId) {
            $sessionId = Str::uuid()->toString();
            CarnetDownload::create([
                'session_id' => $sessionId,
                'total' => $conductores->count(),
                'procesados' => 0,
                'estado' => 'procesando',
                'logs' => [],
            ]);

            // Procesar en segundo plano
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
                try {
                    $this->batchProcessor->procesarCarnets($sessionId, $template, $conductores);
                } catch (\Exception $e) {
                    Log::error('Error en procesamiento: '.$e->getMessage());
                }
            } else {
                register_shutdown_function(function () use ($sessionId, $template, $conductores) {
                    try {
                        $processor = app(CarnetBatchProcessor::class);
                        $processor->procesarCarnets($sessionId, $template, $conductores);
                    } catch (\Exception $e) {
                        Log::error('Error en procesamiento en segundo plano: '.$e->getMessage());
                    }
                });
            }
        }

        return view('carnets.exportar', compact('sessionId'));
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

    public function descargarTodos()
    {
        try {
            $template = CarnetTemplate::where('activo', true)->first();

            if (! $template || ! $template->imagen_plantilla) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay plantilla configurada para generar los carnets',
                ], 400);
            }

            $conductores = Conductor::all();

            if ($conductores->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay conductores para generar carnets',
                ], 400);
            }

            // Crear registro de descarga
            $sessionId = Str::uuid()->toString();
            CarnetDownload::create([
                'session_id' => $sessionId,
                'total' => $conductores->count(),
                'procesados' => 0,
                'estado' => 'procesando',
            ]);

            // Procesar en segundo plano
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
                try {
                    $this->batchProcessor->procesarCarnets($sessionId, $template, $conductores);
                } catch (\Exception $e) {
                    Log::error('Error en procesamiento: '.$e->getMessage());
                }
            } else {
                register_shutdown_function(function () use ($sessionId, $template, $conductores) {
                    try {
                        $processor = app(CarnetBatchProcessor::class);
                        $processor->procesarCarnets($sessionId, $template, $conductores);
                    } catch (\Exception $e) {
                        Log::error('Error en procesamiento en segundo plano: '.$e->getMessage());
                    }
                });
            }

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'total' => $conductores->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en descargarTodos: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la descarga: '.$e->getMessage(),
            ], 500);
        }
    }

    public function obtenerProgreso(string $sessionId)
    {
        $download = CarnetDownload::where('session_id', $sessionId)->first();

        if (! $download) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada',
            ], 404);
        }

        $archivoUrl = null;
        if ($download->archivo_zip) {
            $archivoUrl = asset('storage/'.$download->archivo_zip);
        }

        return response()->json([
            'success' => true,
            'total' => $download->total,
            'procesados' => $download->procesados,
            'estado' => $download->estado,
            'progreso' => $download->total > 0 ? round(($download->procesados / $download->total) * 100, 2) : 0,
            'archivo' => $archivoUrl,
            'error' => $download->error,
            'logs' => $download->logs ?? [],
        ]);
    }

    public function descargarZip(string $sessionId)
    {
        $download = CarnetDownload::where('session_id', $sessionId)->first();

        if (! $download || $download->estado !== 'completado' || ! $download->archivo_zip) {
            return redirect()->route('carnets.index')
                ->with('error', 'El archivo ZIP no está disponible');
        }

        $filePath = public_path('storage/'.$download->archivo_zip);

        if (! File::exists($filePath)) {
            return redirect()->route('carnets.index')
                ->with('error', 'El archivo ZIP no se encontró');
        }

        return response()->download($filePath, 'carnets_'.date('YmdHis').'.zip')
            ->deleteFileAfterSend(true);
    }
}
