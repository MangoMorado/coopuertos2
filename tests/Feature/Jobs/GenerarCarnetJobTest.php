<?php

namespace Tests\Feature\Jobs;

use App\Jobs\FinalizarGeneracionCarnets;
use App\Jobs\GenerarCarnetJob;
use App\Models\CarnetGenerationLog;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Models\User;
use App\Services\CarnetGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GenerarCarnetJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        // Limpiar archivos temporales
        $tempDir = storage_path('app/temp');
        if (File::exists($tempDir)) {
            File::deleteDirectory($tempDir);
        }
        $carnetsDir = storage_path('app/carnets');
        if (File::exists($carnetsDir)) {
            File::cleanDirectory($carnetsDir);
        }
        parent::tearDown();
    }

    public function test_generar_carnet_job_generates_carnet(): void
    {
        $user = User::factory()->create();

        // Crear plantilla y conductor
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        // Crear log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-123',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 1,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Mock del servicio de generación
        $mockGenerator = Mockery::mock(CarnetGeneratorService::class);
        $pdfPath = storage_path('app/temp/test_carnet_'.time().'.pdf');
        $tempDir = dirname($pdfPath);
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        File::put($pdfPath, 'fake pdf content');

        $mockGenerator->shouldReceive('generarCarnetPDF')
            ->once()
            ->with(Mockery::on(function ($arg) use ($conductor) {
                return $arg->id === $conductor->id;
            }), Mockery::on(function ($arg) use ($template) {
                return $arg->id === $template->id;
            }), Mockery::type('string'))
            ->andReturn($pdfPath);

        // Ejecutar el job
        $job = new GenerarCarnetJob($conductor->id, $template->id, $log->session_id);
        $job->handle($mockGenerator);

        // Verificar que el conductor tiene la ruta del carnet actualizada
        $conductor->refresh();
        $this->assertNotNull($conductor->ruta_carnet);
        $this->assertStringStartsWith('carnets/', $conductor->ruta_carnet);

        // Verificar que el archivo existe en la ubicación permanente
        $rutaCompleta = storage_path('app/'.$conductor->ruta_carnet);
        $this->assertTrue(File::exists($rutaCompleta));
    }

    public function test_generar_carnet_job_updates_generation_log(): void
    {
        $user = User::factory()->create();

        // Crear plantilla y conductor
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        // Crear log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-456',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 1,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Mock del servicio de generación
        $mockGenerator = Mockery::mock(CarnetGeneratorService::class);
        $pdfPath = storage_path('app/temp/test_carnet_'.time().'.pdf');
        $tempDir = dirname($pdfPath);
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        File::put($pdfPath, 'fake pdf content');

        $mockGenerator->shouldReceive('generarCarnetPDF')
            ->once()
            ->andReturn($pdfPath);

        // Ejecutar el job
        $job = new GenerarCarnetJob($conductor->id, $template->id, $log->session_id);
        $job->handle($mockGenerator);

        // Verificar que el log se actualizó
        $log->refresh();
        $this->assertEquals(1, $log->procesados);
        $this->assertEquals(1, $log->exitosos);
        $this->assertEquals(0, $log->errores);

        // Verificar que se agregó un log de éxito
        $logs = $log->logs ?? [];
        $this->assertNotEmpty($logs);
        $successLog = collect($logs)->first(function ($logEntry) {
            return isset($logEntry['tipo']) && $logEntry['tipo'] === 'success'
                && isset($logEntry['mensaje']) && str_contains($logEntry['mensaje'], 'Carnet generado');
        });
        $this->assertNotNull($successLog);
    }

    public function test_generar_carnet_job_handles_errors(): void
    {
        $user = User::factory()->create();

        // Crear plantilla y conductor
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
        ]);

        // Crear log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-789',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 1,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Mock del servicio de generación que lanza una excepción
        $mockGenerator = Mockery::mock(CarnetGeneratorService::class);
        $mockGenerator->shouldReceive('generarCarnetPDF')
            ->once()
            ->andThrow(new \Exception('Error al generar PDF'));

        // Ejecutar el job (debe lanzar excepción)
        $job = new GenerarCarnetJob($conductor->id, $template->id, $log->session_id);

        try {
            $job->handle($mockGenerator);
            $this->fail('Se esperaba una excepción');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Error al generar PDF', $e->getMessage());
        }

        // Verificar que el log se actualizó con el error
        $log->refresh();
        $this->assertEquals(1, $log->procesados);
        $this->assertEquals(0, $log->exitosos);
        $this->assertEquals(1, $log->errores);

        // Verificar que se agregó un log de error
        $logs = $log->logs ?? [];
        $this->assertNotEmpty($logs);
        $errorLog = collect($logs)->first(function ($logEntry) {
            return isset($logEntry['tipo']) && $logEntry['tipo'] === 'error'
                && isset($logEntry['mensaje']) && str_contains($logEntry['mensaje'], 'Error generando carnet');
        });
        $this->assertNotNull($errorLog);
    }

    public function test_generar_carnet_job_triggers_finalization_when_complete(): void
    {
        $user = User::factory()->create();

        // Crear plantilla y conductor
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
        ]);

        // Crear log de generación con total = 1 (último trabajo)
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-finalize',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 1,
            'procesados' => 0, // Este será el último
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Mock del servicio de generación
        $mockGenerator = Mockery::mock(CarnetGeneratorService::class);
        $pdfPath = storage_path('app/temp/test_carnet_'.time().'.pdf');
        $tempDir = dirname($pdfPath);
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        File::put($pdfPath, 'fake pdf content');

        $mockGenerator->shouldReceive('generarCarnetPDF')
            ->once()
            ->andReturn($pdfPath);

        // Ejecutar el job
        $job = new GenerarCarnetJob($conductor->id, $template->id, $log->session_id);
        $job->handle($mockGenerator);

        // Verificar que se encoló el job de finalización
        Queue::assertPushed(FinalizarGeneracionCarnets::class, function ($finalizeJob) use ($log) {
            $reflection = new \ReflectionClass($finalizeJob);
            $sessionIdProp = $reflection->getProperty('sessionId');
            $sessionIdProp->setAccessible(true);

            return $sessionIdProp->getValue($finalizeJob) === $log->session_id;
        });

        // Verificar que el job se encoló en la cola correcta
        Queue::assertPushedOn('carnets', FinalizarGeneracionCarnets::class);
    }

    public function test_generar_carnet_job_increments_processed_count(): void
    {
        $user = User::factory()->create();

        // Crear plantilla y conductor
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
        ]);

        // Crear log de generación con procesados iniciales
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-increment',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 5,
            'procesados' => 2, // Ya hay 2 procesados
            'exitosos' => 2,
            'errores' => 0,
        ]);

        // Mock del servicio de generación
        $mockGenerator = Mockery::mock(CarnetGeneratorService::class);
        $pdfPath = storage_path('app/temp/test_carnet_'.time().'.pdf');
        $tempDir = dirname($pdfPath);
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        File::put($pdfPath, 'fake pdf content');

        $mockGenerator->shouldReceive('generarCarnetPDF')
            ->once()
            ->andReturn($pdfPath);

        // Ejecutar el job
        $job = new GenerarCarnetJob($conductor->id, $template->id, $log->session_id);
        $job->handle($mockGenerator);

        // Verificar que los contadores se incrementaron correctamente
        $log->refresh();
        $this->assertEquals(3, $log->procesados); // 2 + 1
        $this->assertEquals(3, $log->exitosos); // 2 + 1
        $this->assertEquals(0, $log->errores);
    }

    public function test_generar_carnet_job_works_without_session_id(): void
    {
        // Crear plantilla y conductor
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
        ]);

        // Mock del servicio de generación
        $mockGenerator = Mockery::mock(CarnetGeneratorService::class);
        $pdfPath = storage_path('app/temp/test_carnet_'.time().'.pdf');
        $tempDir = dirname($pdfPath);
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        File::put($pdfPath, 'fake pdf content');

        $mockGenerator->shouldReceive('generarCarnetPDF')
            ->once()
            ->andReturn($pdfPath);

        // Ejecutar el job sin session_id
        $job = new GenerarCarnetJob($conductor->id, $template->id, null);
        $job->handle($mockGenerator);

        // Verificar que el conductor tiene la ruta del carnet actualizada
        $conductor->refresh();
        $this->assertNotNull($conductor->ruta_carnet);

        // Verificar que no se encoló job de finalización (no hay session_id)
        Queue::assertNothingPushed();
    }
}
