<?php

namespace Tests\Unit\Helpers;

use App\Helpers\StorageHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StorageHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Limpiar directorios de prueba creados durante los tests
        $testDirs = [
            storage_path('app/test_helper'),
            storage_path('app/test_helper_nested'),
        ];

        foreach ($testDirs as $dir) {
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }

        parent::tearDown();
    }

    public function test_storage_helper_creates_directories(): void
    {
        $testPath = storage_path('app/test_helper');

        // Asegurar que el directorio no existe antes del test
        if (File::exists($testPath)) {
            File::deleteDirectory($testPath);
        }

        $this->assertFalse(File::exists($testPath), 'El directorio no debería existir antes del test');

        // Crear el directorio usando el helper
        $result = StorageHelper::ensureDirectoryExists($testPath);

        $this->assertTrue($result, 'El helper debe retornar true cuando crea el directorio');
        $this->assertTrue(File::exists($testPath), 'El directorio debe existir después de crearlo');
        $this->assertTrue(File::isDirectory($testPath), 'La ruta debe ser un directorio');
    }

    public function test_storage_helper_handles_existing_directories(): void
    {
        $testPath = storage_path('app/test_helper');

        // Crear el directorio primero
        File::makeDirectory($testPath, 0755, true);

        $this->assertTrue(File::exists($testPath), 'El directorio debe existir antes de llamar al helper');

        // Llamar al helper cuando el directorio ya existe
        $result = StorageHelper::ensureDirectoryExists($testPath);

        $this->assertTrue($result, 'El helper debe retornar true cuando el directorio ya existe');
        $this->assertTrue(File::exists($testPath), 'El directorio debe seguir existiendo');
        $this->assertTrue(File::isDirectory($testPath), 'La ruta debe seguir siendo un directorio');

        // Verificar que se puede crear un directorio anidado
        $nestedPath = storage_path('app/test_helper_nested/level1/level2');

        if (File::exists($nestedPath)) {
            File::deleteDirectory(dirname($nestedPath, 2));
        }

        $this->assertFalse(File::exists($nestedPath), 'El directorio anidado no debería existir antes del test');

        $result = StorageHelper::ensureDirectoryExists($nestedPath);

        $this->assertTrue($result, 'El helper debe crear directorios anidados');
        $this->assertTrue(File::exists($nestedPath), 'El directorio anidado debe existir');
        $this->assertTrue(File::isDirectory($nestedPath), 'La ruta anidada debe ser un directorio');
    }
}
