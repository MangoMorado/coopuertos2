<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_success_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Estado de salud obtenido exitosamente',
            ]);
    }

    public function test_health_endpoint_does_not_require_authentication(): void
    {
        // Hacer la petición sin autenticación
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verificar que no se requiere token
        $this->assertArrayNotHasKey('Authorization', $response->headers->all());
    }

    public function test_health_endpoint_has_rate_limiting(): void
    {
        // Hacer 60 requests (el límite es 60 por minuto)
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/v1/health');
            $response->assertStatus(200);
        }

        // El request 61 debería ser bloqueado por rate limiting
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_health_endpoint_returns_correct_structure(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'database' => [
                        'status',
                        'message',
                        'connection',
                    ],
                    'queue' => [
                        'status',
                        'pending',
                        'failed',
                        'connection',
                    ],
                    'storage' => [
                        'status',
                        'total',
                        'used',
                        'free',
                        'percentage',
                    ],
                    'versions' => [
                        'php',
                        'laravel',
                    ],
                    'php_extensions' => [
                        'status',
                        'extensions',
                    ],
                ],
                'message',
            ]);

        // Verificar tipos de datos
        $data = $response->json('data');

        $this->assertIsString($data['database']['status']);
        $this->assertIsString($data['database']['message']);
        $this->assertIsString($data['database']['connection']);

        $this->assertIsString($data['queue']['status']);
        $this->assertIsInt($data['queue']['pending']);
        $this->assertIsInt($data['queue']['failed']);
        $this->assertIsString($data['queue']['connection']);

        $this->assertIsString($data['storage']['status']);
        $this->assertIsString($data['storage']['total']);
        $this->assertIsString($data['storage']['used']);
        $this->assertIsString($data['storage']['free']);
        $this->assertIsNumeric($data['storage']['percentage']);

        $this->assertIsString($data['versions']['php']);
        $this->assertIsString($data['versions']['laravel']);

        $this->assertIsString($data['php_extensions']['status']);
        $this->assertIsArray($data['php_extensions']['extensions']);
    }

    public function test_health_endpoint_database_status_is_healthy(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);

        $databaseStatus = $response->json('data.database');

        $this->assertEquals('healthy', $databaseStatus['status']);
        $this->assertEquals('Conexión establecida', $databaseStatus['message']);
        $this->assertNotEmpty($databaseStatus['connection']);
    }

    public function test_health_endpoint_queue_status_includes_counts(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);

        $queueStatus = $response->json('data.queue');

        $this->assertIsString($queueStatus['status']);
        $this->assertIsInt($queueStatus['pending']);
        $this->assertIsInt($queueStatus['failed']);
        $this->assertGreaterThanOrEqual(0, $queueStatus['pending']);
        $this->assertGreaterThanOrEqual(0, $queueStatus['failed']);
        $this->assertNotEmpty($queueStatus['connection']);
    }

    public function test_health_endpoint_storage_status_includes_disk_info(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);

        $storageStatus = $response->json('data.storage');

        $this->assertIsString($storageStatus['status']);
        $this->assertIsString($storageStatus['total']);
        $this->assertIsString($storageStatus['used']);
        $this->assertIsString($storageStatus['free']);
        $this->assertIsNumeric($storageStatus['percentage']);
        $this->assertGreaterThanOrEqual(0, $storageStatus['percentage']);
        $this->assertLessThanOrEqual(100, $storageStatus['percentage']);
    }

    public function test_health_endpoint_versions_include_php_and_laravel(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);

        $versions = $response->json('data.versions');

        $this->assertIsString($versions['php']);
        $this->assertIsString($versions['laravel']);
        $this->assertNotEmpty($versions['php']);
        $this->assertNotEmpty($versions['laravel']);

        // Verificar que PHP version tiene formato correcto (ej: 8.2.12)
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $versions['php']);
    }

    public function test_health_endpoint_php_extensions_include_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);

        $phpExtensions = $response->json('data.php_extensions');

        $this->assertIsString($phpExtensions['status']);
        $this->assertIsArray($phpExtensions['extensions']);
        $this->assertContains($phpExtensions['status'], ['healthy', 'error', 'warning']);

        // Verificar que las extensiones tienen la estructura correcta
        if (! empty($phpExtensions['extensions'])) {
            $firstExtension = reset($phpExtensions['extensions']);
            $this->assertArrayHasKey('loaded', $firstExtension);
            $this->assertArrayHasKey('description', $firstExtension);
            $this->assertArrayHasKey('status', $firstExtension);
            $this->assertIsBool($firstExtension['loaded']);
            $this->assertIsString($firstExtension['description']);
            $this->assertIsString($firstExtension['status']);
        }
    }

    public function test_health_endpoint_returns_json_content_type(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }
}
