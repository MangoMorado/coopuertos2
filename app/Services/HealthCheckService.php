<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class HealthCheckService
{
    public function getHealthStatus(): array
    {
        $status = [];

        // Estado de la base de datos
        try {
            DB::connection()->getPdo();
            $status['database'] = [
                'status' => 'healthy',
                'message' => 'Conexión establecida',
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            $status['database'] = [
                'status' => 'error',
                'message' => 'Error de conexión: '.$e->getMessage(),
                'connection' => config('database.default'),
            ];
        }

        // Jobs en cola
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $status['queue'] = [
                'status' => $failedJobs > 0 ? 'warning' : ($pendingJobs > 100 ? 'warning' : 'healthy'),
                'pending' => $pendingJobs,
                'failed' => $failedJobs,
                'connection' => config('queue.default'),
            ];
        } catch (\Exception $e) {
            $status['queue'] = [
                'status' => 'error',
                'message' => 'No se pudo verificar: '.$e->getMessage(),
            ];
        }

        // Espacio en disco de storage
        try {
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            $usedPercentage = $totalSpace > 0 ? round(($usedSpace / $totalSpace) * 100, 2) : 0;

            $status['storage'] = [
                'status' => $usedPercentage > 90 ? 'error' : ($usedPercentage > 75 ? 'warning' : 'healthy'),
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace),
                'percentage' => $usedPercentage,
                'total_bytes' => $totalSpace,
                'used_bytes' => $usedSpace,
                'free_bytes' => $freeSpace,
            ];
        } catch (\Exception $e) {
            $status['storage'] = [
                'status' => 'error',
                'message' => 'No se pudo verificar: '.$e->getMessage(),
            ];
        }

        // Versiones
        $status['versions'] = [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
        ];

        // Extensiones PHP
        $status['php_extensions'] = $this->checkPhpExtensions();

        return $status;
    }

    private function checkPhpExtensions(): array
    {
        $requiredExtensions = [
            'imagick' => 'Imagick (requerida para procesamiento de imágenes)',
            'pdo' => 'PDO (requerida para base de datos)',
            'pdo_mysql' => 'PDO MySQL (requerida para MySQL)',
            'mbstring' => 'MBString (requerida por Laravel)',
            'openssl' => 'OpenSSL (requerida por Laravel)',
            'tokenizer' => 'Tokenizer (requerida por Laravel)',
            'xml' => 'XML (requerida por Laravel)',
            'ctype' => 'Ctype (requerida por Laravel)',
            'json' => 'JSON (requerida por Laravel)',
            'bcmath' => 'BCMath (requerida por Laravel)',
            'fileinfo' => 'Fileinfo (requerida por Laravel)',
        ];

        $extensions = [];
        $allHealthy = true;

        foreach ($requiredExtensions as $extension => $description) {
            $loaded = extension_loaded($extension);
            $extensions[$extension] = [
                'loaded' => $loaded,
                'description' => $description,
                'status' => $loaded ? 'healthy' : 'error',
            ];

            if (! $loaded) {
                $allHealthy = false;
            }
        }

        return [
            'status' => $allHealthy ? 'healthy' : 'error',
            'extensions' => $extensions,
        ];
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
}
