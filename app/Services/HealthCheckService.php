<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Servicio para verificar el estado de salud del sistema
 *
 * Proporciona métodos para verificar el estado de diversos componentes
 * del sistema: base de datos, colas, almacenamiento, extensiones PHP
 * y versiones. Utilizado por endpoints de health check y configuración.
 */
class HealthCheckService
{
    /**
     * Obtiene el estado completo de salud del sistema
     *
     * Verifica y retorna el estado de:
     * - Base de datos: conexión y estado
     * - Colas: trabajos pendientes y fallidos
     * - Almacenamiento: espacio total, usado, libre y porcentaje
     * - Versiones: PHP y Laravel
     * - Extensiones PHP: estado de cada extensión requerida
     *
     * @return array<string, mixed> Array con el estado de salud completo del sistema
     */
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

            $storageStatus = $usedPercentage > 90 ? 'error' : ($usedPercentage > 75 ? 'warning' : 'healthy');
            $status['storage'] = [
                'status' => $storageStatus,
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace),
                'percentage' => $usedPercentage,
                'total_bytes' => $totalSpace,
                'used_bytes' => $usedSpace,
                'free_bytes' => $freeSpace,
            ];

            if ($storageStatus === 'error') {
                $status['storage']['message'] = 'Espacio en disco crítico: '.$usedPercentage.'% utilizado';
            } elseif ($storageStatus === 'warning') {
                $status['storage']['message'] = 'Espacio en disco bajo: '.$usedPercentage.'% utilizado';
            }
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

    /**
     * Formatea bytes en unidades legibles (B, KB, MB, GB, TB)
     *
     * Convierte un número de bytes en una cadena formateada con la unidad
     * apropiada según el tamaño.
     *
     * @param  int  $bytes  Número de bytes a formatear
     * @param  int  $precision  Número de decimales (por defecto 2)
     * @return string Bytes formateados con unidad (ej: "1.5 GB")
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
}
