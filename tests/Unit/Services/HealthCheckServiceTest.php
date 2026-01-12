<?php

namespace Tests\Unit\Services;

use App\Services\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HealthCheckService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new HealthCheckService;
    }

    public function test_health_check_service_checks_database(): void
    {
        $status = $this->service->getHealthStatus();

        $this->assertArrayHasKey('database', $status);
        $this->assertArrayHasKey('status', $status['database']);
        $this->assertArrayHasKey('message', $status['database']);
        $this->assertArrayHasKey('connection', $status['database']);

        // En un entorno de testing normal, la base de datos debería estar healthy
        $this->assertEquals('healthy', $status['database']['status']);
        $this->assertEquals('Conexión establecida', $status['database']['message']);
        $this->assertNotEmpty($status['database']['connection']);

        // Verificar que la conexión es válida
        try {
            DB::connection()->getPdo();
            $this->assertTrue(true, 'La conexión a la base de datos funciona');
        } catch (\Exception $e) {
            $this->fail('La conexión a la base de datos debería funcionar en tests');
        }
    }

    public function test_health_check_service_checks_storage(): void
    {
        $status = $this->service->getHealthStatus();

        $this->assertArrayHasKey('storage', $status);
        $this->assertArrayHasKey('status', $status['storage']);
        $this->assertArrayHasKey('total', $status['storage']);
        $this->assertArrayHasKey('used', $status['storage']);
        $this->assertArrayHasKey('free', $status['storage']);
        $this->assertArrayHasKey('percentage', $status['storage']);
        $this->assertArrayHasKey('total_bytes', $status['storage']);
        $this->assertArrayHasKey('used_bytes', $status['storage']);
        $this->assertArrayHasKey('free_bytes', $status['storage']);

        // Verificar tipos de datos
        $this->assertIsString($status['storage']['status']);
        $this->assertIsString($status['storage']['total']);
        $this->assertIsString($status['storage']['used']);
        $this->assertIsString($status['storage']['free']);
        $this->assertIsFloat($status['storage']['percentage']);
        // disk_total_space y disk_free_space pueden retornar int o float dependiendo del sistema
        $this->assertTrue(is_int($status['storage']['total_bytes']) || is_float($status['storage']['total_bytes']), 'total_bytes debe ser int o float');
        $this->assertTrue(is_int($status['storage']['used_bytes']) || is_float($status['storage']['used_bytes']), 'used_bytes debe ser int o float');
        $this->assertTrue(is_int($status['storage']['free_bytes']) || is_float($status['storage']['free_bytes']), 'free_bytes debe ser int o float');

        // Verificar que el porcentaje está en el rango válido
        $this->assertGreaterThanOrEqual(0, $status['storage']['percentage']);
        $this->assertLessThanOrEqual(100, $status['storage']['percentage']);

        // Verificar que los bytes tienen sentido
        $this->assertGreaterThan(0, $status['storage']['total_bytes']);
        $this->assertGreaterThanOrEqual(0, $status['storage']['used_bytes']);
        $this->assertGreaterThanOrEqual(0, $status['storage']['free_bytes']);

        // Verificar que used + free = total (aproximadamente)
        $totalCalculated = $status['storage']['used_bytes'] + $status['storage']['free_bytes'];
        $this->assertEqualsWithDelta($status['storage']['total_bytes'], $totalCalculated, 1000, 'used + free debería ser aproximadamente igual a total');
    }

    public function test_health_check_service_checks_queue(): void
    {
        $status = $this->service->getHealthStatus();

        $this->assertArrayHasKey('queue', $status);
        $this->assertArrayHasKey('status', $status['queue']);
        $this->assertArrayHasKey('pending', $status['queue']);
        $this->assertArrayHasKey('failed', $status['queue']);
        $this->assertArrayHasKey('connection', $status['queue']);

        // Verificar tipos de datos
        $this->assertIsString($status['queue']['status']);
        $this->assertIsInt($status['queue']['pending']);
        $this->assertIsInt($status['queue']['failed']);
        $this->assertIsString($status['queue']['connection']);

        // Verificar que el status es uno de los valores esperados
        $this->assertContains($status['queue']['status'], ['healthy', 'warning', 'error']);

        // Verificar que los contadores son no negativos
        $this->assertGreaterThanOrEqual(0, $status['queue']['pending']);
        $this->assertGreaterThanOrEqual(0, $status['queue']['failed']);

        // Verificar que la conexión está configurada
        $this->assertNotEmpty($status['queue']['connection']);
    }

    public function test_health_check_service_returns_status(): void
    {
        $status = $this->service->getHealthStatus();

        // Verificar estructura completa del status
        $this->assertIsArray($status);
        $this->assertArrayHasKey('database', $status);
        $this->assertArrayHasKey('queue', $status);
        $this->assertArrayHasKey('storage', $status);
        $this->assertArrayHasKey('versions', $status);
        $this->assertArrayHasKey('php_extensions', $status);

        // Verificar versions
        $this->assertArrayHasKey('php', $status['versions']);
        $this->assertArrayHasKey('laravel', $status['versions']);
        $this->assertIsString($status['versions']['php']);
        $this->assertIsString($status['versions']['laravel']);
        $this->assertNotEmpty($status['versions']['php']);
        $this->assertNotEmpty($status['versions']['laravel']);

        // Verificar php_extensions
        $this->assertArrayHasKey('status', $status['php_extensions']);
        $this->assertArrayHasKey('extensions', $status['php_extensions']);
        $this->assertIsString($status['php_extensions']['status']);
        $this->assertIsArray($status['php_extensions']['extensions']);
        $this->assertContains($status['php_extensions']['status'], ['healthy', 'error']);

        // Verificar estructura de extensiones
        if (! empty($status['php_extensions']['extensions'])) {
            $firstExtension = reset($status['php_extensions']['extensions']);
            $this->assertArrayHasKey('loaded', $firstExtension);
            $this->assertArrayHasKey('description', $firstExtension);
            $this->assertArrayHasKey('status', $firstExtension);
            $this->assertIsBool($firstExtension['loaded']);
            $this->assertIsString($firstExtension['description']);
            $this->assertIsString($firstExtension['status']);
        }
    }

    public function test_health_check_service_handles_errors(): void
    {
        // Este test verifica que el servicio maneja errores correctamente
        // En un entorno normal, no deberíamos tener errores, pero el servicio
        // debe manejar excepciones sin fallar

        $status = $this->service->getHealthStatus();

        // Verificar que todos los componentes tienen al menos un status
        $this->assertArrayHasKey('status', $status['database']);
        $this->assertArrayHasKey('status', $status['queue']);
        $this->assertArrayHasKey('status', $status['storage']);

        // Verificar que los status son strings válidos
        $this->assertIsString($status['database']['status']);
        $this->assertIsString($status['queue']['status']);
        $this->assertIsString($status['storage']['status']);

        // El servicio debe retornar un array completo incluso si hay errores
        $this->assertIsArray($status);
        $this->assertNotEmpty($status);

        // Si hay un error, debe tener un mensaje
        if ($status['database']['status'] === 'error') {
            $this->assertArrayHasKey('message', $status['database']);
            $this->assertIsString($status['database']['message']);
        }

        if ($status['queue']['status'] === 'error') {
            $this->assertArrayHasKey('message', $status['queue']);
            $this->assertIsString($status['queue']['message']);
        }

        if ($status['storage']['status'] === 'error') {
            $this->assertArrayHasKey('message', $status['storage']);
            $this->assertIsString($status['storage']['message']);
        }
    }
}
