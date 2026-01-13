<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para consultar logs de Laravel
 */
class ObtenerLogsLaravel extends Tool
{
    protected string $description = 'Consulta los logs de Laravel desde el archivo de log. Permite filtrar por nivel, buscar texto y limitar resultados.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos - solo usuarios con acceso a configuración
        if (! Auth::user()->hasPermissionTo('ver configuracion')) {
            return Response::error(
                'No tienes permisos para ver logs de Laravel.',
                [
                    'code' => 'PERMISSION_DENIED',
                    'hint' => 'Se requiere el permiso "ver configuracion" para acceder a esta información.',
                ]
            );
        }

        $validated = $request->validate([
            'level' => ['nullable', 'string', 'in:debug,info,notice,warning,error,critical,alert,emergency'],
            'search' => ['nullable', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'file' => ['nullable', 'string'],
        ], [
            'level.in' => 'El nivel debe ser uno de: debug, info, notice, warning, error, critical, alert, emergency',
            'limit.max' => 'El límite máximo es 500 líneas',
            'search.max' => 'La búsqueda no puede exceder 200 caracteres',
        ]);

        try {
            // Determinar archivo de log
            $logFile = $validated['file'] ?? storage_path('logs/laravel.log');

            // Verificar que el archivo existe
            if (! File::exists($logFile)) {
                return Response::error(
                    'El archivo de log no existe.',
                    [
                        'code' => 'LOG_FILE_NOT_FOUND',
                        'file' => $logFile,
                    ]
                );
            }

            // Leer el archivo completo
            $content = File::get($logFile);
            $lines = explode("\n", $content);

            // Filtrar líneas según criterios
            $filteredLines = [];
            $level = $validated['level'] ?? null;
            $search = $validated['search'] ?? null;
            $limit = $validated['limit'] ?? 100;

            // Leer desde el final (logs más recientes)
            $lines = array_reverse($lines);

            foreach ($lines as $line) {
                if (count($filteredLines) >= $limit) {
                    break;
                }

                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Filtrar por nivel
                if ($level && ! str_contains(strtolower($line), strtolower($level))) {
                    continue;
                }

                // Filtrar por búsqueda de texto
                if ($search && ! str_contains(strtolower($line), strtolower($search))) {
                    continue;
                }

                $filteredLines[] = $line;
            }

            // Revertir para mostrar más recientes primero
            $filteredLines = array_reverse($filteredLines);

            return Response::structured([
                'file' => $logFile,
                'total_lines' => count($filteredLines),
                'file_size' => File::size($logFile),
                'file_size_formatted' => $this->formatBytes(File::size($logFile)),
                'lines' => $filteredLines,
                'filters' => [
                    'level' => $level,
                    'search' => $search,
                ],
            ]);
        } catch (\Exception $e) {
            return Response::error(
                'Error al leer logs de Laravel: '.$e->getMessage(),
                [
                    'code' => 'LOG_READ_ERROR',
                ]
            );
        }
    }

    /**
     * Formatea bytes en unidades legibles
     */
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
            'level' => $schema->string()
                ->enum(['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])
                ->description('Filtrar por nivel de log'),
            'search' => $schema->string()
                ->maxLength(200)
                ->description('Buscar texto en los logs'),
            'limit' => $schema->integer()
                ->default(100)
                ->description('Número máximo de líneas a retornar (máximo 500)'),
            'file' => $schema->string()
                ->description('Ruta del archivo de log (por defecto: storage/logs/laravel.log)'),
        ];
    }

    public function name(): string
    {
        return 'obtener_logs_laravel';
    }
}
