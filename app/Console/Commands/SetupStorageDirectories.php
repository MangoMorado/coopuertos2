<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupStorageDirectories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:setup-directories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea todos los directorios necesarios para almacenamiento de la aplicación';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📁 Configurando directorios de almacenamiento...');

        $directorios = [
            // Storage logs y framework
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            public_path('uploads/pqrs'),
            public_path('uploads/carnets'),
            // Public storage
            public_path('storage/carnets'),
            public_path('storage/carnet_previews'),
            // Storage app
            storage_path('app/carnets'),
            storage_path('app/temp'),
            storage_path('app/temp_imports'),
            storage_path('app/public'),
        ];

        $creados = 0;
        $errores = 0;

        foreach ($directorios as $directorio) {
            $existe = File::exists($directorio);

            if (! $existe) {
                try {
                    File::makeDirectory($directorio, 0775, true);
                    $this->info("✅ Creado: {$directorio}");
                    $creados++;
                } catch (\Exception $e) {
                    $this->error("❌ Error al crear {$directorio}: {$e->getMessage()}");
                    $errores++;

                    continue;
                }
            } else {
                $this->line("⏭️  Ya existe: {$directorio}");
            }

            // Establecer permisos (incluso si ya existe)
            if (PHP_OS_FAMILY !== 'Windows') {
                try {
                    chmod($directorio, 0775);
                    // Si es un directorio de storage, también establecer permisos recursivos en archivos
                    if (str_contains($directorio, storage_path())) {
                        $this->setRecursivePermissions($directorio);
                    }
                } catch (\Exception $e) {
                    $this->warn("⚠️  No se pudieron establecer permisos en {$directorio}");
                }
            }
        }

        // Crear symlink si no existe
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! file_exists($link) && file_exists($target)) {
            try {
                if (PHP_OS_FAMILY !== 'Windows') {
                    symlink($target, $link);
                    $this->info("✅ Symlink creado: {$link} -> {$target}");
                } else {
                    $this->call('storage:link');
                }
            } catch (\Exception $e) {
                $this->warn("⚠️  No se pudo crear el symlink: {$e->getMessage()}");
                $this->line('   Ejecuta manualmente: php artisan storage:link');
            }
        }

        $this->newLine();
        $this->info('📊 Resumen:');
        $this->line("   ✅ Directorios creados: {$creados}");

        if ($errores > 0) {
            $this->line("   ❌ Errores: {$errores}");
            $this->newLine();
            $this->warn('⚠️  Algunos directorios no pudieron crearse automáticamente.');
            $this->line('   En producción, asegúrate de que el usuario del proceso PHP tenga permisos para escribir en:');
            $this->line('   - /public/uploads/pqrs (para adjuntos de PQRS)');
            $this->line('   - /public/uploads/carnets (para plantillas de carnets)');
            $this->line('   - /public/storage/ (para previsualizaciones de carnets)');
            $this->line('   - /storage/ (para carnets generados y archivos temporales)');

            return Command::FAILURE;
        }

        $this->info('   ✨ Configuración completada exitosamente.');

        return Command::SUCCESS;
    }

    /**
     * Establecer permisos recursivos en un directorio
     */
    protected function setRecursivePermissions(string $dir): void
    {
        if (! File::exists($dir) || PHP_OS_FAMILY === 'Windows') {
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    @chmod($item->getPathname(), 0775);
                } else {
                    @chmod($item->getPathname(), 0664);
                }
            }
            @chmod($dir, 0775);
        } catch (\Exception $e) {
            // Silenciar errores de permisos
        }
    }
}
