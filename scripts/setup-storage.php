<?php

/**
 * Script de configuración automática de directorios de almacenamiento
 *
 * Este script se ejecuta automáticamente durante composer install/post-install-cmd
 * y crea todos los directorios necesarios para el funcionamiento de la aplicación.
 */

// Detectar la ruta del proyecto
$projectPath = getcwd();
if (! $projectPath) {
    $projectPath = dirname(__DIR__);
}

// Función para crear directorio con permisos adecuados
function crearDirectorio(string $ruta, int $permisos = 0775): bool
{
    $creado = false;

    if (! file_exists($ruta)) {
        // Intentar crear el directorio padre primero
        $directorioPadre = dirname($ruta);
        if (! file_exists($directorioPadre)) {
            crearDirectorio($directorioPadre, $permisos);
        }

        try {
            if (mkdir($ruta, $permisos, true)) {
                echo "✅ Directorio creado: {$ruta}\n";
                $creado = true;
            }
        } catch (\Exception $e) {
            echo "⚠️  Error al crear directorio {$ruta}: {$e->getMessage()}\n";

            return false;
        }
    } else {
        $creado = true;
    }

    // Establecer permisos (incluso si ya existe)
    if ($creado && PHP_OS_FAMILY !== 'Windows') {
        try {
            chmod($ruta, $permisos);
        } catch (\Exception $e) {
            echo "⚠️  No se pudieron establecer permisos en {$ruta}: {$e->getMessage()}\n";
        }
    }

    return $creado;
}

echo "📁 Configurando directorios de almacenamiento...\n";

// Directorios en public/uploads y public/storage
$directoriosPublic = [
    $projectPath.'/public/uploads',
    $projectPath.'/public/uploads/carnets',
    $projectPath.'/public/storage/carnets',
    $projectPath.'/public/storage/carnet_previews',
];

// Directorios en storage
$directoriosStorage = [
    $projectPath.'/storage/logs',
    $projectPath.'/storage/framework/cache',
    $projectPath.'/storage/framework/sessions',
    $projectPath.'/storage/framework/views',
    $projectPath.'/storage/app/carnets',
    $projectPath.'/storage/app/temp',
    $projectPath.'/storage/app/temp_imports',
    $projectPath.'/storage/app/public',
];

// Crear todos los directorios
$creados = 0;
$errores = 0;

foreach ($directoriosPublic as $directorio) {
    if (crearDirectorio($directorio, 0775)) {
        $creados++;
    } else {
        $errores++;
    }
}

foreach ($directoriosStorage as $directorio) {
    if (crearDirectorio($directorio, 0775)) {
        $creados++;
    } else {
        $errores++;
    }
}

// Establecer permisos recursivos en public/storage y storage
if (PHP_OS_FAMILY !== 'Windows') {
    $directoriosPermisos = [
        $projectPath.'/public/storage',
        $projectPath.'/storage',
    ];

    foreach ($directoriosPermisos as $dir) {
        if (file_exists($dir)) {
            try {
                // Establecer permisos recursivos
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
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
                echo "⚠️  No se pudieron establecer permisos recursivos en {$dir}: {$e->getMessage()}\n";
            }
        }
    }
}

// Intentar crear el symlink de storage si no existe
$publicStorageLink = $projectPath.'/public/storage';
$storageAppPublic = $projectPath.'/storage/app/public';

if (! file_exists($publicStorageLink) && file_exists($storageAppPublic)) {
    try {
        if (PHP_OS_FAMILY === 'Windows') {
            // En Windows, crear un enlace simbólico (requiere permisos de administrador)
            if (is_dir($storageAppPublic)) {
                symlink($storageAppPublic, $publicStorageLink);
                echo "✅ Symlink creado: {$publicStorageLink}\n";
            }
        } else {
            // En Linux/Unix
            symlink($storageAppPublic, $publicStorageLink);
            echo "✅ Symlink creado: {$publicStorageLink}\n";
        }
    } catch (\Exception $e) {
        echo "⚠️  No se pudo crear el symlink (esto es normal en algunos entornos): {$e->getMessage()}\n";
        echo "   Puedes crearlo manualmente con: php artisan storage:link\n";
    }
}

echo "\n📊 Resumen:\n";
echo "   ✅ Directorios creados: {$creados}\n";
if ($errores > 0) {
    echo "   ⚠️  Errores: {$errores}\n";
    echo "\n⚠️  Algunos directorios no pudieron crearse automáticamente.\n";
    echo "   - /public/uploads/carnets (para plantillas de carnets)\n";
    echo "   - /public/storage/ (para previsualizaciones y ZIPs de carnets)\n";
    echo "   - /storage/app/ (para carnets generados y archivos temporales)\n";
    exit(1);
}

echo "   ✨ Configuración completada exitosamente.\n";
exit(0);
