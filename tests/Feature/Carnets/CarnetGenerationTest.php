<?php

namespace Tests\Feature\Carnets;

use App\Jobs\ProcesarGeneracionCarnets;
use App\Models\CarnetGenerationLog;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CarnetGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_user_can_initiate_massive_carnet_generation(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear conductores de prueba
        Conductor::factory()->count(5)->create();

        $response = $this->actingAs($user)->post('/carnets/generar', []);

        $response->assertRedirect();
        $this->assertStringContainsString(route('carnets.exportar'), $response->getTargetUrl());
        $response->assertSessionHas('success', 'Generación de carnets iniciada. El proceso se ejecutará en segundo plano.');

        // Verificar que se creó un log de generación
        $this->assertDatabaseHas('carnet_generation_logs', [
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => 5,
        ]);

        // Verificar que se despachó el job
        Queue::assertPushed(ProcesarGeneracionCarnets::class);
    }

    public function test_user_can_initiate_selective_carnet_generation(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear conductores de prueba
        $conductor1 = Conductor::factory()->create();
        $conductor2 = Conductor::factory()->create();
        $conductor3 = Conductor::factory()->create();

        // Generar carnets solo para conductor1 y conductor2
        $response = $this->actingAs($user)->post('/carnets/generar', [
            'conductor_ids' => [$conductor1->id, $conductor2->id],
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString(route('carnets.exportar'), $response->getTargetUrl());
        $response->assertSessionHas('success', 'Generación de carnets iniciada. El proceso se ejecutará en segundo plano.');

        // Verificar que se creó un log de generación con solo 2 conductores
        $this->assertDatabaseHas('carnet_generation_logs', [
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'pendiente',
            'total' => 2,
        ]);

        // Verificar que se despachó el job
        Queue::assertPushed(ProcesarGeneracionCarnets::class);
    }

    public function test_carnet_generation_requires_active_template(): void
    {
        $user = User::factory()->create();

        // No crear plantilla activa

        // Crear conductores de prueba
        Conductor::factory()->count(3)->create();

        $response = $this->actingAs($user)->post('/carnets/generar', []);

        $response->assertRedirect(route('carnets.exportar'));
        $response->assertSessionHas('error', 'No hay plantilla activa para generar los carnets. Por favor, configure una plantilla primero.');

        // Verificar que NO se creó un log de generación
        $this->assertDatabaseMissing('carnet_generation_logs', [
            'estado' => 'pendiente',
        ]);

        // Verificar que NO se despachó el job
        Queue::assertNothingPushed();
    }

    public function test_carnet_generation_creates_generation_log(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear conductores de prueba
        Conductor::factory()->count(3)->create();

        $response = $this->actingAs($user)->post('/carnets/generar', []);

        $response->assertRedirect();
        $this->assertStringContainsString(route('carnets.exportar'), $response->getTargetUrl());

        // Verificar que se creó un log de generación con todos los campos necesarios
        $log = CarnetGenerationLog::where('user_id', $user->id)->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->session_id);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('masivo', $log->tipo);
        $this->assertEquals('pendiente', $log->estado);
        $this->assertEquals(3, $log->total);
        $this->assertEquals(0, $log->procesados);
        $this->assertEquals(0, $log->exitosos);
        $this->assertEquals(0, $log->errores);
        $this->assertIsArray($log->logs);
        $this->assertGreaterThan(0, count($log->logs));
    }

    public function test_carnet_generation_dispatches_jobs_to_queue(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear conductores de prueba
        $conductores = Conductor::factory()->count(3)->create();

        $response = $this->actingAs($user)->post('/carnets/generar', []);

        $response->assertRedirect();
        $this->assertStringContainsString(route('carnets.exportar'), $response->getTargetUrl());

        // Verificar que se despachó el job correctamente
        Queue::assertPushed(ProcesarGeneracionCarnets::class);

        // Verificar que el job se encoló en la cola correcta
        Queue::assertPushedOn('carnets', ProcesarGeneracionCarnets::class);
    }

    public function test_user_can_check_generation_progress(): void
    {
        $user = User::factory()->create();

        // Crear un log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-123',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 10,
            'procesados' => 5,
            'exitosos' => 4,
            'errores' => 1,
            'mensaje' => 'Generando carnets...',
            'logs' => [
                ['timestamp' => now()->toDateTimeString(), 'tipo' => 'info', 'mensaje' => 'Procesando...'],
            ],
        ]);

        $response = $this->actingAs($user)->getJson("/carnets/progreso/{$log->session_id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total' => 10,
            'procesados' => 5,
            'exitosos' => 4,
            'errores' => 1,
            'estado' => 'procesando',
            'mensaje' => 'Generando carnets...',
        ]);
        $response->assertJsonStructure([
            'success',
            'total',
            'procesados',
            'exitosos',
            'errores',
            'estado',
            'progreso',
            'archivo',
            'error',
            'mensaje',
            'logs',
            'tiempo_transcurrido',
            'tiempo_estimado_restante',
        ]);
    }

    public function test_generation_progress_updates_correctly(): void
    {
        $user = User::factory()->create();

        // Crear un log de generación inicial
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-456',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 10,
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
            'mensaje' => 'Iniciando...',
        ]);

        // Verificar progreso inicial
        $response = $this->actingAs($user)->getJson("/carnets/progreso/{$log->session_id}");
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 10,
            'procesados' => 0,
            'progreso' => 0.0,
        ]);

        // Actualizar el log
        $log->update([
            'procesados' => 5,
            'exitosos' => 5,
            'mensaje' => 'Procesando...',
        ]);

        // Verificar progreso actualizado
        $response = $this->actingAs($user)->getJson("/carnets/progreso/{$log->session_id}");
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 10,
            'procesados' => 5,
            'exitosos' => 5,
            'progreso' => 50.0,
        ]);
    }

    public function test_generation_log_contains_correct_metadata(): void
    {
        $user = User::factory()->create();

        // Crear una plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => 'Plantilla de Prueba',
            'imagen_plantilla' => 'test.png',
            'variables_config' => [],
            'activo' => true,
        ]);

        // Crear conductores de prueba
        $conductores = Conductor::factory()->count(5)->create();

        $response = $this->actingAs($user)->post('/carnets/generar', []);

        $response->assertRedirect();
        $this->assertStringContainsString(route('carnets.exportar'), $response->getTargetUrl());

        // Verificar que el log contiene los metadatos correctos
        $log = CarnetGenerationLog::where('user_id', $user->id)->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->session_id);
        $this->assertIsString($log->session_id);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('masivo', $log->tipo);
        $this->assertEquals('pendiente', $log->estado);
        $this->assertEquals(5, $log->total);
        $this->assertIsArray($log->logs);
        $this->assertGreaterThan(0, count($log->logs));

        // Verificar que el primer log contiene información relevante
        $primerLog = $log->logs[0];
        $this->assertArrayHasKey('timestamp', $primerLog);
        $this->assertArrayHasKey('tipo', $primerLog);
        $this->assertArrayHasKey('mensaje', $primerLog);
        $this->assertArrayHasKey('data', $primerLog);
        $this->assertEquals('info', $primerLog['tipo']);
        $this->assertArrayHasKey('template_id', $primerLog['data']);
        $this->assertArrayHasKey('template_nombre', $primerLog['data']);
        $this->assertArrayHasKey('total_conductores', $primerLog['data']);
        $this->assertEquals($template->id, $primerLog['data']['template_id']);
        $this->assertEquals(5, $primerLog['data']['total_conductores']);
    }
}
