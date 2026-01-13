<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarImportacionConductores;
use App\Models\ImportLog;
use App\Services\ConductorImport\ConductorImportFileValidator;
use App\Services\ConductorImport\ConductorImportProgressTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConductorImportController extends Controller
{
    public function __construct(
        private ConductorImportFileValidator $fileValidator,
        private ConductorImportProgressTracker $progressTracker
    ) {}

    public function showImportForm()
    {
        $importSessionId = session('import_session_id');
        $importLog = null;

        if ($importSessionId) {
            $importLog = ImportLog::where('session_id', $importSessionId)
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('conductores.import', [
            'importLog' => $importLog,
            'importSessionId' => $importSessionId,
        ]);
    }

    public function import(Request $request)
    {
        // Aumentar tiempo de ejecución para importaciones grandes
        set_time_limit(300); // 5 minutos

        try {
            $file = $request->file('archivo');

            // Validar archivo
            $validation = $this->fileValidator->validate($file);

            if (! $validation['valid']) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de validación: '.$validation['errors']->first(),
                    ], 422);
                }

                return redirect()->back()->withErrors($validation['errors'])->withInput();
            }

            // Crear registro de importación en base de datos
            $sessionId = Str::uuid()->toString();
            $extension = $validation['extension'];
            $fileName = $file->getClientOriginalName();

            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/temp_imports');
            if (! File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Guardar archivo temporalmente
            $tempFilename = $sessionId.'.'.$extension;
            $tempPath = 'temp_imports/'.$tempFilename;

            try {
                $file->move($tempDir, $tempFilename);
            } catch (\Exception $e) {
                Log::error('Error moviendo archivo: '.$e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar archivo: '.$e->getMessage(),
                ], 500);
            }

            // Crear registro en base de datos
            $importLog = ImportLog::create([
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
                'file_path' => $tempPath,
                'file_name' => $fileName,
                'extension' => $extension,
                'estado' => 'pendiente',
                'progreso' => 0,
                'mensaje' => 'Archivo cargado. Esperando procesamiento...',
                'logs' => [['mensaje' => 'Archivo cargado correctamente', 'tipo' => 'success']],
                'started_at' => null,
            ]);

            // Encolar job para procesar en segundo plano
            ProcesarImportacionConductores::dispatch($sessionId, $tempPath, $extension, auth()->id())
                ->onQueue('importaciones');

            // Si es petición AJAX, retornar inmediatamente
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'session_id' => $sessionId,
                    'message' => 'Importación iniciada. Procesando en segundo plano...',
                    'estado' => 'procesando',
                ]);
            }

            // Si no es AJAX, redirigir a la página de importación con el session_id
            return redirect()->route('conductores.import.index')
                ->with('import_session_id', $sessionId)
                ->with('success', 'Archivo cargado. La importación se procesará en segundo plano.');

        } catch (\Exception $e) {
            Log::error('Error en import: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($sessionId)) {
                $this->progressTracker->updateSessionProgress($sessionId, [
                    'estado' => 'error',
                    'error' => 'Error al importar archivo: '.$e->getMessage(),
                    'log' => [['mensaje' => 'Error: '.$e->getMessage(), 'tipo' => 'error']],
                ]);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al iniciar importación: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error al importar archivo: '.$e->getMessage());
        }
    }

    public function obtenerProgreso($sessionId)
    {
        try {
            $importLog = ImportLog::where('session_id', $sessionId)
                ->where('user_id', auth()->id())
                ->first();

            if (! $importLog) {
                Log::warning("ImportLog no encontrado: {$sessionId}");

                return response()->json([
                    'success' => false,
                    'message' => 'Registro de importación no encontrado. La importación puede haber expirado o no haberse iniciado correctamente.',
                ], 404);
            }

            // Calcular tiempo transcurrido y estimado
            $tiempoTranscurrido = $importLog->tiempo_transcurrido;
            $tiempoEstimadoRestante = $importLog->tiempo_estimado_restante;
            $tiempoFormateado = $importLog->formatearTiempo($tiempoTranscurrido);
            $tiempoRestanteFormateado = $tiempoEstimadoRestante > 0 ? $importLog->formatearTiempo($tiempoEstimadoRestante) : null;

            return response()->json([
                'success' => true,
                'estado' => $importLog->estado,
                'progreso' => $importLog->progreso,
                'total' => $importLog->total,
                'procesados' => $importLog->procesados,
                'importados' => $importLog->importados,
                'duplicados' => $importLog->duplicados,
                'errores' => $importLog->errores ?? [],
                'errores_count' => $importLog->errores_count,
                'mensaje' => $importLog->mensaje ?? 'Procesando...',
                'log' => $importLog->logs ?? [],
                'tiempo_transcurrido' => $tiempoFormateado,
                'tiempo_estimado_restante' => $tiempoRestanteFormateado,
                'started_at' => $importLog->started_at?->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo progreso: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener progreso: '.$e->getMessage(),
            ], 500);
        }
    }
}
