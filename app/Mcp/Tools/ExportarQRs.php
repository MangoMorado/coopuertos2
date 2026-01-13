<?php

namespace App\Mcp\Tools;

use App\Models\Conductor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

/**
 * Herramienta MCP para exportar códigos QR de conductores
 */
class ExportarQRs extends Tool
{
    protected string $description = 'Exporta todos los códigos QR de conductores en formato SVG y los comprime en un archivo ZIP. Requiere permisos de creación de carnets.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('crear carnets')) {
            return Response::error(
                'No tienes permisos para exportar QRs.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'crear carnets']
            );
        }

        try {
            // Obtener todos los conductores
            $conductores = Conductor::with(['asignacionActiva.vehicle'])->get();

            if ($conductores->isEmpty()) {
                return Response::error(
                    'No hay conductores para exportar QRs.',
                    ['code' => 'NO_CONDUCTORS']
                );
            }

            // Crear directorio temporal para QRs
            $tempDir = storage_path('app/temp/qrs_'.time());
            if (! File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            $qrGenerados = 0;
            $errores = [];

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
                    $errores[] = [
                        'conductor_id' => $conductor->id,
                        'cedula' => $conductor->cedula,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if ($qrGenerados === 0) {
                File::deleteDirectory($tempDir);

                return Response::error(
                    'No se pudieron generar QRs.',
                    ['code' => 'GENERATION_FAILED', 'errores' => $errores]
                );
            }

            // Crear ZIP con todos los QRs
            $zipFileName = 'qrs_conductores_'.date('YmdHis').'.zip';
            $zipPath = storage_path('app/temp/'.$zipFileName);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                File::deleteDirectory($tempDir);

                return Response::error(
                    'No se pudo crear el archivo ZIP.',
                    ['code' => 'ZIP_CREATION_FAILED']
                );
            }

            // Agregar todos los archivos SVG al ZIP
            $files = File::files($tempDir);
            foreach ($files as $file) {
                $zip->addFile($file->getPathname(), $file->getFilename());
            }

            $zip->close();

            // Limpiar directorio temporal de QRs
            File::deleteDirectory($tempDir);

            // Leer el ZIP y convertirlo a base64 para retornarlo
            $zipContent = File::get($zipPath);
            $zipBase64 = base64_encode($zipContent);
            $zipSize = File::size($zipPath);

            // Eliminar el ZIP temporal
            File::delete($zipPath);

            return Response::structured([
                'success' => true,
                'message' => 'QRs exportados exitosamente',
                'archivo' => [
                    'nombre' => $zipFileName,
                    'tamaño_bytes' => $zipSize,
                    'tamaño_formato' => $this->formatBytes($zipSize),
                    'formato' => 'ZIP',
                    'contenido_base64' => $zipBase64,
                ],
                'estadisticas' => [
                    'total_conductores' => $conductores->count(),
                    'qrs_generados' => $qrGenerados,
                    'errores' => count($errores),
                ],
                'errores' => $errores,
                'instrucciones' => [
                    'El archivo ZIP está codificado en base64 en el campo contenido_base64.',
                    'Decodifica el base64 para obtener el archivo ZIP.',
                    'El ZIP contiene archivos SVG con los códigos QR de cada conductor.',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error exportando QRs: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::error(
                'Error al exportar QRs: '.$e->getMessage(),
                ['code' => 'EXPORT_ERROR']
            );
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function name(): string
    {
        return 'exportar_qrs';
    }
}
