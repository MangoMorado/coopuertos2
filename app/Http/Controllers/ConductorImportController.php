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

/**
 * Controlador web para importación de conductores
 *
 * Gestiona la importación de conductores desde archivos CSV/Excel mediante
 * formularios web y seguimiento de progreso mediante ImportLog. Utiliza
 * jobs en cola para procesar importaciones grandes en segundo plano.
 */
class ConductorImportController extends Controller
{
    /**
     * @param  ConductorImportFileValidator  $fileValidator  Validador de archivos de importación
     * @param  ConductorImportProgressTracker  $progressTracker  Seguimiento de progreso de importaciones
     */
    public function __construct(
        private ConductorImportFileValidator $fileValidator,
        private ConductorImportProgressTracker $progressTracker
    ) {}

    /**
     * Muestra el formulario de importación de conductores
     *
     * Si hay una sesión de importación activa, carga el ImportLog asociado
     * para mostrar el estado de la última importación.
     *
     * @return \Illuminate\Contracts\View\View Vista del formulario de importación
     */
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

    /**
     * Inicia el proceso de importación de conductores
     *
     * Valida el archivo CSV/Excel, lo guarda temporalmente, crea un ImportLog
     * en base de datos y encola un job (ProcesarImportacionConductores) para
     * procesar la importación en segundo plano. Aumenta el tiempo de ejecución
     * para archivos grandes. Retorna respuesta JSON si es petición AJAX o
     * redirige si es petición HTTP tradicional.
     *
     * @param  Request  $request  Request HTTP con el archivo a importar
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse Respuesta JSON o redirección
     *
     * @throws \Exception Si hay errores al guardar el archivo o crear el registro
     */
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

    /**
     * Obtiene el progreso de una importación en curso
     *
     * Retorna el estado actual de la importación incluyendo progreso, totales,
     * errores, logs, tiempo transcurrido y tiempo estimado restante.
     *
     * @param  string  $sessionId  Identificador único de la sesión de importación
     * @return \Illuminate\Http\JsonResponse Respuesta JSON con el estado de la importación
     */
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
