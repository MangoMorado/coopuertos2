<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para obtener métricas detalladas de las colas de trabajos
 */
class ObtenerMetricasColas extends Tool
{
    protected string $description = 'Obtiene métricas detalladas de los jobs en cola: trabajos pendientes, fallidos, y estadísticas por tipo de job.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos - solo usuarios con acceso a configuración
        if (! Auth::user()->hasPermissionTo('ver configuracion')) {
            return Response::error(
                'No tienes permisos para ver las métricas de colas.',
                [
                    'code' => 'PERMISSION_DENIED',
                    'hint' => 'Se requiere el permiso "ver configuracion" para acceder a esta información.',
                ]
            );
        }

        try {
            // Trabajos pendientes
            $pendingJobs = DB::table('jobs')->count();
            $pendingJobsDetails = DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get()
                ->mapWithKeys(fn ($item) => [$item->queue => $item->count])
                ->toArray();

            // Trabajos fallidos
            $failedJobs = DB::table('failed_jobs')->count();
            $failedJobsRecent = DB::table('failed_jobs')
                ->select('queue', 'failed_at', 'exception')
                ->orderBy('failed_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($job) {
                    return [
                        'queue' => $job->queue,
                        'failed_at' => $job->failed_at,
                        'exception' => substr($job->exception, 0, 200), // Primeros 200 caracteres
                    ];
                })
                ->toArray();

            // Análisis de jobs pendientes por tipo (si tienen payload)
            $jobTypes = [];
            if ($pendingJobs > 0) {
                $jobs = DB::table('jobs')
                    ->select('payload')
                    ->limit(100) // Analizar máximo 100 jobs
                    ->get();

                foreach ($jobs as $job) {
                    $payload = json_decode($job->payload, true);
                    if (isset($payload['displayName'])) {
                        $jobType = $payload['displayName'];
                        $jobTypes[$jobType] = ($jobTypes[$jobType] ?? 0) + 1;
                    }
                }
            }

            return Response::structured([
                'pending' => [
                    'total' => $pendingJobs,
                    'by_queue' => $pendingJobsDetails,
                    'by_type' => $jobTypes,
                ],
                'failed' => [
                    'total' => $failedJobs,
                    'recent' => $failedJobsRecent,
                ],
                'connection' => config('queue.default'),
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            return Response::error(
                'Error al obtener métricas de colas: '.$e->getMessage(),
                [
                    'code' => 'QUEUE_METRICS_ERROR',
                ]
            );
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function name(): string
    {
        return 'obtener_metricas_colas';
    }
}
