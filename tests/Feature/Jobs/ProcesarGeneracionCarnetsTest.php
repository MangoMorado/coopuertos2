<?php

namespace Tests\Feature\Jobs;

use App\Jobs\GenerarCarnetJob;
use App\Jobs\ProcesarGeneracionCarnets;
use App\Models\CarnetGenerationLog;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcesarGeneracionCarnetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_procesar_generacion_carnets_updates_log_status(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear conductores
        Conductor::factory()->count(3)->create();

        // Crear un log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-123',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => 3,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarGeneracionCarnets(
            'test-session-123',
            'masivo',
            $user->id,
            $template->id
        );
        $job->handle();

        // Verificar que el log se actualizó
        $log->refresh();
        $this->assertEquals('procesando', $log->estado);
        $this->assertNotNull($log->started_at);
        // El mensaje puede ser "Iniciando encolado" o "Procesando X carnets en segundo plano..."
        $this->assertTrue(
            str_contains($log->mensaje, 'Iniciando encolado') || str_contains($log->mensaje, 'Procesando'),
            "El mensaje debe contener información sobre el proceso. Mensaje actual: {$log->mensaje}"
        );
    }

    public function test_procesar_generacion_carnets_dispatches_individual_jobs(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear conductores
        $conductor1 = Conductor::factory()->create();
        $conductor2 = Conductor::factory()->create();
        $conductor3 = Conductor::factory()->create();

        // Crear un log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-456',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => 3,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarGeneracionCarnets(
            'test-session-456',
            'masivo',
            $user->id,
            $template->id
        );
        $job->handle();

        // Verificar que se despacharon los jobs individuales
        Queue::assertPushed(GenerarCarnetJob::class, 3);

        // Verificar que cada job se despachó con los parámetros correctos usando reflexión
        $pushedJobs = Queue::pushed(GenerarCarnetJob::class);
        $this->assertCount(3, $pushedJobs);

        // Verificar que al menos un job tiene los parámetros correctos
        $foundJob = false;
        foreach ($pushedJobs as $job) {
            $reflection = new \ReflectionClass($job);
            $conductorIdProp = $reflection->getProperty('conductorId');
            $conductorIdProp->setAccessible(true);
            $templateIdProp = $reflection->getProperty('templateId');
            $templateIdProp->setAccessible(true);
            $sessionIdProp = $reflection->getProperty('sessionId');
            $sessionIdProp->setAccessible(true);

            if ($conductorIdProp->getValue($job) === $conductor1->id
                && $templateIdProp->getValue($job) === $template->id
                && $sessionIdProp->getValue($job) === 'test-session-456') {
                $foundJob = true;
                break;
            }
        }
        $this->assertTrue($foundJob, 'Debe haber un job con los parámetros correctos');
    }

    public function test_procesar_generacion_carnets_handles_no_template(): void
    {
        $user = User::factory()->create();

        // No crear plantilla activa

        // Crear conductores
        Conductor::factory()->count(2)->create();

        // Crear un log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-789',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => 2,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Ejecutar el job (debe lanzar excepción)
        $job = new ProcesarGeneracionCarnets(
            'test-session-789',
            'masivo',
            $user->id,
            null
        );

        try {
            $job->handle();
            $this->fail('Se esperaba una excepción cuando no hay plantilla');
        } catch (\Exception $e) {
            $this->assertStringContainsString('No hay plantilla configurada', $e->getMessage());
        }

        // Verificar que el log se actualizó con el error
        $log->refresh();
        $this->assertEquals('error', $log->estado);
        $this->assertNotNull($log->error);
        $this->assertNotNull($log->completed_at);
        $this->assertStringContainsString('No hay plantilla configurada', $log->error);
    }

    public function test_procesar_generacion_carnets_handles_no_conductores(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // No crear conductores

        // Crear un log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-101',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => 0,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Ejecutar el job (debe lanzar excepción)
        $job = new ProcesarGeneracionCarnets(
            'test-session-101',
            'masivo',
            $user->id,
            $template->id
        );

        try {
            $job->handle();
            $this->fail('Se esperaba una excepción cuando no hay conductores');
        } catch (\Exception $e) {
            $this->assertStringContainsString('No hay conductores', $e->getMessage());
        }

        // Verificar que el log se actualizó con el error
        $log->refresh();
        $this->assertEquals('error', $log->estado);
        $this->assertNotNull($log->error);
        $this->assertNotNull($log->completed_at);
        $this->assertStringContainsString('No hay conductores', $log->error);
    }

    public function test_procesar_generacion_carnets_uses_template_id_when_provided(): void
    {
        $user = User::factory()->create();

        // Crear dos plantillas
        $template1 = CarnetTemplate::create([
            'nombre' => 'Plantilla 1',
            'imagen_plantilla' => 'test1.png',
            'variables_config' => [],
            'activo' => false, // Inactiva
        ]);

        $template2 = CarnetTemplate::create([
            'nombre' => 'Plantilla 2',
            'imagen_plantilla' => 'test2.png',
            'variables_config' => [],
            'activo' => true, // Activa
        ]);

        // Crear conductores
        Conductor::factory()->count(2)->create();

        // Crear un log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-template',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => 2,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Ejecutar el job con template_id específico (template1, que está inactiva)
        $job = new ProcesarGeneracionCarnets(
            'test-session-template',
            'masivo',
            $user->id,
            $template1->id // Usar template1 aunque esté inactiva
        );
        $job->handle();

        // Verificar que se usó la plantilla especificada (no la activa)
        $pushedJobs = Queue::pushed(GenerarCarnetJob::class);
        $this->assertGreaterThan(0, count($pushedJobs));

        // Verificar que todos los jobs usan template1
        foreach ($pushedJobs as $job) {
            $reflection = new \ReflectionClass($job);
            $templateIdProp = $reflection->getProperty('templateId');
            $templateIdProp->setAccessible(true);
            $this->assertEquals($template1->id, $templateIdProp->getValue($job));
        }

        // Verificar que el log contiene información de la plantilla correcta
        $log->refresh();
        $logs = $log->logs ?? [];
        $this->assertNotEmpty($logs);
        // Buscar el log que menciona la plantilla
        $plantillaLog = collect($logs)->first(function ($logEntry) {
            return isset($logEntry['mensaje']) && str_contains($logEntry['mensaje'], 'Usando plantilla');
        });
        $this->assertNotNull($plantillaLog, 'Debe existir un log que mencione la plantilla usada');
        $this->assertStringContainsString('Plantilla 1', $plantillaLog['mensaje']);
    }

    public function test_procesar_generacion_carnets_filters_conductores_by_ids(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear conductores
        $conductor1 = Conductor::factory()->create();
        $conductor2 = Conductor::factory()->create();
        $conductor3 = Conductor::factory()->create();

        // Crear un log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-filter',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => 2,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
        ]);

        // Ejecutar el job con solo conductor1 y conductor2
        $job = new ProcesarGeneracionCarnets(
            'test-session-filter',
            'masivo',
            $user->id,
            $template->id,
            [$conductor1->id, $conductor2->id] // Solo estos dos
        );
        $job->handle();

        // Verificar que solo se despacharon 2 jobs
        Queue::assertPushed(GenerarCarnetJob::class, 2);

        // Verificar que se despacharon para los conductores correctos usando reflexión
        $pushedJobs = Queue::pushed(GenerarCarnetJob::class);
        $this->assertCount(2, $pushedJobs);

        $conductorIds = [];
        foreach ($pushedJobs as $job) {
            $reflection = new \ReflectionClass($job);
            $conductorIdProp = $reflection->getProperty('conductorId');
            $conductorIdProp->setAccessible(true);
            $conductorIds[] = $conductorIdProp->getValue($job);
        }

        // Verificar que se despacharon para los conductores correctos
        $this->assertContains($conductor1->id, $conductorIds);
        $this->assertContains($conductor2->id, $conductorIds);
        $this->assertNotContains($conductor3->id, $conductorIds);
    }
}
