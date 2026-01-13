<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Comando para generar documentación PHPDoc usando phpDocumentor
 */
class GenerateDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar documentación HTML a partir de bloques PHPDoc usando phpDocumentor';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generando documentación PHPDoc...');

        // Buscar el ejecutable de phpDocumentor
        $phpdocPath = $this->findPhpdocExecutable();

        if (! $phpdocPath) {
            $this->error('phpDocumentor no está instalado.');
            $this->info('Instala phpDocumentor ejecutando: composer require --dev phpdocumentor/shim');
            $this->newLine();
            $this->info('Nota: phpdocumentor/shim es la versión recomendada que evita conflictos de dependencias.');

            return Command::FAILURE;
        }

        // Verificar si existe el archivo de configuración
        $configPath = base_path('phpdoc.dist.xml');
        if (! File::exists($configPath)) {
            $this->error('Archivo de configuración phpdoc.dist.xml no encontrado.');
            $this->info('Crea el archivo de configuración según el plan de implementación.');

            return Command::FAILURE;
        }

        // Crear directorio de salida si no existe
        $outputPath = base_path('docs/api');
        if (! File::isDirectory($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
            $this->info("Directorio de salida creado: {$outputPath}");
        }

        // Ejecutar phpDocumentor según la documentación oficial
        // https://docs.phpdoc.org/guide/getting-started/installing.html#as-a-dependency-using-composer
        $this->info('Ejecutando phpDocumentor...');
        $this->newLine();

        // Usar la sintaxis recomendada con el archivo de configuración
        // php vendor/bin/phpdoc run --config=phpdoc.dist.xml
        $phpBinary = PHP_BINARY;
        $command = escapeshellarg($phpBinary).' '.escapeshellarg($phpdocPath).' run --config='.escapeshellarg($configPath);

        $output = [];
        $returnVar = 0;
        exec("{$command} 2>&1", $output, $returnVar);

        // Mostrar salida
        foreach ($output as $line) {
            $this->line($line);
        }

        if ($returnVar === 0) {
            $this->newLine();
            $this->info('✓ Documentación generada exitosamente!');
            $this->info("Ubicación: {$outputPath}");
            $this->info('Accede a la documentación desde: /documentacion');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->error('✗ Error al generar la documentación.');
        $this->info('Revisa los mensajes de error arriba.');

        return Command::FAILURE;
    }

    /**
     * Buscar el ejecutable de phpDocumentor
     *
     * Según la documentación: https://docs.phpdoc.org/guide/getting-started/installing.html
     * Con phpdocumentor/shim, el ejecutable está en vendor/bin/phpdoc
     *
     * @return string|null Ruta del ejecutable o null si no se encuentra
     */
    private function findPhpdocExecutable(): ?string
    {
        $basePath = base_path('vendor/bin');
        $possiblePaths = [
            // Windows con extensión .bat
            $basePath.'/phpdoc.bat',
            // Windows con extensión .cmd
            $basePath.'/phpdoc.cmd',
            // Unix/Linux sin extensión
            $basePath.'/phpdoc',
        ];

        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
