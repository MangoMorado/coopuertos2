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
            // Public
            public_path('uploads/conductores'),
            public_path('uploads/vehiculos'),
            public_path('uploads/pqrs'),
            public_path('uploads/carnets'),
            public_path('storage/carnets'),
            // Storage
            storage_path('app/carnets'),
            storage_path('app/temp'),
            storage_path('app/temp_imports'),
            storage_path('app/public'),
        ];

        $creados = 0;
        $errores = 0;

        foreach ($directorios as $directorio) {
            if (File::exists($directorio)) {
                $this->line("⏭️  Ya existe: {$directorio}");

                continue;
            }

            try {
                File::makeDirectory($directorio, 0755, true);
                $this->info("✅ Creado: {$directorio}");
                $creados++;
            } catch (\Exception $e) {
                $this->error("❌ Error al crear {$directorio}: {$e->getMessage()}");
                $errores++;
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
            $this->line('   - /public/uploads/');
            $this->line('   - /storage/app/');

            return Command::FAILURE;
        }

        $this->info('   ✨ Configuración completada exitosamente.');

        return Command::SUCCESS;
    }
}
