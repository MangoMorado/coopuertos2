<?php

namespace App\Mcp\Tools;

use App\Models\CarnetGenerationLog;
use App\Models\Conductor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para obtener URL o datos de carnet generado
 */
class DescargarCarnet extends Tool
{
    protected string $description = 'Obtiene la URL o datos de un carnet generado. Puede buscar por session_id (para generación masiva) o por conductor_id (para carnet individual).';

    public function handle(Request $request): Response|ResponseFactory
    {
        $sessionId = $request->get('session_id');
        $conductorId = $request->get('conductor_id');

        if ($sessionId) {
            // Buscar por session_id (generación masiva)
            $log = CarnetGenerationLog::where('session_id', $sessionId)->first();

            if (! $log) {
                return Response::error(
                    'Sesión de generación no encontrada.',
                    ['code' => 'NOT_FOUND', 'session_id' => $sessionId]
                );
            }

            if ($log->estado !== 'completado') {
                return Response::error(
                    'La generación aún no ha completado.',
                    [
                        'code' => 'NOT_COMPLETED',
                        'estado' => $log->estado,
                        'progreso' => [
                            'procesados' => $log->procesados,
                            'total' => $log->total,
                        ],
                    ]
                );
            }

            if (! $log->archivo_zip) {
                return Response::error(
                    'No hay archivo ZIP disponible para esta sesión.',
                    ['code' => 'NO_FILE', 'session_id' => $sessionId]
                );
            }

            $filePath = public_path('storage/'.$log->archivo_zip);

            // Si no existe en la ruta del log, intentar ruta esperada
            if (! File::exists($filePath)) {
                $expectedPath = public_path('storage/carnets/carnets_'.$sessionId.'.zip');
                if (File::exists($expectedPath)) {
                    $filePath = $expectedPath;
                    $log->update(['archivo_zip' => 'carnets/carnets_'.$sessionId.'.zip']);
                } else {
                    return Response::error(
                        'El archivo ZIP no se encontró.',
                        ['code' => 'FILE_NOT_FOUND', 'session_id' => $sessionId]
                    );
                }
            }

            $zipSize = File::size($filePath);
            $zipContent = File::get($filePath);
            $zipBase64 = base64_encode($zipContent);

            return Response::structured([
                'success' => true,
                'tipo' => 'masivo',
                'session_id' => $sessionId,
                'archivo' => [
                    'nombre' => 'carnets_'.$sessionId.'.zip',
                    'ruta' => $log->archivo_zip,
                    'url' => asset('storage/'.$log->archivo_zip),
                    'tamaño_bytes' => $zipSize,
                    'tamaño_formato' => $this->formatBytes($zipSize),
                    'contenido_base64' => $zipBase64,
                    'disponible' => true,
                ],
                'estadisticas' => [
                    'total' => $log->total,
                    'exitosos' => $log->exitosos,
                    'errores' => $log->errores,
                ],
            ]);
        } elseif ($conductorId) {
            // Buscar por conductor_id (carnet individual)
            $conductor = Conductor::find($conductorId);

            if (! $conductor) {
                return Response::error(
                    'Conductor no encontrado.',
                    ['code' => 'NOT_FOUND', 'conductor_id' => $conductorId]
                );
            }

            if (! $conductor->ruta_carnet) {
                return Response::error(
                    'El conductor no tiene un carnet generado.',
                    [
                        'code' => 'NO_CARNET',
                        'conductor_id' => $conductorId,
                        'hint' => 'Usa la herramienta generar_carnet para crear un carnet para este conductor.',
                    ]
                );
            }

            $filePath = storage_path('app/'.$conductor->ruta_carnet);

            if (! File::exists($filePath)) {
                return Response::error(
                    'El archivo del carnet no se encontró en el sistema de archivos.',
                    ['code' => 'FILE_NOT_FOUND', 'ruta' => $conductor->ruta_carnet]
                );
            }

            $fileSize = File::size($filePath);
            $fileContent = File::get($filePath);
            $fileBase64 = base64_encode($fileContent);

            return Response::structured([
                'success' => true,
                'tipo' => 'individual',
                'conductor' => [
                    'id' => $conductor->id,
                    'nombres' => $conductor->nombres,
                    'apellidos' => $conductor->apellidos,
                    'cedula' => $conductor->cedula,
                ],
                'archivo' => [
                    'nombre' => basename($conductor->ruta_carnet),
                    'ruta' => $conductor->ruta_carnet,
                    'url' => asset('storage/'.$conductor->ruta_carnet),
                    'tamaño_bytes' => $fileSize,
                    'tamaño_formato' => $this->formatBytes($fileSize),
                    'contenido_base64' => $fileBase64,
                    'formato' => 'PDF',
                    'disponible' => true,
                ],
            ]);
        } else {
            return Response::error(
                'Debes proporcionar session_id o conductor_id.',
                ['code' => 'MISSING_PARAMETER', 'required' => ['session_id', 'conductor_id']]
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
        return [
            'session_id' => $schema->string()->nullable()->description('ID de sesión de generación masiva (para obtener ZIP de carnets masivos)'),
            'conductor_id' => $schema->integer()->nullable()->description('ID del conductor (para obtener carnet individual)'),
        ];
    }

    public function name(): string
    {
        return 'descargar_carnet';
    }
}
