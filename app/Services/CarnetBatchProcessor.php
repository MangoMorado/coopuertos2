<?php

namespace App\Services;

use App\Models\CarnetDownload;
use App\Models\CarnetTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class CarnetBatchProcessor
{
    public function __construct(
        protected CarnetGeneratorService $generator
    ) {}

    /**
     * Procesa múltiples carnets y crea un ZIP
     */
    public function procesarCarnets(string $sessionId, CarnetTemplate $template, Collection $conductores): void
    {
        $download = CarnetDownload::where('session_id', $sessionId)->first();

        $this->agregarLog($download, 'info', 'Iniciando proceso de generación masiva de carnets', [
            'total_conductores' => $conductores->count(),
        ]);

        $tempDir = storage_path('app/temp/carnets_'.$sessionId);
        File::makeDirectory($tempDir, 0755, true);

        $this->agregarLog($download, 'info', 'Directorio temporal creado', [
            'ruta' => $tempDir,
        ]);

        try {
            $procesados = 0;
            $errores = 0;

            $this->agregarLog($download, 'info', 'Iniciando generación de carnets individuales');

            foreach ($conductores as $index => $conductor) {
                try {
                    $this->agregarLog($download, 'debug', 'Iniciando generación de carnet', [
                        'conductor_id' => $conductor->id,
                        'cedula' => $conductor->cedula,
                        'nombres' => $conductor->nombres.' '.$conductor->apellidos,
                        'numero' => ($index + 1).'/'.$conductores->count(),
                    ]);

                    // Actualizar progreso antes de generar
                    $download->refresh();
                    $download->procesados = $index; // Mostrar el número que está procesando
                    $download->save();

                    // Generar carnet PDF uno por uno
                    $pdfPath = $this->generator->generarCarnetPDF($conductor, $template, $tempDir);

                    $procesados++;

                    // Verificar que el PDF se haya creado correctamente
                    if (! File::exists($pdfPath)) {
                        throw new \Exception('El archivo PDF no se generó correctamente: '.$pdfPath);
                    }

                    $fileSize = File::size($pdfPath);

                    $this->agregarLog($download, 'success', 'Carnet generado exitosamente', [
                        'conductor_id' => $conductor->id,
                        'cedula' => $conductor->cedula,
                        'archivo' => basename($pdfPath),
                        'tamaño' => number_format($fileSize / 1024, 2).' KB',
                        'progreso' => $procesados.'/'.$conductores->count(),
                    ]);

                    // Actualizar progreso después de generar
                    $download->refresh();
                    $download->procesados = $procesados;
                    $download->save();

                    // Pequeña pausa para evitar sobrecarga
                    usleep(100000); // 0.1 segundos
                } catch (\Exception $e) {
                    $errores++;
                    $mensajeError = 'Error generando carnet para conductor '.$conductor->id.': '.$e->getMessage();
                    Log::error($mensajeError);
                    Log::error('Stack trace: '.$e->getTraceAsString());

                    $this->agregarLog($download, 'error', 'Error al generar carnet', [
                        'conductor_id' => $conductor->id,
                        'cedula' => $conductor->cedula ?? 'N/A',
                        'nombres' => $conductor->nombres.' '.$conductor->apellidos,
                        'error' => $e->getMessage(),
                        'archivo' => $e->getFile(),
                        'linea' => $e->getLine(),
                    ]);

                    // Actualizar progreso aunque haya error
                    $download->refresh();
                    $download->procesados = $index + 1;
                    $download->save();

                    continue;
                }
            }

            $this->agregarLog($download, 'info', 'Generación de carnets completada', [
                'procesados' => $procesados,
                'errores' => $errores,
                'total' => $conductores->count(),
            ]);

            // Contar archivos generados
            $archivosGenerados = count(File::files($tempDir));
            $this->agregarLog($download, 'info', 'Archivos listos para comprimir', [
                'total_archivos' => $archivosGenerados,
                'esperados' => $procesados,
            ]);

            // Crear archivo ZIP
            $this->agregarLog($download, 'info', 'Creando archivo ZIP');
            $zipPath = $this->crearZip($tempDir, $sessionId);

            $this->agregarLog($download, 'success', 'Archivo ZIP creado', [
                'ruta' => $zipPath,
            ]);

            // Mover ZIP a storage público
            $this->agregarLog($download, 'info', 'Moviendo archivo ZIP a directorio público');
            $publicZipPath = $this->moverZip($zipPath, $sessionId);

            $this->agregarLog($download, 'success', 'Archivo ZIP movido a directorio público', [
                'ruta' => $publicZipPath,
            ]);

            // Actualizar registro
            $download->refresh();
            $download->estado = 'completado';
            $download->archivo_zip = 'carnets/carnets_'.$sessionId.'.zip';
            $download->save();

            $this->agregarLog($download, 'success', 'Proceso completado exitosamente', [
                'archivo_zip' => $download->archivo_zip,
            ]);

            // Limpiar archivos temporales
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
                $this->agregarLog($download, 'info', 'Archivos temporales eliminados');
            }
        } catch (\Exception $e) {
            $mensajeError = 'Error fatal en el proceso: '.$e->getMessage();
            Log::error($mensajeError);
            Log::error('Stack trace: '.$e->getTraceAsString());

            $this->agregarLog($download, 'error', 'Error fatal en el proceso', [
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ]);

            $download->refresh();
            $download->estado = 'error';
            $download->error = $e->getMessage();
            $download->save();

            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Agrega un log al registro de descarga (se guarda inmediatamente para tiempo real)
     */
    protected function agregarLog(CarnetDownload $download, string $tipo, string $mensaje, array $data = []): void
    {
        try {
            // Refrescar desde la base de datos para obtener logs más recientes
            $download->refresh();

            // Obtener logs existentes
            $logs = $download->logs ?? [];

            // Agregar nuevo log
            $logs[] = [
                'timestamp' => now()->toDateTimeString(),
                'tipo' => $tipo, // info, success, error, debug, warning
                'mensaje' => $mensaje,
                'data' => $data,
            ];

            // Guardar inmediatamente para que se refleje en tiempo real
            $download->logs = $logs;
            $download->save();

            // Forzar actualización en la base de datos
            $download->touch();
        } catch (\Exception $e) {
            // Si falla el log, registrar en Laravel log pero continuar
            Log::warning('Error guardando log en tiempo real: '.$e->getMessage());
        }
    }

    /**
     * Crea un archivo ZIP con todos los carnets
     */
    protected function crearZip(string $tempDir, string $sessionId): string
    {
        $zipPath = storage_path('app/temp/carnets_'.$sessionId.'.zip');
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('No se pudo crear el archivo ZIP: '.$zip->getStatusString());
        }

        $files = File::files($tempDir);
        $archivosAgregados = 0;

        foreach ($files as $file) {
            // Solo agregar archivos PDF
            if (strtolower($file->getExtension()) === 'pdf') {
                if ($zip->addFile($file->getPathname(), $file->getFilename())) {
                    $archivosAgregados++;
                } else {
                    Log::warning('No se pudo agregar archivo al ZIP: '.$file->getFilename());
                }
            }
        }

        $zip->close();

        if ($archivosAgregados === 0) {
            throw new \Exception('No se encontraron archivos PDF para comprimir');
        }

        Log::info("ZIP creado con $archivosAgregados archivos");

        return $zipPath;
    }

    /**
     * Mueve el ZIP al directorio público
     */
    protected function moverZip(string $zipPath, string $sessionId): string
    {
        $publicZipDir = public_path('storage/carnets');
        if (! File::exists($publicZipDir)) {
            File::makeDirectory($publicZipDir, 0755, true);
        }

        $publicZipPath = $publicZipDir.'/carnets_'.$sessionId.'.zip';
        if (File::exists($zipPath)) {
            File::move($zipPath, $publicZipPath);
        }

        return $publicZipPath;
    }
}
